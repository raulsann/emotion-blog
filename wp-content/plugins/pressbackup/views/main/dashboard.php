<?php
	$this->import('dashHelper.php');
	$dh = new DashHelperPBLib();

	$this->import('Msg.php');
	$msg = new MsgPBLib();

	$backup_types = explode(',',$backup_type);
	date_default_timezone_set($timezone_string);


	//get user info to create form
	$ud = get_userdata(get_current_user_id());
	if(isset($ud->data)){
		$user_email = $ud->data->user_email;
	} else {
		$user_email = $ud->user_email;
	}
?>

<?php  echo $this->css('ui-lightness/jquery-ui.css');?>
<?php echo $this->css('framepress.default.css');?>
<?php echo $this->css('pb.css');?>

<!-- MAIN BOX TABS -->
<div class="tab tabactive" ><?php echo $this->link(__('Dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'));?></div>
<div class="tab " ><?php echo $this->link(__('Service','pressbackup'), array('controller'=>'settings', 'function'=>'configService'));?></div>
<div class="tab " ><?php echo $this->link(__('Settings','pressbackup'), array('controller'=>'settings', 'function'=>'configSettings'));?></div>
<div class="tab " ><?php echo $this->link(__('Compatibility','pressbackup'), array('controller'=>'settings', 'function'=>'configCompatibility'));?></div>
<div class="tabclear" >&nbsp;</div>
<div class="tab_subnav"> </div>
<div  class="tab_content">

	<!-- MESSAGES -->
	<?php echo $msg->show('error');?>
	<?php echo $msg->show('info');?>

	<!-- SETTINGS INFO -->
	<?php  echo $dh->currentSettings(); ?>

	<br/><hr class="separator"/>

	<!-- BACKUP ACTIONS -->
	<?php echo $this->link( $this->img('disk.gif') . ' '.__('Backup now','pressbackup'), "#", array('class'=>'pb_action', 'id'=>'press_send_backup'));?>
	<?php echo $this->link( $this->img('download.gif') . ' '.__('Backup now','pressbackup').' <i class="pba_details">('.__('Download','pressbackup').')</i>', "#", array('class'=>'pb_action', 'id'=>'press_download_backup'));?>
	<?php echo $this->link( $this->img('upload.gif') . ' '.__('Restore backup','pressbackup').' <i class="pba_details">('.__('From computer','pressbackup').')</i>', array('controller'=>'main', 'function'=>'uploadAndRestoreBackup'), array('class'=>'pb_action'));?>

	<hr class="separator"/>

	<h4><?php _e('Backup list','pressbackup'); ?></h4>

	<!-- BACKUPS TABLE STORAGE SERVICE TABS -->
	<?php  echo $dh->backupListTabs('Services', $service); ?>

	<!-- BACKUPS TABLE SITE SELECTOR TAB -->
	<?php  echo $dh->backupListTabs('Sites', $from); ?>

	<!-- BACKUPS TABLE PAGINATOR -->
	<div class="tabclear paginator">
		<?php  echo  $dh->paginator($backup_paginator); ?>
	</div>

	<!-- BACKUPS TABLE -->
	<table class="widefat tabbed" cellspacing="0">
		<tr class="alternate">
			<td class="row-title"><?php _e('From','pressbackup'); ?></td>
			<td class="row-title"><?php _e('Type','pressbackup'); ?></td>
			<td class="row-title"><?php _e('Date','pressbackup'); ?></td>
			<td class="row-title"><?php _e('Size','pressbackup'); ?></td>
		</tr>
		<?php if(count($backup_list)==0){?>
			<tr>
				<td><?php _e('Empty','pressbackup'); ?></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		<?php }?>

		<?php $counter=1;?>

		<?php foreach($backup_list as $key => $value){?>
			<tr <?php  if($counter%2==0){echo 'class="alternate"';}?>>
				<?php
					//Prepare info to show in table
					$file_name = explode('_', $value['name']);
					$name= str_replace('-', ' ', rawurldecode($file_name[0]));
					$time=date_i18n('M d, Y - ga', $value['time']);
					$size=round(($value['size'] / 1048576), 2);
					$types=array(7=>__('Database','pressbackup'), 5=>__('Themes','pressbackup'), 3=>__('Plugins','pressbackup'), 1=>__('Uploads','pressbackup'));
					$type = explode('-', $file_name[1]);
					$backup_type=array();
					for($i = 0; $i<count($type); $i++){$backup_type[]=$types[$type[$i]];}
				?>
				<td>
					<?php echo $name?><br/>
					<div class="options">
						<?php echo $this->link(__('Restore','pressbackup'), array('function'=>'backupRestoreFromService', rawurldecode($value['name'])), array('class'=>'pb_action2 pb_restore'));?> |
						<?php echo $this->link(__('Download','pressbackup'), array('function'=> 'backupGet', rawurldecode($value['name'])), array('class'=>'pb_action2'));?> |
						<?php echo $this->link(__('Delete','pressbackup'), array('function'=>'backupDelete', rawurldecode($value['name'])), array('class'=>'pb_action2 pb_delete'));?>
					</div>
				</td>
				<td><?php echo (join(', ',$backup_type))?></td>
				<td><?php echo $time?></td>
				<td><?php echo $size?>MB</td>
			</tr>

			<?php $counter++;?>
		<?php }?>
	</table>

	<!-- BACKUPS NOW STATUS -->
	<div class="loading_bar dnone static" id='pressbackup_status_box'>
		<hr class="separator"/>
		<div><?php _e('Please do not close your window, this may take a few minutes','pressbackup'); ?></div>
		<div><?php _e('Task','pressbackup'); ?>: <span id="pressbackup_status_text">...</span></div>

		<?php echo $this->img('indicator.gif', array('class'=>'static_image'));?>
		<div id="progressbar" class="dinamic_image"></div>

		<br>
		<?php echo $this->link( $this->img('close.gif') . ' '.__('Cancel Backup','pressbackup'), array('function'=>'backupCancel'), array('class'=>'pb_action cancelBackup'));?>
	</div>

