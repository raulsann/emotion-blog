<?php
	$this->import('Msg.php');
	$msg = new MsgPBLib();
?>

<?php echo $this->css("framepress.default.css");?>
<?php echo $this->css("pb.css");?>

<div class="tab " ><?php echo $this->link(__('Dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'));?></div>
<div class="tab " ><?php echo $this->link(__('Service','pressbackup'), array('controller'=>'settings', 'function'=>'configService'));?></div>
<div class="tab " ><?php echo $this->link(__('Settings','pressbackup'), array('controller'=>'settings', 'function'=>'configSettings'));?></div>
<div class="tab " ><?php echo $this->link(__('Compatibility','pressbackup'), array('controller'=>'settings', 'function'=>'configCompatibility'));?></div>
<div class="tabclear" >&nbsp;</div>
<div class="tab_subnav"> </div>
<div  class="tab_content">
	<form method="post" action="<?php echo $this->router(array('controller'=>'main', 'function'=>'backupRestoreFromUpload'));?>" enctype="multipart/form-data">
		<h3><?php _e("Restore your site's backup",'pressbackup'); ?></h3>

		<?php echo $msg->show('error');?>

		<p><?php _e('If you previously have downloaded a backup from PressBackup, you can restore it here.','pressbackup'); ?><br/>
		<strong><?php _e('Be careful!','pressbackup'); ?></strong> <?php _e("Your site's data will be replaced and this step can not be undone.",'pressbackup'); ?></p>

		<h4><?php _e('Select a backup from your Hard disk','pressbackup'); ?></h4>

		<div>
			<p><input type="file" name="backup" /></p>

			<p><?php _e('Max upload size','pressbackup'); ?>: <?php echo ($upload_size);?> Mb</p>

		</div>

		<br/><hr class="separator"/>
		<input class="button" type="submit" value="<?php _e('Upload','pressbackup'); ?>" <?php if ($disable_ulpoad){?>disabled<?php }?>> <?php _e('or','pressbackup'); ?>
		<?php echo $this->link(__('Back to dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'), array('class'=>'pb_action2'));?>

	</form>
</div>
<div  class="tab_content continue"></div>
