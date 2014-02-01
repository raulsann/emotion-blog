<?php
	$this->import('Msg.php');
	$msg = new MsgPBLib();
?>

	<?php echo $this->css("framepress.default.css");?>
	<?php echo $this->css("pb.css");?>

	<div class="tab " ><?php echo $this->link(__('Dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'));?></div>
	<div class="tab " ><?php echo $this->link(__('Service','pressbackup'), array('controller'=>'settings', 'function'=>'configService'));?></div>
	<div class="tab " ><?php echo $this->link(__('Settings','pressbackup'), array('controller'=>'settings', 'function'=>'configSettings'));?></div>
	<div class="tab tabactive" ><?php echo $this->link(__('Compatibility','pressbackup'), array('controller'=>'settings', 'function'=>'configCompatibility'));?></div>
	<div class="tabclear" >&nbsp;</div>
	<div class="tab_subnav"> </div>
	<div class="tab_content">

		<form method="post" action="<?php echo $this->router(array('controller'=>'settings', 'function'=>'configCompatibilitySave'));?>" >
			<h3><?php _e('Compatibility Settings','pressbackup'); ?></h3>

			<?php echo $msg->show('error');?>

			<p><?php _e('Here you can configure PressBackup compatibility Settings','pressbackup'); ?></p>
			<p><?php _e('Pressbackup can run in a huge ecosystem: different HTTP Server types and versions, different PHP versions, etc.','pressbackup'); ?><br/>
			<?php _e('So, because of that it may have some problems','pressbackup'); ?></p>

			<div style="float: left; width: 350px;">
				<h4><a href="#" class="help_radio" id="bg_help"> <?php echo $this->img('help.png');?></a> <?php _e('Background process creation','pressbackup'); ?> </h4>
				<ul>
					<li><input id="bg_10" type="radio" name="data[compatibility][background]" value ="10" <?php if ($settings['compatibility']['background']==10) {?>checked<?php }?>> <?php _e('Soft','pressbackup'); ?></li>
					<li><input id="bg_20" type="radio" name="data[compatibility][background]" value ="20" <?php if ($settings['compatibility']['background']==20){?>checked<?php }?>> <?php _e('Medium','pressbackup'); ?></li>
					<li><input id="bg_30" type="radio" name="data[compatibility][background]" value ="30" <?php if ($settings['compatibility']['background']==30){?>checked<?php }?>> <?php _e('Hard','pressbackup'); ?></li>
				</ul>
			</div>

			<div style="float: left; width: 350px;">
				<h4><a href="#" class="help_radio" id="zip_help"><?php echo $this->img('help.png');?></a> <?php _e('Zip Creation','pressbackup'); ?> </h4>
				<ul>
					<li><input id="zip_10" type="radio" name="data[compatibility][zip]" value ="10" <?php if ($settings['compatibility']['zip']==10) {?>checked<?php }?>> <?php _e('PHP','pressbackup'); ?></li>
					<li><input id="zip_20" type="radio" name="data[compatibility][zip]" value ="20" <?php if ($settings['compatibility']['zip']==20) {?>checked<?php }?>> <?php _e('Shell Zip','pressbackup'); ?></li>
				</ul>
			</div>
			<div class="clear"></div>

			<br/>

			<div class="msgbox info mbox_wfixed " id="bg_tip">
				<p><?php _e('Background process creation - Help','pressbackup'); ?></p>
				<p><?php _e('The process of create and send the backup can take too much time, and the browser can crash after wait more that 30 seconds. because of thats we have to do that process in background.','pressbackup'); ?></p>
				<p><?php _e('If you see, on the manual backup, that the progress bar go hide after a few seconds without the wanted result, try to change the value of this setting.','pressbackup'); ?></p>
			</div>

			<div class="msgbox info mbox_wfixed srvHelp" id="zip_tip">
				<p><?php _e('Zip Creation - Help','pressbackup'); ?></p>
				<p><?php _e('The backup is created using zip files, this reduce the weight of them.','pressbackup'); ?></p>
				<p><?php _e('PHP Zip librarie works in almost all hosts, but for the ones with low work capacity (poor RAM and/or poor CPU) and hight amount of info (a bigger blog), it can take too long or produce errors','pressbackup'); ?></p>
				<p><?php _e("Shell Zip app work faster and take less resources, but it can not be available on some hosts",'pressbackup'); ?></p>
			</div>

			<hr class="separator"/>

			<input class="button" type="button" value="<?php _e('Restore','pressbackup'); ?>" onclick="restore_def();">
			<input class="button" type="submit" value="<?php _e('Save','pressbackup'); ?>"> <?php _e('or','pressbackup'); ?>
			<?php echo $this->link(__('Go back to dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'), array('class'=>'pb_action2'));?>
		</form>
	</div>

	<div class="tab_content continue">
	</div>

	<script type="text/javascript">
		function restore_def (){
			jQuery("#bg_10").click();
			jQuery("#zip_10").click();
			return false;
		}

		jQuery('.help_radio').click(function(){
			var id = jQuery(this).attr('id');

			switch (id) {
				case 'bg_help':
					if (jQuery('#bg_tip').css('display') == 'none'){
						jQuery('#zip_tip').hide(); jQuery('#bg_tip').slideToggle();
					}
				break;
				case 'zip_help':
					if (jQuery('#zip_tip').css('display') == 'none'){
						jQuery('#bg_tip').hide(); jQuery('#zip_tip').slideToggle();
					}
				break;
			}
			return false;
		});


	</script>
