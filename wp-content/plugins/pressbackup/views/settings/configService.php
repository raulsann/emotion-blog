<?php
	$this->import('Msg.php');
	$msg = new MsgPBLib();
?>

<?php echo $this->css("framepress.default.css");?>
<?php echo $this->css("pb.css");?>
<?php
	$listType = array('input' => 'radio', 'class'=> 'service_radio');
	if($servicesLimit > 1) {$listType = array('input' => 'checkbox', 'class'=> 'service_check');}
?>

<div class="tab" ><?php echo $this->link(__('Dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'));?></div>
<div class="tab tabactive" ><?php echo $this->link(__('Service','pressbackup'), array('controller'=>'settings', 'function'=>'configService'));?></div>
<div class="tab" ><?php echo $this->link(__('Settings','pressbackup'), array('controller'=>'settings', 'function'=>'configSettings'));?></div>
<div class="tab" ><?php echo $this->link(__('Compatibility','pressbackup'), array('controller'=>'settings', 'function'=>'configCompatibility'));?></div>
<div class="tabclear" >&nbsp;</div>
<div class="tab_subnav"></div>

<div class="tab_content">
	<form method="post" action="<?php echo $this->router(array('function'=>'configServiceSave'));?>">

		<h3><?php _e('Storage Service','pressbackup'); ?></h3>
		<p><?php _e('Configure your storage services to perform backups automatically.','pressbackup'); ?></p><br>

		<?php echo $msg->show('error');?>

		<h4><?php _e('Which storage service will you use?','pressbackup'); ?></h4>

		<div class ="left" style="width: 150px; margin-right:15px;">
			<?php echo $this->img('services/pressbackup128.png');?>
			<?php $checked = ''; if( !isset($data['service']) || ( isset($data['service']) && in_array('pressbackup', $data['service']))) {$checked = 'checked';} ?>
			<p><input type="<?php echo $listType['input']?>" id="srvPressR" name="data[service][]" class="<?php echo $listType['class']?>" value="pressbackup" <?php echo $checked;?> /> <?php _e('Pressbackup','pressbackup'); ?></p>
		</div>

		<div class ="left" style="width: 150px; margin-right:15px;">
			<?php echo $this->img('services/dropbox128.png');?>
			<?php $checked = ''; if(isset($data['service']) && in_array('dropbox', $data['service'])) {$checked = 'checked';} ?>
			<p><input type="<?php echo $listType['input']?>" id="srvdBoxR" name="data[service][]" class="<?php echo $listType['class']?>" value="dropbox" <?php echo $checked;?> /> <?php _e('Dropbox','pressbackup'); ?></p>
		</div>
		<div class ="left" style="width: 150px; margin-right:15px;">
			<?php echo $this->img('services/amazon128.png');?>
			<?php $checked = ''; if(isset($data['service']) && in_array('s3', $data['service'])) {$checked = 'checked';} ?>
			<p><input type="<?php echo $listType['input']?>" id="srvS3R" name="data[service][]" class="<?php echo $listType['class']?>" value="s3" <?php echo $checked;?> /> <?php _e('Amazon S3','pressbackup'); ?></p>
		</div>
		<div class ="left" style="width: 150px; margin-right:15px;">
			<?php echo $this->img('services/folderServer128.png');?>
			<?php $checked = ''; if(isset($data['service']) && in_array('local', $data['service'])) {$checked = 'checked';} ?>
			<p><input type="<?php echo $listType['input']?>" id="srvLocalR" name="data[service][]" class="<?php echo $listType['class']?>" value="local" <?php echo $checked;?> /> <?php _e('Server Folder','pressbackup'); ?></p>
		</div>

		<div class="clear"></div><br>

		<div class="msgbox info mbox_wfixed srvHelp" id="srvPressH">
			<p><?php _e('Using Amazon cloud technology, PressBackup will handle the backups for you.','pressbackup'); ?></p>
			<p style="font-style: italic;"><b><?php _e('Important!','pressbackup'); ?>:</b> <?php _e('If you are using a free PressBackup account only the last 5 backups will be saved and will have a 50 Mb limit, a bigger backup won\'t be possible.','pressbackup'); ?></p>
		</div>

		<div class="msgbox info mbox_wfixed srvHelp" id="srvDboxH">
			<p><?php _e('Backups will be saved on a folder named PressBackup on Dropbox/Apps.','pressbackup'); ?></p>
			<p style="font-style: italic;"><b><?php _e('Important!','pressbackup'); ?>:</b> <?php _e('Dropbox has a 150 Mb limit, a backup bigger than that won\'t be possible.','pressbackup'); ?></p>
		</div>

		<div class="msgbox info mbox_wfixed srvHelp" id="srvLocalH">
			<p><?php _e('Backups will be saved on your host in a folder of your choice.','pressbackup'); ?><br/>
			<p><?php _e('You must enter the full path to the storage folder, use the base path of your blog folder as reference.','pressbackup'); ?></p>
		</div>

		<div class="msgbox info mbox_wfixed srvHelp" id="srvS3H">
			<p><?php _e('Backups will be saved on a bucket automatically created by PressBackup.','pressbackup'); ?></p>
			<p><?php _e('You must enter the Access and secret Keys provided by Amazon. You can also decide to use american or european Servers in the advanced options.','pressbackup'); ?></p>
		</div>

		<div class="srvOption" id="srvS3O">
			<br>
			<div class="left" style=" margin-right:15px;" >
				<label class="label_for_input"><?php _e('S3 accessKey','pressbackup'); ?></label><br/>
				<?php $value = ''; if(isset($data['s3']['accessKey']) && $data['s3']['accessKey'] ) {$value = $data['s3']['accessKey'];} ?>
				<input type="text" class="longinput" name="data[s3][accessKey]" value="<?php echo $value;?>" ><br/>

				<a id="ao_link" href="#"><?php _e('Advanced options','pressbackup'); ?></a>
				<div id="srvS3O_adv" style="display: none">
					<p>

					<?php $checked = ''; if(isset($data['s3']['region']) && !$data['s3']['region'] ) {$checked = 'checked';} ?>
					<input type="radio" name="data[s3][region]" value="US" <?php echo $checked;?> > <label><?php _e('US S3','pressbackup'); ?></label>&nbsp;&nbsp;

					<?php $checked = ''; if(isset($data['s3']['region']) && $data['s3']['region'] ) {$checked = 'checked';} ?>
					<input type="radio" name="data[s3][region]" value="EU" <?php echo $checked;?> > <label ><?php _e('European S3','pressbackup'); ?></label>

					</p>
				</div>
			</div>
			<div class="left">
				<label class="label_for_input"><?php _e('S3 secretKey','pressbackup'); ?></label><br/>
				<?php $value = ''; if(isset($data['s3']['secretKey']) && $data['s3']['secretKey'] ) {$value = $data['s3']['secretKey'];} ?>
				<input type="text" class="longinput" name="data[s3][secretKey]" value="<?php echo $value;?>" ><br/>
			</div>
			<div class="clear"></div>
		</div>

		<div class="srvOption" id="srvLocalO">
			<br>
			<div class="left">
				<label class="label_for_input"><?php _e('Path to storage folder:','pressbackup'); ?></label><br/>
				<?php $value = ABSPATH; if(isset($data['local']['path']) && $data['local']['path'] ) {$value = $data['local']['path'];} ?>
				<input type="text" class="longinput" name="data[local][path]" value="<?php echo $value;?>"><br/>
			</div>
			<div class="left">
			</div>
			<div class="clear"></div>
		</div>

		<hr class="separator"/>
		<input class="button" type="submit" value="<?php _e('Save','pressbackup'); ?>"> <?php _e('or','pressbackup'); ?>
		<?php echo $this->link(__('Go back to dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'), array('class'=>'pb_action2'));?>
	</form>
