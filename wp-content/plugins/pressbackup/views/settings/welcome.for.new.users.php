	<style type="text/css">
		label{ display: block;	font-size:14px; font-weight: bold; color: #565656; }
		.form_pair { float:left; padding: 10px 0px 15px 0; width: 50% }
		.form_pair.doble { width: 98%; }	
		.form_pair + .form_pair{ padding-right: 0; }
		input, select, option , textarea{	height: 45px; width: 95%; border:1px solid #7F7F7F; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; color:#565656; font-size:22px;}
		input[type="submit"]{ background:#616161; color:white; font-size:16px; font-weight:bold; width: auto;}
		input[type="submit"]:hover{ background:#1E90FF; color:white; font-size:16px; font-weight:bold; }
		input[type="file"]{ height: auto;}
		textarea{ height: 200px; }
		.errori { color:red; }
		.left {float: left;}
		.right {float: right;}
		.clear{clear:both;}
		.notice{font-size: 17px; padding: 0 0px 0 0;}
		.notice li {margin-bottom: 30px;}
	</style>

	<h3><?php _e('Welcome and Thanks for installing PressBackup!','pressbackup'); ?></h3>
	<p><?php _e('PressBackup allows you to schedule backups of your entire site, as well as perform manual backups whenever you want to. It also lets you restore backups, and helps you quickly migrate servers in the event of hardware failure or moving between domains/servers.','pressbackup'); ?></p>
	<br>
	
	<?php if(isset($check_cron)) { ?>
		<div id="pressbackup_mess_wait">
			<br>
			<?php _e('Please wait a few seconds while we perform some compatibility tests on your system.', 'pressbackup'); ?>
			<br>
			<?php echo $this->img('indicator.gif'); ?>
		 </div>
		<?php echo $this->js("cron_chk.js");?>
	<?php }?>

	<div class="createContainer" <?php if(isset($check_cron)){?> style="display:none;" <?php }?>  >
		<br>
		<h3><?php _e('You will need a PressBackup account to use this plugin.','pressbackup'); ?></h3>
		<form id="create_form" method="post" action="<?php echo $this->router(array('function' => 'createAccount', 'free')); ?>" class="left" style="width:70%">
			<!-- [User][username] -->
			<div class="form_pair">
				<label for="in_username">Username</label>
				<input type="text" name="data[User][username]" id="in_username" value="<?php echo $data['User']['username'];?>" autofocus>
			</div>
			
			<!-- [User][email] -->
			<div class="form_pair" id='in_email' style=<?php if($show_email_field){echo 'display:block;';}else{echo 'display:none;';}?>>
				<label for="in_email">Email</label>
				<input type="text" name="data[User][email]" id="in_email" value="<?php echo $data['User']['email'];?>">
			</div>
			<div class="clear"> </div>
			<br>

			<!-- [User][fullname] -->
			<div class="form_pair">
				<label for="in_first_name">First Name</label>
				<input type="text" name="data[User][first_name]" id="in_first_name" value="<?php echo $data['User']['first_name'];?>">
			</div>

			<div class="form_pair">
				<label for="in_last_name">Last Name</label>
				<input type="text" name="data[User][last_name]" id="in_last_name" value="<?php echo $data['User']['last_name'];?>">
			</div>

			<div class="clear"> </div>

			<!-- [User][password] -->
			<div class="form_pair">
				<label for="in_Password">Password</label>
				<input type="password" name="data[User][password]" id="in_Password" value="">
			</div>

			<div class="form_pair" id="pass_check">
				<label for="in_Password_confirm">Password Confirm</label>
				<input type="password" name="data[User][pass_confirm]" id="in_Password_confirm" value="" class="jaja">
			</div>
			<div class="clear"> </div>
		</form>

		<div class="right notice" style="width:29%;">
			<a href="http://pressbackup.com/pricing" target="_blank"><?php _e('Check out our paid memberships!', 'pressbackup'); ?></a><br><br>
			<ul class ="description ">
				<li><b><?php _e('Full Backup', 'pressbackup'); ?></b><br/><small><?php _e('Includes files, database, plugins and themes.', 'pressbackup'); ?></small></li>
				<li><b><?php _e('Backups From 150Mb up to 1GB', 'pressbackup'); ?></b><br/><small><?php _e('This restriction applies to each backup.', 'pressbackup'); ?></small></li>
				<li><b><?php _e('Store From 14 up to 30 snapshots', 'pressbackup'); ?></b><br/><small><?php _e('A minimum of two weeks of backups per site!', 'pressbackup'); ?></small></li>
				<li><b><?php _e('More Redundance', 'pressbackup'); ?></b><br/><small><?php _e('Use up to 4 storage services at once for your backups.', 'pressbackup'); ?></small></li>
			</ul>
		</div>
		<div class="clear"> </div>
	</div>

	<hr class="separator">
	<a href="#" class="button" id="submit" <?php if(isset($check_cron)){?> disabled <?php }?>><?php _e('Create a free account', 'pressbackup'); ?></a>&nbsp;&nbsp;
	<a href="<?php echo $this->router(array('function'=> 'registerSite'));?>" class="" <?php if(isset($check_cron)){?> disabled <?php }?>><?php _e('Already have an account, Register this site please', 'pressbackup'); ?></a>

	<script type="text/javascript">
		var reload_url_fail = '<?php echo str_replace('&amp;', '&', $this->router(array('controller'=>'settings', 'function'=> 'wizardSetCronTaskStatusFail'))); ?>';

		jQuery('.button').click(function(){
			var disabled = jQuery(this).attr('disabled');
			if (disabled){	return false; }

			if(jQuery(this).attr('id') == 'submit'){
				if ((jQuery.trim(jQuery('#in_Password_confirm').val()) =='') || jQuery('#in_Password_confirm').val() != jQuery('#in_Password').val()){
					alert('Passwords does not match')
					return false;
				}
				jQuery('#create_form').submit();
			}
			return true;
		});

		jQuery('#in_Password_confirm').blur(function(){
			if ((jQuery.trim(jQuery(this).val()) =='') || jQuery(this).val() != jQuery('#in_Password').val()){
				jQuery(this).css( 'background', '#FDDFE5');
			}else{
				jQuery(this).css( 'background', '#fff');
			}
		});
	</script>