</div>

<!-- NEWSLETTER && SUPPORT-->
<div class="tab_content continue">

	<div class="newsletter left">
		<h4><?php _e('Signup for PressBackup News to get updates','pressbackup'); ?></h4>
		<form action="http://infinimedia.createsend.com/t/y/s/otyxj/" method="post" id="subForm">
			<div>
				<input type="text" name="cm-otyxj-otyxj" id="otyxj-otyxj" class="longinput" value="<?php echo $user_email; ?>" onfocus="if(jQuery(this).val() == '<?php echo $user_email; ?>'){ jQuery(this).val('') }" onblur="if(jQuery(this).val() == ''){ jQuery(this).val('<?php _e('Your email address here','pressbackup'); ?>') }" />&nbsp;&nbsp;
				<input class="button" type="submit" value="<?php _e('Subscribe','pressbackup'); ?>" />
			</div>
		</form>
		<small><?php printf(__('Found a bug? go to %1$s and send us','pressbackup'), $this->link('http://pressbackup.com/contact', 'http://pressbackup.com/contact')) ?> <?php echo $this->link(__('this info','pressbackup'), array('controller'=>'main', 'function'=>'reportInfo'));?></small>
	</div>
	<div class="upgrade_message_dash left">
		<div class="left"><a class="target-popup" href="https://twitter.com/share?original_referer=https%3A%2F%2Fpressbackup.com&via=PressBackup&text=I just intalled @PressBackup on my WordPress site: easy, faster, perfect!"  title="Share PressBackup on twitter" ><?php echo $this->img("social/Twitter_001.jpg");?></a></div>
		<div class="left"><a class="target-popup" href="http://www.facebook.com/share.php?u=http%3A%2F%2Fpressbackup.com" title="Share PressBackup on facebook"  ><?php echo $this->img("social/facebook_001.jpg");?></a></div>
		<div class="left">
			<h1><a href="<?php echo $dash_promo['url'];?>" title="Upgrade to a better package"  class ="upgrade_button"> <?php echo $dash_promo['text'];?></a></h1>
		</div>
		<div class="clear"></div>
		<p> * This plan is the best fit for your site based on your website size. It also includes additional features and more responsive support options.</p>

	</div>
	<div class="clear"></div>

</div>

