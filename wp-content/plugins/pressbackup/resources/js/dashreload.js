/*
* Run ajax and check background process status
* this function will call itself untill background finish
*/
function pressbackup_chk_status(task) {

	/*
		data: { "action" : "x", "task_now" : "z", "status": "r", response: "s"}
		action > finish | wait
		status (backprocess) >  ok | fail | percent
		task_now > *
		response > *
	*/
	jQuery.post(ajaxurl, {action:"pressbackup.main.chkCronBackupStatus", 'task': task, 'cookie': encodeURIComponent(document.cookie)}, function(data) {

		if (data.action == 'finish') {

			if(reload_url){
				pressbackup_reload_page( data );
				return true;
			}

			status_box_hide();
			return true;
		}

		if (data.action == 'wait') {

			switch (data.status) {

				case 'percent':
					status_box_show ('dinamic', data.task_now, ( parseInt( data.response.current ) * 100 ) / parseInt( data.response.total ) );
					setTimeout('pressbackup_chk_status("'+task+'")', 1000);
				break;
				case 'ok':
					status_box_show ('static', data.task_now );
					setTimeout('pressbackup_chk_status("'+task+'")', 1000);
				break;
				case 'fail':

					process_fail++;
					if( process_fail == 10 ){

						if(reload_url){
							setTimeout('pressbackup_reload_page({"status":"fail"})', 250);
							return false;
						}

						status_box_hide();
						return false;
					}

					status_box_show ('static', ' ... ' );
					setTimeout('pressbackup_chk_status("'+task+'")', 2000);

				break;
			}

		}
	});
}

/*
* show the status box with a progressbar or a loading image
* @type string: show a progress bar or a loading image (dinamic | static)
* @msg string: text to show on status box
* @value integer: for dinamic type, the value of the progress bar [0..100]
*/
function status_box_show (type, msg, value) {

	if(type == 'dinamic' ) { 
		jQuery("#pressbackup_status_box").removeClass('static');
		jQuery("#pressbackup_status_box").addClass('dinamic');
		jQuery("#progressbar").progressbar( "value" , value );
	} else {
		jQuery("#pressbackup_status_box").removeClass('dinamic');
		jQuery("#pressbackup_status_box").addClass('static');
	}
	jQuery("#pressbackup_status_text").html(msg);
	jQuery("#pressbackup_status_box").show('fast');

}

/*
* hide the status box
*/
function status_box_hide () {
	jQuery("#pressbackup_status_box").hide('fast');
}


/*
* Perform a redirect when a background process finish
* @status string: status of background process
* @data mixed: returned data by background process
*/
function pressbackup_reload_page(data) {

	var args = "";

	status_box_hide ();

	jQuery("#press_send_backup, #press_download_backup").removeClass('disabled');


	if(data.status == "fail"){
		setTimeout('document.location.href=reload_url_fail;', 1500);
	}

	else if ( data.status == "ok" ) {
		if (typeof(data.response) != 'undefined' && data.response.file != '' ) { reload_url = reload_url + '&fargs[]='+data.response.file; }
		setTimeout('document.location.href=reload_url;', 1500);
	}
}

/*
* Number of times checked for background process start
* after 10 times of no notice about process start, it is considered dead
*/
var process_fail = 0;

/*
 *Disable backup now buttons
 */
jQuery("#press_send_backup, #press_download_backup").addClass('disabled');

/*
* Begin check background process status
*/
if (reloadPage =="backupDownload") {
	setTimeout('pressbackup_chk_status("download")', 200);
}else{
	setTimeout('pressbackup_chk_status("save")', 200);
}




