<?php
	global $pressbackup;
	$pressbackup->import('download.php');
	smartReadFile($file, $name,"application/zip");
?>
