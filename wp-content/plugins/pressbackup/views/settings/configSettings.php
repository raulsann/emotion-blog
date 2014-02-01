<?php
	$this->import('Msg.php');
	$msg = new MsgPBLib();
?>

<?php echo $this->css("framepress.default.css");?>
<?php echo $this->css("pb.css");?>

<?php $bs = $settings['backup'];?>

<div class="tab " ><?php echo $this->link(__('Dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'));?></div>
<div class="tab " ><?php echo $this->link(__('Service','pressbackup'), array('controller'=>'settings', 'function'=>'configService'));?></div>
<div class="tab tabactive" ><?php echo $this->link(__('Settings','pressbackup'), array('controller'=>'settings', 'function'=>'configSettings'));?></div>
<div class="tab " ><?php echo $this->link(__('Compatibility','pressbackup'), array('controller'=>'settings', 'function'=>'configCompatibility'));?></div>
<div class="tabclear" >&nbsp;</div>
<div class="tab_subnav"> </div>
<div class="tab_content">

	<form method="post" action="<?php echo $this->router(array('controller'=>'settings', 'function'=>'configSettingsSave'));?>">
		<h3><?php _e('Configuration Settings','pressbackup'); ?></h3>
		<p><?php _e('Here you can configure Backups properties','pressbackup'); ?></p>

		<?php echo $msg->show('error');?>

		<br/><h4><?php _e('How often do you want to backup?','pressbackup'); ?></h4>

		<table class="widefat" cellspacing="0">
			<tr class="alternate">
				<td class="row-title">&nbsp;</td>
				<td class="row-title"><?php _e('Database','pressbackup')?></td>
				<td class="row-title"><?php _e('Themes','pressbackup')?></td>
				<td class="row-title"><?php _e('Plugins','pressbackup')?></td>
				<td class="row-title"><?php _e('Uploads','pressbackup')?></td>
			</tr>
			<tr>
				<th><?php _e('Never','pressbackup')?></th>
				<th><input type="radio" name="data[backup][db][time]" value ="0" <?php if ($bs['db']['time']==0) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][themes][time]" value ="0" <?php if ($bs['themes']['time']==0) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][plugins][time]" value ="0" <?php if ($bs['plugins']['time']==0){ echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][uploads][time]" value ="0" <?php if ($bs['uploads']['time']==0){ echo 'checked';}?>></th>
			</tr>
			<?php if ($time <= 12) {?>
			<tr class="alternate" >
				<th><?php _e('Every 12 hours','pressbackup')?></th>
				<th><input type="radio" name="data[backup][db][time]" value ="12" <?php if ($bs['db']['time']==12) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][themes][time]" value ="12" <?php if ($bs['themes']['time']==12) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][plugins][time]" value ="12" <?php if ($bs['plugins']['time']==12){ echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][uploads][time]" value ="12" <?php if ($bs['uploads']['time']==12){ echo 'checked';}?>></th>
			</tr>
			<?php }?>
			<tr class="<?php if ($time > 12) {?>alternate<?php }?>" >
				<th><?php _e('Daily','pressbackup')?></th>
				<th><input type="radio" name="data[backup][db][time]" value ="24" <?php if ($bs['db']['time']==24) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][themes][time]" value ="24" <?php if ($bs['themes']['time']==24) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][plugins][time]" value ="24" <?php if ($bs['plugins']['time']==24){ echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][uploads][time]" value ="24" <?php if ($bs['uploads']['time']==24){ echo 'checked';}?>></th>
			</tr>
			<tr class="<?php echo ($time > 12)?'':'alternate';?>">
				<th><?php _e('Weekly','pressbackup')?></th>
				<th><input type="radio" name="data[backup][db][time]" value ="168" <?php if ($bs['db']['time']==168) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][themes][time]" value ="168" <?php if ($bs['themes']['time']==168) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][plugins][time]" value ="168" <?php if ($bs['plugins']['time']==168){ echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][uploads][time]" value ="168" <?php if ($bs['uploads']['time']==168){ echo 'checked';}?>></th>
			</tr>
			<tr class="<?php echo ($time > 12)?'alternate':'';?>">
				<th><?php _e('Monthly','pressbackup')?></th>
				<th><input type="radio" name="data[backup][db][time]" value ="720" <?php if ($bs['db']['time']==720) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][themes][time]" value ="720" <?php if ($bs['themes']['time']==720) { echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][plugins][time]" value ="720" <?php if ($bs['plugins']['time']==720){ echo 'checked';}?>></th>
				<th><input type="radio" name="data[backup][uploads][time]" value ="720" <?php if ($bs['uploads']['time']==720){ echo 'checked';}?>></th>
			</tr>
		</table>

		<hr class="separator"/>

		<input class="button " type="submit" value="<?php _e('Save','pressbackup'); ?>"> <?php _e('or','pressbackup'); ?>
		<?php echo $this->link(__('Go back to dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'), array('class'=>'pb_action2'));?>

	</form>
</div>


<div class="tab_content continue">
</div>
