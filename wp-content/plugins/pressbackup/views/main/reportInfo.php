<?php
	global $pressbackup;
	$pressbackup->import('Misc.php');
	$misc = new MiscPBLib();
	date_default_timezone_set($misc->timezoneString());
?>

<div class="updated fade" style="width:700px;" >

	<div style="float:left; width: 325px">
		<br/>==WP Info==<br/>
		* url: <?php echo $info['WP']['url']."<br/>\n";?>
		* version: <?php echo $info['WP']['version']."<br/>\n";?>

		<br/>==Host Info==<br/>
		* Server : <?php echo $info['Host']['type']."<br/>\n";?>
		* Port : <?php echo $info['Host']['port']."<br/>\n";?>
		* SAPI : <?php echo $info['Host']['sapi']."<br/>\n";?>
		* MEM Max : <?php echo $info['Host']['mem_max']."<br/>\n";?>
		* MEM Used : <?php echo $misc->getByteSize($info['Host']['mem_used'], 'mb')." MB<br/>\n";?>
		* TMP Dir: <?php  echo $info['Host']['tmp_dir']."<br/>\n";?>
		* TMP Free: <?php echo $misc->getByteSize($info['Host']['tmp_free'], 'mb')." MB<br/>\n";?>

		<br/>==Debug info==<br/>
		<p>
			* files container : - <br/>
			<?php  foreach($files as $file){?>
				<b><?php echo $file['name'];?></b><br>
				<?php echo $file['size'];?> -
				<?php echo $this->link('download', array('controller'=>'main', 'function'=>'backupDownload', $file['download']));?>
				<br>
			<?php }?>
		</p>
		<br/>
		<p>
			* Error Log : - <br/>
			<?php echo $logs['errors'];?>
		</p>
		<br/>
		<p>
			* Creation Log : - <br/>
			<?php echo $logs['creation'];?>
		</p>
		<br/>
		<p>
			* Send Log : - <br/>
			<?php echo $logs['save'];?>
		</p>
		<br/>
		<p>
			now: <?php echo  date('< d M y - H:i:s >'); ?><br/>
			cron: <?php echo  date('< d M y - H:i:s >', wp_next_scheduled('pressbackup.cron.doBackupAndSaveIt') ); ?><br/>
			down: <?php echo  date('< d M y - H:i:s >', wp_next_scheduled('pressbackup.main.doBackupAndDownloadIt') ); ?><br/>
			downA: <?php echo  date('< d M y - H:i:s >', wp_next_scheduled('pressbackup.ajax.doBackupAndDownloadIt') ); ?><br/>
			save: <?php echo  date('< d M y - H:i:s >', wp_next_scheduled('pressbackup.main.doBackupAndSaveIt') ); ?><br/>
			saveA: <?php echo  date('< d M y - H:i:s >', wp_next_scheduled('pressbackup.ajax.doBackupAndSaveIt') ); ?><br/>
		</p>

	</div>

	<div style="float:left; width: 325px; margin-left: 50px;">
		<br/>==Browser==<br/>
		* version: <?php echo $info['User']['browser']."<br/>\n";?>

		<br/>==Plugin info==<br/>
		* version: <?php  echo $info['Plugin']['version']; echo "<br/>\n";?>
		* Service: <?php  echo $info['Plugin']['service']; echo "<br/>\n";?>

		<br/>=Host modules=<br/>
		<?php for ($i=0; $i< count($info['Host']['modules']); $i++) { echo '&nbsp;&nbsp;  * '.$info['Host']['modules'][$i]; if ($i % 4 == 0) {echo "<br/>\n";} } echo "<br/>\n";?>

			<br/>=htaccess=<br/>
		<?php echo '<pre>' . file_get_contents(ABSPATH . '.htaccess' ) . '</pre>';?>

	</div>

	<div style="clear:both;"></div>
</div>
<?php echo $this->link(__('Clean TMP folders','pressbackup'), array('controller'=>'main', 'function'=>'reportInfo', false, true, false), array('class'=>'button'));?>
<?php echo $this->link(__('Remove scheduled jobs','pressbackup'), array('controller'=>'main', 'function'=>'reportInfo', true, false, false), array('class'=>'button'));?>
<?php echo $this->link(__('Clean Log files','pressbackup'), array('controller'=>'main', 'function'=>'reportInfo', false, false, true), array('class'=>'button'));?>
<?php echo $this->link(__('Back to dashboard','pressbackup'), array('controller'=>'main', 'function'=>'dashboard'), array('class'=>'button'));?>
