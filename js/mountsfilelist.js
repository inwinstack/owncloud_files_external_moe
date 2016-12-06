/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {

	/**
	 * @class OCA.External.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc External storage file list.
	 *
	 * Displays a list of mount points visible
	 * for the current user.
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 **/
	var FileList = function($el, options) {
		this.initialize($el, options);
	};

	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.External.FileList.prototype */ {
		appName: 'External storage',

		/**
		 * @private
		 */
		initialize: function($el, options) {
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			if (this.initialized) {
				return;
			}
		},

		/**
		 * @param {OCA.External.MountPointInfo} fileData
		 */
		_createRow: function(fileData) {
			// TODO: hook earlier and render the whole row here
			var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			var $scopeColumn = $('<td class="column-scope column-last"><span></span></td>');
			var $backendColumn = $('<td class="column-backend"></td>');
			var scopeText = t('files_external_moe', 'Personal');
			if (fileData.scope === 'system') {
				scopeText = t('files_external_moe', 'System');
			}
			$tr.find('.filesize,.date').remove();
			$scopeColumn.find('span').text(scopeText);
			$backendColumn.text(fileData.backend);
			$tr.find('td.filename').after($scopeColumn).after($backendColumn);
			$tr.find('td.filename input:checkbox').remove();
			return $tr;
		},

		updateEmptyContent: function() {
			var dir = this.getCurrentDirectory();
			if (dir === '/') {
				// root has special permissions
				this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
				this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
			}
			else {
				OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
			}
		},

		getDirectoryPermissions: function() {
			return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
		},

		updateStorageStatistics: function() {
			// no op because it doesn't have
			// storage info like free space / used space
		},

		reload: function() {
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}
			this._reloadCall = $.ajax({
				url: OC.linkToOCS('apps/files_external_moe/api/v1') + 'mounts',
				data: {
					format: 'json'
				},
				type: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('OCS-APIREQUEST', 'true');
				}
			});
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},

		reloadCallback: function(result) {
			delete this._reloadCall;
			this.hideMask();

			if (result.ocs && result.ocs.data) {
				this.setFiles(this._makeFiles(result.ocs.data));
			}
			else {
				// TODO: error handling
			}
		},

		/**
		 * Converts the OCS API  response data to a file info
		 * list
		 * @param OCS API mounts array
		 * @return array of file info maps
		 */
		_makeFiles: function(data) {
			var files = _.map(data, function(fileData) {
				fileData.icon = OC.imagePath('core', 'filetypes/folder-external');
				fileData.mountType = 'external';
				return fileData;
			});

			files.sort(this._sortComparator);

			return files;
		}
	});

	/**
	 * Mount point info attributes.
	 *
	 * @typedef {Object} OCA.External.MountPointInfo
	 *
	 * @property {String} name mount point name
	 * @property {String} scope mount point scope "personal" or "system"
	 * @property {String} backend external storage backend name
	 */

	OCA.External.FileList = FileList;
})();
