	<h3><?php _e('Welcome and Thanks for installing PressBackup!','pressbackup'); ?></h3>
	<p><?php _e('PressBackup allows you to schedule backups of your entire site, as well as perform manual backups whenever you want to. It also lets you restore backups, and helps you quickly migrate servers in the event of hardware failure or moving between domains/servers.','pressbackup'); ?></p>
	<br>
	<p><?php _e('Now that you’re registered, we’ll be backing up your site in no time','pressbackup'); ?></p>

	<?php if(isset($check_cron)) { ?>
		<div id="pressbackup_mess_wait">
			<br>
			<?php _e('Please wait a few seconds while we perform some compatibility tests on your system.', 'pressbackup'); ?>
			<br>
			<?php echo $this->img('indicator.gif'); ?>
		 </div>
		<?php echo $this->js("cron_chk.js");?>
	<?php }?>

	<hr class="separator">
	<a href="<?php echo $this->router(array('function'=> 'wizardSetDefaultService'));?>" class="button" <?php if(isset($check_cron)){?> disabled="true" <?php }?> ><?php _e('Start now!', 'pressbackup'); ?></a>

	<script type="text/javascript">
		var reload_url_fail = '<?php echo str_replace('&amp;', '&', $this->router(array('controller'=>'settings', 'function'=> 'wizardSetCronTaskStatusFail'))); ?>';

		jQuery('.button').click(function(){
			var disabled = jQuery(this).attr('disabled');
			if (disabled){
				return false;
			}
			return true;
		});
	</script>
