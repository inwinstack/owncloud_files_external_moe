$(document).ready(function() {
	OCA.External.Settings.mountConfig.whenSelectAuthMechanism(function($tr, authMechanism, scheme) {
		if (authMechanism === 'oauth2::oauth2') {
			var config = $tr.find('.configuration');
			config.append($(document.createElement('input'))
				.addClass('button auth-param')
				.attr('type', 'button')
				.attr('value', t('files_external_moe', 'Reconnect'))
				.attr('name', 'oauth2_grant')
			);

			var configured = $tr.find('[data-parameter="configured"]');
			if ($(configured).val() == 'true') {
				$tr.find('.configuration input').attr('disabled', 'disabled');
				$tr.find('.configuration input').hide();
			} else {
			    var client_id = $tr.find('.configuration [data-parameter="client_id"]').val();
			    var client_secret = $tr.find('.configuration [data-parameter="client_secret"]').val();
			    var redirect = location.protocol + '//' + location.host + location.pathname;
			    // client_type = googledrive/dropbox2/onedrive
			    var client_type = $tr.attr('class');
			    if (client_type == 'onedrive'){
				var state = $tr.find('.configuration [data-parameter="state"]');
				var state_val = state.val();
				}
			    if (client_id != '' && client_secret != '') {
				var params = {};
				window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
				    params[key] = value;
				    });
				if (params['code'] !== undefined) {
				    if (client_type == 'dropbox2'){
					var state_val = params['state'];
				    }
				    var token = $tr.find('.configuration [data-parameter="token"]');
				    var statusSpan = $tr.find('.status span');
				    statusSpan.removeClass();
				    statusSpan.addClass('waiting');
				    $.post(OC.filePath('files_external_moe', 'ajax', 'oauth2.php'),
					    {
						step: 2,
						client_id: client_id,
						client_secret: client_secret,
						client_type: client_type,
						redirect: redirect,
						code: params['code'],
						state: state_val,
					    }, function(result) {
						if (result && result.status == 'success') {
						    $(token).val(result.data.token);
						    $(state).val(result.data.state);
						    $(configured).val('true');
						    OCA.External.Settings.mountConfig.saveStorageConfig($tr, function(status) {
							if (status) {
							    $tr.find('.configuration input').attr('disabled', 'disabled');
							    $tr.find('.configuration input').hide();
							}
						    });
						 } else {
						     OC.dialogs.alert(result.data.message,
							     t('files_external_moe', 'Error configuring OAuth2'));
						     }
						}
				    );
				}
			    }
			}
		}
	});

	$('#externalStorage').on('click', '[name="oauth2_grant"]', function(event) {
		event.preventDefault();
		var tr = $(this).parent().parent();
		var configured = $(this).parent().find('[data-parameter="configured"]');
		var redirect = location.protocol + '//' + location.host + location.pathname;
		var client_type = tr.attr('class');

		if (client_type == 'googledrive'){
		    var google_id = '857771313057-2jqkp0ipoq1tm7l5m3d66lkmf1asjdlb.apps.googleusercontent.com';
		    var google_secret = 'PqFV0ZLo7VPOLmtUmz3l29Hq';

		    $(this).parent().find('[data-parameter="client_id"]').
		    attr('value', google_id);
		    $(this).parent().find('[data-parameter="client_secret"]').
		    attr('value', google_secret);

		}

		else if(client_type == 'dropbox2'){
		    var dropbox_id = 'roy2119npmyufpu';
		    var dropbox_secret = 'orzgyl1z1pr3cnh';

		    $(this).parent().find('[data-parameter="client_id"]').
		    attr('value', dropbox_id);
		    $(this).parent().find('[data-parameter="client_secret"]').
		    attr('value', dropbox_secret);

		}

		else{
		    var onedrive_id = '4f49ed22-04e5-46bd-98fb-49b4b4b380d5';
		    var onedrive_secret = 'aMEFj4NhffZcU3VzC02F5BU';
		    $(this).parent().find('[data-parameter="client_id"]').
		    attr('value', onedrive_id);
		    $(this).parent().find('[data-parameter="client_secret"]').
		    attr('value', onedrive_secret);

		    var state = $(this).parent().find('[data-parameter="state"]');
		}

		var client_id = $(this).parent().find('[data-parameter="client_id"]').val();
		var client_secret = $(this).parent().find('[data-parameter="client_secret"]').val();

		if (client_id != '' && client_secret != '') {
			var token = $(this).parent().find('[data-parameter="token"]');
			$.post(OC.filePath('files_external_moe', 'ajax', 'oauth2.php'),
				{
					step: 1,
					client_id: client_id,
					client_secret: client_secret,
					redirect: redirect,
					client_type: client_type
				}, function(result) {
					if (result && result.status == 'success') {
						$(configured).val('false');
						$(token).val('false');
						$(state).val(result.data.state);
						OCA.External.Settings.mountConfig.saveStorageConfig(tr, function(status) {
							window.location = result.data.url;
						});
					} else {
						OC.dialogs.alert(result.data.message,
							t('files_external_moe', 'Error configuring OAuth2')
						);
					}
				}
			);
		}
	});

});