</div>

<div class="tab_content continue">

</div>


<script type='text/javascript'>

	if (	<?php if( !isset($data['service']) || in_array('pressbackup', $data['service'])) { echo 'true'; } else { echo 'false'; } ?>) {
		last_selected = 'srvPressR';
		jQuery('#srvPressR').attr('checked', true);
		jQuery('#srvPressH').show();
	}
	if (<?php if( isset($data['service']) && in_array('dropbox', $data['service'])) { echo 'true'; } else { echo 'false'; } ?>) {
		last_selected = 'srvdBoxR';
		jQuery('#srvdBoxR').attr('checked', true);
		jQuery('#srvDboxH').show();
	}
	if (<?php if( isset($data['service']) && in_array('s3', $data['service'])) { echo 'true'; } else { echo 'false'; } ?>) {
		last_selected = 'srvS3R';
		jQuery('#srvS3R').attr('checked', true);
		jQuery('#srvS3H').show();
		jQuery('#srvS3O').show();
	}
	if (<?php if( isset($data['service']) && in_array('local', $data['service'])) { echo 'true'; } else { echo 'false'; } ?>) {
		last_selected = 'srvLocalR';
		jQuery('#srvLocalR').attr('checked', true);
		jQuery('#srvLocalH').show();
		jQuery('#srvLocalO').show();
	}

	jQuery('.service_radio').click(function(){
		var id = jQuery(this).attr('id');

		if (last_selected == id) { return true; }

		switch (id) {
			case 'srvPressR':
				jQuery('#srvS3O').hide(); jQuery('#srvLocalO').hide();
				jQuery('#srvS3H,#srvLocalH,#srvDboxH').hide(); jQuery('#srvPressH').show();
				last_selected = 'srvProR';
			break;
			case 'srvdBoxR':
				jQuery('#srvS3O').hide(); jQuery('#srvLocalO').hide();
				jQuery('#srvS3H,#srvLocalH,#srvPressH').hide(); jQuery('#srvDboxH').show();
				last_selected = 'srvdBoxR';
			break;
			case 'srvS3R':
				jQuery('#srvLocalO').hide(); jQuery('#srvS3O').show();
				jQuery('#srvPressH,#srvLocalH,#srvDboxH').hide(); jQuery('#srvS3H').show();
				last_selected = 'srvS3R';
			break;
			case 'srvLocalR':
				jQuery('#srvS3O').hide(); jQuery('#srvLocalO').show();
				jQuery('#srvS3H,#srvPressH,#srvDboxH').hide(); jQuery('#srvLocalH').show();
				last_selected = 'srvLocalR';
			break;
		}
	});


	jQuery('.service_check').click(function(){
		var id = jQuery(this).attr('id');

		switch (id) {
			case 'srvPressR':
				jQuery('#srvS3H,#srvLocalH,#srvDboxH').hide(); jQuery('#srvPressH').show();
			break;
			case 'srvdBoxR':
				jQuery('#srvS3H,#srvLocalH,#srvPressH').hide(); jQuery('#srvDboxH').show();
			break;
			case 'srvS3R':
				jQuery('#srvS3O').slideToggle();
				jQuery('#srvPressH,#srvLocalH,#srvDboxH').hide(); jQuery('#srvS3H').show();
			break;
			case 'srvLocalR':
				jQuery('#srvLocalO').slideToggle();
				jQuery('#srvS3H,#srvPressH,#srvDboxH').hide(); jQuery('#srvLocalH').show();
			break;
		}
	});

	jQuery('#ao_link').click(function(){
		jQuery('#srvS3O_adv').slideToggle();
		return false;
	});
</script>
