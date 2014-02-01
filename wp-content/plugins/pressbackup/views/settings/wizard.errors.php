	<div>
		<h3><?php _e('Sorry there are some incompatibilities present','pressbackup'); ?></h3>
		<?php foreach($error as $key => $value){?>
			<p style="font-size: 12px;">* <?php echo $value;?></p>
		<?php }?>
	</div>
