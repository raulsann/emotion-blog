<?php
	$this->import('Msg.php');
	$msg = new MsgPBLib();
?>

<?php echo $this->css("framepress.default.css"); ?>
<?php echo $this->css("pb.css"); ?>

<div class="tabclear">&nbsp;</div>
<div class="tab_subnav"><h2><?php _e('Welcome!', 'pressbackup'); ?></h2></div>

<div class="tab_content">
	<?php global $pressbackup;?>

	<?php echo $msg->show('warning');?>
	<?php echo $msg->show('error');?>

	<?php require_once($this->path['view'] . '/settings/' . $show_page .'.php' );?>

</div>

<div class="tab_content continue">
	<h4><?php _e('Signup for PressBackup News to get updates','pressbackup'); ?></h4>
	<form action="http://infinimedia.createsend.com/t/y/s/otyxj/" method="post" id="subForm">
		<div>
			<input type="text" name="cm-otyxj-otyxj" id="otyxj-otyxj" class="longinput" value="<?php _e('Your email address here','pressbackup'); ?>" onfocus="if(jQuery(this).val() == '<?php _e('Your email address here','pressbackup'); ?>'){ jQuery(this).val('') }" onblur="if(jQuery(this).val() == ''){ jQuery(this).val('<?php _e('Your email address here','pressbackup'); ?>') }" />&nbsp;&nbsp;
			<input class="button" type="submit" value="<?php _e('Subscribe','pressbackup'); ?>" />
		</div>
	</form>
</div>


