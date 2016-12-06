$(document).ready(function() {
var tbody = $('#externalStorage tbody');
$('#addMountPoint').hide();
tbody.find('.mountOptionsToggle').hide();
tbody.find('.authentication').hide();
tbody.find('.mountPoint input').prop('disabled', true);

	function suggestMountPoint (defaultMountPoint) {
		var $el = $('#externalStorage.grid');
		var pos = defaultMountPoint.indexOf('/');
		if (pos !== -1) {
			defaultMountPoint = defaultMountPoint.substring(0, pos);
		}
		defaultMountPoint = defaultMountPoint.replace(/\s+/g, '');
		var i = 1;
		var append = '';
		var match = true;
		while (match && i < 20) {
			match = false;
			$el.find('tbody td.mountPoint input').each(function(index, mountPoint) {
				if ($(mountPoint).val() === defaultMountPoint+append) {
					match = true;
					return false;
				}
			});
			if (match) {
				append = i;
				i++;
			} else {
				break;
			}
		}
		return defaultMountPoint + append;
	};
		
	$( ".externalBtn" ).on("click", function(e) {
		e.preventDefault();
		storageName = e.target.textContent;
		appname = "files_external_moe";
		dialog = $('<div>');
		dialog.attr('id','ex-dialog');
		
		text1 = t(appname, "System will create a folder in your file list.");
		text2 = t(appname, "This folder allows you to access files that are mounted on an external cloud personal space."); 
		text3 = t(appname, "Please input a name for this folder:");
		content1 = $('<p>');
		content1.text(text1);
		dialog.append(content1);
		content2 = $('<p>');
		content2.text(text2);
		dialog.append(content2);
		content3 = $('<p>');
		content3.text(text3);
		dialog.append(content3);
		Name = suggestMountPoint(storageName);
		input = $('<input>');
		input.attr('id', 'ex-name');
		input.val(Name);
		dialog.append(input);
	
		dialog.dialog({
			autoOpen:true,
			title: t(appname, "Add ") + storageName,
			modal:true,
			buttons: [{
			    text: t(appname,"Add"),
			    id: 'ex-add',
			    click: function() {
				folderName = $('#ex-name').val();
				appname = "files_external_moe";
				$.ajax({
			    	    method:'POST',
			    	    url: OC.filePath(appname, 'ajax', 'externalpathcheck.php'),
				    data: {
					external_path: folderName,
				    },
				}).done(function(result) {
				    valid = result.data.result;
				    if(valid == 'invalid') {
					OC.Notification.showTemporary(t(appname, "The folder name contains invalid characters"));
				    }
				    else if (valid){	
					OC.Notification.showTemporary(t(appname, "The folder name already existed"));
				    }
				    else if(storageName == "GoogleDrive") {
				    	$('#selectBackend').val('googledrive').trigger('change');
				    	$el = $('#externalStorage tbody').find( ":hidden.googledrive");
				    	$el.find('td:nth-child(2) input').val(folderName);
				    	$el.find('td:nth-child(5) input').click();
				    }
				    else if(storageName == "Dropbox") {
				    	$('#selectBackend').val('dropbox2').trigger('change');
				    	$el = $('#externalStorage tbody').find( ":hidden.dropbox2");
				    	$el.find('td:nth-child(2) input').val(folderName);
				    	$el.find('td:nth-child(5) input').click();
				    }
				    else if(storageName == "OneDrive") {
                                    	$('#selectBackend').val('onedrive').trigger('change');
				    	$el = $('#externalStorage tbody').find( ":hidden.onedrive");
				    	$el.find('td:nth-child(2) input').val(folderName);
				    	$el.find('td:nth-child(5) input').click();
				    }
				}); 	
				    

				$('#ex-dialog').remove();
			    }},
			    {	
			    text: t(appname,"Cancel"),
			    click: function() {
				$('#ex-dialog').remove();
			    }
			    }],
			close: function() {
			    $('#ex-name').text("");
			    $('#ex-dialog').remove();
			}
		});
        });


});
