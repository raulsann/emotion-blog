<?php

/*
	WordPress Framework, activation  v0.1
	developer: Perecedero (Ivan Lansky) @perecedero
*/


	function pressbackup_on_activation () {
		global $pressbackup;

		//options
		$settings =  array (
			'version' => '2.5.1',
			'configured' => false,
			'membership' => 'free',
			'compatibility' => array(
				'background' => 10,
				'zip'=> 10,
			),
			'backup' => array(
				'db' => array('time' => null, 'last_date' => null),
				'themes' => array('time' => null, 'last_date' => null),
				'plugins' => array('time' => null, 'last_date' => null),
				'uploads' => array('time' => null, 'last_date' => null),
				'copies' => array('pressbackup' => 7, 'others' => 7),
			),
			'Service' => array(
				'pressbackup' => array(
					'enabled' => false,
					'credential' => '',
				),
				'dropbox' => array(
					'enabled' => false,
					'credential' => '',
				),
				's3' => array(
					'enabled' => false,
					'credential' => '',
					'bucket_name' => '',
					'region' => false,
				),
				'local' => array(
					'enabled' => false,
					'path' => '',
				),
			),
		);

		$settings['show.updated.msg'] = true;

		update_option('pressbackup.preferences',$settings);
		update_option('pressbackup.wizard.cronTaskStatus', 'no checked');
		update_option('pressbackup.firstRun', true);
		//update_option('pressbackup.backupFullSize', 0); puesto solo para tener registro de que existe


		$pressbackup->import('ServicePressbackup.php');
		$pbsrv = new ServicePressbackupPBLib();
		$pbsrv->pingInstalled();
	}

	function pressbackup_on_deactivation () {
		global $pressbackup;

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$sch->remove('all');

		delete_option('pressbackup.preferences');
		delete_option('pressbackup.wizard.cronTaskStatus');
		delete_option('pressbackup.firstRun');
		delete_option('pressbackup.backupFullSize');

		$pressbackup->import('ServicePressbackup.php');
		$pbsrv = new ServicePressbackupPBLib();
		$pbsrv->pingRemoved();
	}
?>