<!-- BACKUP NOW OPTIONS DIALOG -->
<div id="backup_options" style='display:none' title="<?php _e('What do you want to backup?','pressbackup'); ?>">
	<div>
		<div class="msgbox warning wpf-msg" style="display: none; ">
			<p><?php _e('Specify what you would like to backup','pressbackup'); ?></p>
		</div>
		<ul class="left">
			<li><input type="checkbox" name="data[preferences][type][]" value ="7" <?php if(in_array('7',$backup_types)){echo "checked";}?>> <?php _e('Database','pressbackup'); ?> </li>
			<li><input type="checkbox" name="data[preferences][type][]" value ="5" <?php if(in_array('5',$backup_types)){echo "checked";}?>> <?php _e('Themes','pressbackup'); ?></li>
		</ul>
		<ul class="right" style="margin-right:15px">
			<li><input type="checkbox" name="data[preferences][type][]" value ="3" <?php if(in_array('3',$backup_types)){echo "checked";}?>> <?php _e('Plugins','pressbackup'); ?></li>
			<li><input type="checkbox" name="data[preferences][type][]" value ="1" <?php if(in_array('1',$backup_types)){echo "checked";}?>> <?php _e('Uploads','pressbackup'); ?></li>
		</ul>
		<div class="clear"></div>
	</div>

	<hr class="separator"/>
	<?php  echo $this->link(__('Backup','pressbackup'), array('controller'=>'main', 'function'=>'backupStart', 'dashboard',), array('class'=>'pb_nowoption button ', 'id'=>'press_send_backup_ok', 'style'=>'dislay: none'));?>
	<?php  echo $this->link(__('Backup','pressbackup'), array('controller'=>'main', 'function'=>'backupStart', 'backupDownload'), array('class'=>'pb_nowoption button', 'id'=>'press_download_backup_ok', 'style'=>'dislay: none'));?>
	<?php  echo __('or', 'pressbackup') . ' ' . $this->link(__('cancel','pressbackup'), "#", array('class'=>'pb_action press_backup_cancel ' ));?>
</div>

<!-- ALL SITES PASS REQUEST DIALOG -->
<div id="pass_request" style='display:none'>
	<h4><?php _e('Are you the account owner?','pressbackup'); ?></h4>
	<label>Pressbackup password</label>
	<input id="requestpass_input" type="password" value = "" style="width:100%; height:40px; padding:5px;">

	<hr class="separator"/>
	<?php  echo $this->link(__('Authorize','pressbackup'), '#', array('class'=>'button requestpass_ok'));?>
	<?php  echo __('or', 'pressbackup') . ' ' . $this->link(__('cancel','pressbackup'), "#", array('class'=>'pb_action requestpass_cancel' ));?>
</div>

<?php if($first_backup_msg){?>
<!-- FIRST BACKUP FINISHED MESSAGE -->
<div id="upgrade_message" title="<?php _e('Support us','pressbackup'); ?>"  style='display:none'>
	<div>
		<h2><?php _e('Congrats! Your first backup was complete! :-)','pressbackup'); ?></h2>
		<h3><?php _e('Now that your files are safely backed up to the cloud, we\'d love if you could help us out by sharing PressBackup with your friends. Or upgrade your account and get more space and features (like website monitoring and alerts).','pressbackup'); ?></h3>

		<div class="left"><a class="target-popup" href="https://twitter.com/share?original_referer=https%3A%2F%2Fpressbackup.com&via=PressBackup&text=I just intalled @PressBackup on my WordPress site: easy, faster, perfect!"  title="Share PressBackup on twitter" ><?php echo $this->img("social/Twitter_001.jpg");?></a></div>
		<div class="left"><a class="target-popup" href="http://www.facebook.com/share.php?u=http%3A%2F%2Fpressbackup.com" title="Share PressBackup on facebook"  ><?php echo $this->img("social/facebook_001.jpg");?></a></div>
		<div class="left">
			<h1><a href="<?php echo $first_backup_msg['url'];?>" title="Upgrade to a better package"  class ="upgrade_button"> <?php echo $first_backup_msg['text'];?></a></h1>
		</div>
		<div class="clear"></div>
		<p> * This plan is the best fit for your site based on your website size. It also includes additional features and more responsive support options.</p>

	</div>
</div>
<?php }?>

<?php  echo $this->js('jquery-ui-1.9.2.custom.min.js');?>

<script type='text/javascript'>

	var delete_confirm="<?php _e('Do you really want to delete this backup?','pressbackup'); ?>",
	delete_status="<?php _e('Deleting backup','pressbackup'); ?>",
	restore_confirm="<?php _e('Do you really want to apply this backup?','pressbackup'); ?>",
	restore_status="<?php _e('Restoring from backup','pressbackup'); ?>",
	cancelBackup_confirm = "<?php _e('Do you really want to cancel this backup?','pressbackup'); ?>",
	backup_types = <?php echo json_encode($backup_types);?>;

	<?php  if($reload) {?>
	var reloadPage = '<?php  echo $reload; ?>',
	reload_url = '<?php  echo str_replace('&amp;', '&', $this->router(array('controller'=>'main', 'function'=>$reload))); ?>',
	reload_url_fail = '<?php  echo str_replace('&amp;', '&', $this->router(array('controller'=>'main', 'function'=>'dashOptions', false, false, true))); ?>';
	jQuery('#progressbar').progressbar({'value': '0' });
	<?php }?>

</script>

<?php  echo $this->js("dashboard.js");?>
<?php if($settings['membership']=='developer'){ echo $this->js("md5.js"); }?>
<?php  if($reload) { echo $this->js("dashreload.js"); }?>

