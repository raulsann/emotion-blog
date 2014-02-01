/**
 * check status Cron
 */
function pressbackup_check_cron_status()
{
	jQuery.post(ajaxurl, {action:"pressbackup.wizard.chkCronTaskStatus", 'cookie': encodeURIComponent(document.cookie)}, function(data) {

		if (data.status == "ok")
		{
			jQuery('#pressbackup_mess_wait').hide();
			jQuery('.button').removeAttr('disabled');
			jQuery('.createContainer').show('slow');

		}else{
			count_fails++;
			if(count_fails == 10){
				document.location.href = reload_url_fail;
			}else{
				setTimeout('pressbackup_check_cron_status()', 1500);
			}
		}
	});
}

/*
* Number of times checked for background process start
* after 10 time of no notice about process start, it is considered dead
*/
var count_fails = 0;
pressbackup_check_cron_status();
