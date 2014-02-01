<?php
	/*
	Plugin Name: PressBackup
	Plugin URI: http://pressbackup.com
	Description: Easily backup your WordPress site and use our cloud storage service for free! or choose to save backups on DropBox, Amazon S3, or a server folder.
	Author: Infinimedia Inc.
	Version: 2.5.1
	Author URI: http://infinimedia.com/
	*/

	//init framework
	require_once( 'core/FPL.php' );
	global $FramePress;

	//Create a global instance of framepress, choose a unique name
	global $pressbackup;
	$pressbackup = new $FramePress(__FILE__, array(
		'prefix' => 'Pressbackup',
		'use.tmp' => true,
		'S3.bucketname' => 'pressbackups',
	));

	$pb_postfix = substr(md5(get_bloginfo('wpurl')), 0,5);
	$pressbackup->mergePaths(array(
		'PBKTMP' => $pressbackup->path['systmp'] . DS . 'pressback.' . $pb_postfix,
		'LOGTMP' => $pressbackup->path['systmp'] . DS . 'presslog.' . $pb_postfix,
	));

	//Admin pages to add
	$my_pages = array (
		'menu' => array (
			array (
				'page.title' => 'PressBackup',
				'menu.title' => 'PressBackup',
				'capability' => 'administrator',
				'controller' => 'main',
				'function' => 'index',
				'icon' => 'services/pressbackup16.png',
			),
		),
		'submenu' => array (
			array (
				'parent' => 'PressBackup',
				'page.title' => 'PressBackup',
				'menu.title' => 'Settings',
				'capability' => 'administrator',
				'controller' => 'settings',
				'function' => 'index',
			),
			array (
				'parent' => 'PressBackup',
				'page.title' => 'PressBackup',
				'menu.title' => 'Debug Log',
				'capability' => 'administrator',
				'controller' => 'main',
				'function' => 'reportInfo',
			),
		),
	);

	$my_actions = array (

		//save (for scheduled jobs)
		//called on WP doing cron
		array(
			'tag' => 'pressbackup.cron.doBackupAndSaveIt',
			'controller' => 'main',
			'function' => 'createBackupAndSaveIt',
		),

		//save and download (for backup now && compatibility soft)
		//called on main::backupStart
		array(
			'tag' => 'pressbackup.main.doBackupAndSaveIt',
			'controller' => 'main',
			'function' => 'createBackupAndSaveIt',
		),
		array(
			'tag' => 'pressbackup.main.doBackupAndDownloadIt',
			'controller' => 'main',
			'function' => 'createBackupAndDownloadIt',
		),

		//save and download ajax (for backup now && compatibility medium)
		//called on main::backupStart
		array(
			'tag' => 'pressbackup.ajax.doBackupAndSaveIt',
			'controller' => 'main',
			'function' => 'createBackupAndSaveIt',
			'is_ajax' => 'private'
		),
		array(
			'tag' => 'pressbackup.ajax.doBackupAndDownloadIt',
			'controller' => 'main',
			'function' => 'createBackupAndDownloadIt',
			'is_ajax' => 'private'
		),

		//save and download remote (for backup now && compatibility hard)
		//called on main::backupStart
		array(
			'tag' => 'pressbackup.remote.doBackupAndSaveIt',
			'controller' => 'main',
			'function' => 'createBackupAndSaveIt',
			'is_ajax' => 'public'
		),
		array(
			'tag' => 'pressbackup.remote.doBackupAndDownloadIt',
			'controller' => 'main',
			'function' => 'createBackupAndDownloadIt',
			'is_ajax' => 'public'
		),

		// check main|ajax|cron status
		//called via ajax on dash pages
		array(
			'tag' => 'pressbackup.main.chkCronBackupStatus',
			'controller' => 'main',
			'function' => 'cronBackupStatus',
			'is_ajax' => 'private',
		),

		// create test cron task (wizard check)
		//called on settigs::configInit and configInit page
		array(
			'tag' => 'pressbackup.wizard.doCronTask',
			'controller' => 'settings',
			'function' => 'wizardStartCronTask'
		),
		array(
			'tag' => 'pressbackup.wizard.chkCronTaskStatus',
			'controller' => 'settings',
			'function' => 'wizardChkCronTaskStatus',
			'is_ajax' => 'private',
		),

		//add post-upgrade admin message
		array(
			'tag' => 'admin_notices',
			'controller' => 'extra',
			'function' => 'addUpdateMsg',
		),
		array(
			'tag' => 'admin_notices',
			'controller' => 'extra',
			'function' => 'addPrefixMsg',
		),


		// check for incompatibilities with previous versions
		array(
			'tag' => 'init',
			'controller' => 'extra',
			'function' => 'checks',
		),

		// send status info to web
		array(
			'tag' => 'init',
			'controller' => 'extra',
			'function' => 'onPing',
		),

		//function called after a restore
		array(
			'tag' => 'admin_init',
			'controller' => 'extra',
			'function' => 'restoreHtaccess',
		),
	);

	$pressbackup->pages($my_pages);
	$pressbackup->actions($my_actions);

	if (!function_exists('ppr')){
		function ppr ($data){
			echo '<pre>';
			print_r($data);
			echo '</pre>';
		}
	}
