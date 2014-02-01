<?php
 /**
 * Extra Controller for Pressbackup.
 *
 * This Class provide misc function that are called automaticaly at some point
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link			http://pressbackup.com
 * @package		controlers
 * @subpackage	controlers.extra
 * @since			0.5
 * @license		GPL v2 License
 */

class PressbackupExtra {

	/**
	 * Check if the scheluded taks is active and active otherwise
	 * Also check for missing preferences added between versons
	 * Note: this function its called at the init of the plugin
	*/
	public function checks ()
	{
		//tools
		global $pressbackup;
		$settings= get_option('pressbackup.preferences');
		$settings_old= get_option('pressbackup_preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		require_once ABSPATH.'wp-admin/includes/plugin.php';
		$plugin_info = get_plugin_data($pressbackup->status['plugin.fullpath'] . DS . $pressbackup->status['plugin.mainfile']);

		if($settings_old){
			$sotored_version = '1.5.1';
		}
		elseif(!isset($settings['version'])){
			$sotored_version = '1.6';
		}
		else{
			$sotored_version = $settings['version'];
		}


		if ( $plugin_info['Version'] > $sotored_version ){

			//must change settings structure
			if($sotored_version < '2.0'){
				$settings =  array (
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

				delete_option('pressbackup_preferences');
				delete_option('pressbackup.preferences');
			}

			//send info about the configuration and the selected services
			if($sotored_version  < '2.3') {

				$srv = $misc->serviceObject('pressbackup');
				$services = $misc->currentService();
				$enabled_services = array(); foreach ($services as $s){ $enabled_services[] = $s['id'];  }
				$srv->pingConfigured($enabled_services, $misc->timezoneString(), $plugin_info['Version']);
			}

			//error introduced on 2.4 or 2.3
			if(!$settings['backup']['copies']) {
				$settings['backup']['copies'] = array('pressbackup' => 7, 'others' => 7);
			}

			$settings['previous.version'] = $sotored_version;
			$settings['version'] = $plugin_info['Version'];

			update_option('pressbackup.preferences',$settings);
		}

		//set the type of service use to get/show backups
		$enabled_services = $misc->currentService();

		//fix to missing schedule
		if ($enabled_services && !$ns=wp_next_scheduled('pressbackup.cron.doBackupAndSaveIt')){
			$pressbackup->import('Scheduler.php');
			$sch = new SchedulerPBLib();
			$sch->add();
		}

		return true;
	}

	/**
	 * Restore the permalinks
	 *
	 * called after a restore is done
	 * Note: this function its called at the admin init
	 */
	public function restoreHtaccess ()
	{
		//tools
		global $wp_rewrite;

		$settings= get_option('pressbackup.preferences');

		if(isset($settings['restore'])){
			$wp_rewrite->flush_rules();

			unset($settings['restore']);
			update_option('pressbackup.preferences',$settings);
		}
		return true;
	}

	public function addUpdateMsg()
	{
		global $pressbackup;
		$settings= get_option('pressbackup.preferences');

		//nothing to show
		if ( (isset($_GET['page']) && strpos($_GET['page'],  $pressbackup->config['prefix'] . '-settings') !== false) || !isset($settings['show.updated.msg']) || !$settings['show.updated.msg']){
			return true;
		}

		if(in_array($settings['previous.version'], array('1.5.1', '1.6'))){

			//do not show anything if the user already has a membership
			$removeLink = ' | '.$pressbackup->link('Remove this Message', array('menu_type'=>'menu', 'controller'=> 'settings', 'function'=> 'removeUpdateMsg'));
			$reconfigureLink = $pressbackup->link('reconfigure', array('menu_type'=>'menu', 'controller'=> 'settings', 'function'=> 'index'));
			echo '<div class="updated"><p>Pressbackup has changed, Please '.$reconfigureLink.' PressBackup to can continue using it. '.$removeLink.'</p></div>';
		}

		elseif($settings['version'] >= '2.4'){

			//do not show anything if the user already has a membership
			$removeLink = ' | '.$pressbackup->link('Remove this Message', array('menu_type'=>'menu', 'controller'=> 'settings', 'function'=> 'removeUpdateMsg'));
			$reconfigureLink = $pressbackup->link('<b>configure it</b>', array('menu_type'=>'menu', 'controller'=> 'settings', 'function'=> 'index'));
			echo '<div class="updated"><p><b>Thanks for installing PressBackup!</b>, Please '.$reconfigureLink.' and start backing up your site now. '.$removeLink.'</p></div>';
		}
	}

	public function addPrefixMsg()
	{
		global $pressbackup;
		$settings= get_option('pressbackup.preferences');

		if(!isset($settings['show.prefix.msg'])){
			return true;
		}

		$removeLink = ' | '.$pressbackup->link('Remove this Message', array('menu_type'=>'menu', 'controller'=> 'settings', 'function'=> 'removePrefixMsg'));
		echo '<div class="updated"><p>PressBackup: Please change the value of <b>$table_prefix</b> to <b>'.$settings['show.prefix.msg'].'</b> on your <b>wp_config.php</b> file to can use the restored Database. '.$removeLink.'</p></div>';
	}


	/**
	 * On pressup ping Event handler
	 */
	public function onPing ()
	{
		if (isset($_GET['PressUpPing'])){
			$this->sendDatabaseStatus();
		}
		elseif(isset($_GET['PBInfoPing'])){
			$this->sendPluginInfo();
		}
		else{
			return true;
		}
	}

	private function sendDatabaseStatus ()
	{
		//tomar los datos actuales
		$response =  array( 'status' => array('database' => $this->databaseStatus()));

		//clean output buffer
		ob_end_clean();

		//send json
		header('Status: 200 OK', true);
		header('HTTP/1.1 200 OK', true);
		header("Content-type: application/json", true);
		echo json_encode($response);
		exit;
	}

	private function sendPluginInfo()
	{
		$settings= get_option('pressbackup.preferences');

		$response=array(
			'site.title' => get_bloginfo('name'),
			'plugin.version' =>  $settings['version'],
			'plugin.status' => ($settings['configured'])?'configured':'installed',
		);

		//clean output buffer
		ob_end_clean();

		//send json
		header('Status: 200 OK', true);
		header('HTTP/1.1 200 OK', true);
		header("Content-type: application/json", true);
		echo json_encode($response);
		exit;
	}

	private function databaseStatus ()
	{
		global $wpdb;

		$method = 'Tables_in_'. DB_NAME;
		$database_tables = $wpdb->get_results( 'SHOW TABLES IN `'.DB_NAME.'`');

		if($wpdb->last_error){
			return array('status'=> 'Down', 'latency'=> 0 );
		}

		$tables = count($database_tables);
		$totalTime = 0;
		for($i = 0; $i < $tables; $i++) {

			$timeStart = microtime();
			$res = $wpdb->get_results( $sql = 'SELECT count(*) as cant FROM  `'.$database_tables[$i]->$method.'` as T');
			$timeFinish = microtime();

			$totalTime += ($timeFinish - $timeStart);
		}

		if ($totalTime == 0) {
			return array('status'=> 'down', 'latency'=> 0 );
		}

		return array('status'=> 'up', 'latency'=> round( ($totalTime / $tables), 5 ) );
	}

}
?>
