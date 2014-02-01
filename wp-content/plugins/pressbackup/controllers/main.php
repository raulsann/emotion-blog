<?php
 /**
 * Principal Controller for Pressbackup.
 *
 * This Class provide a interface to display and manage backups
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link		http://pressbackup.com
 * @package		controlers
 * @subpackage	controlers.principal
 * @since		0.1
 * @license		GPL v2 License
 */

class PressbackupMain
{
	public $layout = 'principal';

	public function __construct ()
	{
	}


	/**
	 * Redirect the user to de correct page
	 * If the user has not configured the plugin
	 * this function redirect directly to the config init page
	 *
	 * Note: this function is automaticaly called
	 * by pressing on tool menu link
	*/
	public function index()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		if ($settings['configured']) {

			$pressbackup->redirect(array('function' => 'dashboard'));

		} else {

			$pressbackup->redirect(array('controller' => 'settings', 'function' => 'wizardInit'));
		}
	}

	//------------------------------------------------------------------------------------

	/**
	 * Save the preferences to display in dash page (pressbackup's main page)
	 * Set the info to see what to see on backup tables, from, age number, etc
	 *
	 * @from string: what tab of backups see
	 * @page integer: page of backup to see
	 * @remove_schedule bool: remove all scheduled jobs befor render the page (clean start)
	 */
	public function dashOptions ($from = null, $service = null, $page = null, $remove_schedule = false )
	{
		global $pressbackup;

		if( $page ){
			$pressbackup->sessionWrite('dash.page', $page);
		}

		if( $from ) {

			$pressbackup->import('Misc.php');
			$misc = new MiscPBLib();
			$srv = $misc->serviceObject('pressbackup');

			$settings = get_option('pressbackup.preferences');
			if($settings['membership']=='developer'){

				if ( $from == 'all_sites' && ( !$pressbackup->sessionCheck('dev.auth') && !$srv->authToken($_GET['token']) )){
					$from = 'this_site';
					$pressbackup->import('Msg.php');
					$msg = new MsgPBLib();
					$msg->set('error', __('Authorization failed', 'pressbackup') );
				} elseif($from == 'all_sites') {
					$pressbackup->sessionWrite('dev.auth', true);
				}
			}

			$saved_from = ($saved_from = $pressbackup->sessionRead('dash.from'))?$saved_from:'this_site';
			$pressbackup->sessionWrite('dash.from', $from);

			// if tab is different of the previos saved, show the first page
			if( $saved_from != $from ){
				$pressbackup->sessionWrite('dash.page', 1);
			}
		}

		if( $service ) {
			$pressbackup->import('Misc.php');
			$misc = new MiscPBLib();

			$enabled_services = $misc->currentService();
			$saved_service = ($saved_service = $pressbackup->sessionRead('dash.service'))?$saved_service:$enabled_services[0]['id'];

			$pressbackup->sessionWrite('dash.service', $service);

			// if tab is different of the previos saved, show the first page
			if( $saved_service != $service ){
				$pressbackup->sessionWrite('dash.page', 1);
			}
		}

		if( $remove_schedule ){
			$pressbackup->import('Scheduler.php');
			$sch = new SchedulerPBLib();

			$sch->remove('all');
		}

		$pressbackup->redirect(array('function' => 'dashboard'));
	}


	/**
	 * Renders dashboard page (the main page)
	 * This page has the interface to manage backups
	 */
	public function dashboard ()
	{
		global $pressbackup;

		$settings= get_option('pressbackup.preferences');

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Msg.php');
		$msg = new MsgPBLib();


		$pbsrv = $misc->serviceObject('pressbackup');


		//get Service settings
		$enabled_services = $misc->currentService();

		//check what backup list display
		$from = ($saved_from = $pressbackup->sessionRead('dash.from'))?$saved_from:'this_site';

		//check what storage service display on backup list
		// get service interface object
		$service = ($saved_service = $pressbackup->sessionRead('dash.service'))?$saved_service:$enabled_services[0]['id'];
		$srv = $misc->serviceObject($service);

		//load ajax to check background backups status?
		//loaded on "cron"  or  "backup now" events
		$reload = ( $misc->getLogFile('create.log') == 'start' ||  $misc->getLogFile('sent.log')  == 'start' )?'dashboard':false;

		if($saved_reload = $pressbackup->sessionRead('dash.reload')){
			$reload = $saved_reload;
			$pressbackup->sessionDelete('dash.reload');
		}

		//retrive backups from Pro
		if( $service == 'pressbackup' ){
			$backup_list = $srv->getFilesList();
			if($srv->response['code'] != '200'){
				$res=@json_decode($srv->response['body'], true);
				if($res['code'] == '1003'){
					$res['msg'] .= ' <a href="'.$pressbackup->router(array('controller' => 'settings', 'function'=> 'registerSite')).'" >Register now</a>';
				}
				$msg->set('error',__($res['msg'],'pressbackup'));
			}
		}

		//retrive backups from DropBox
		elseif( $service == 'dropbox' ){
			$backup_list = $srv->getFilesList();
		}

		//retrive backups from Amazon S3
		elseif( $service == 's3' ){
			$serviceInfo =  $misc->service('s3');
			$backup_list = $srv->getBucket($serviceInfo['bucket_name']);
		}

		//retrive backups from Local
		elseif( $service == 'local' ){
			$backup_list = $srv->getFilesList();
		}

		//fix for empty response
		if(!$backup_list){ $backup_list = array(); }

		//init paginator
		$backup_paginator = array();

		if ($backup_list) {

			//get a normalized list and sorted by date
			$aux_list = @$misc->msort($service, $backup_list);
			$aux_list = $misc->filterFiles ($from, $aux_list);

			$pagination_size = 5;
			$page = ($page_saved = $pressbackup->sessionRead('dash.page'))?$page_saved:1;

			//fix page value when it is incorrect ( a page that not exists, like page > pages )
			if(!array_slice($aux_list, (($page -1 )*$pagination_size), $pagination_size, true) && $page > 1){
				$page--;
				$pressbackup->sessionWrite('dash.page', $page);
			}

			//build paginator
			$paginator['page'] = $page ;
			$paginator['total']  = count($aux_list);
			$paginator['pages'] = ceil ($paginator['total'] /$pagination_size);
			$paginator['ini']  = (($page -1 )*$pagination_size);
			$paginator['fin']  = (($page*$pagination_size) -1);
			$paginator['func_path']  = array('controller'=>'main', 'function'=>'dashOptions', $from, false);
			$paginator['pagination'] = $pagination_size;

			//set paginator to view
			$backup_list = array_slice($aux_list, $paginator['ini'], $pagination_size, true);
			$backup_paginator =  $paginator;
		}

		//inform about the first time backup
		if(get_option('pressbackup.firstRun') == true ) {
			$msg->set('info', __('We are processing a first backup, if you do not see a progress bar you can refresh the page', 'pressbackup') );
			update_option('pressbackup.firstRun', false);
		}

		//check if the first backup was done and show an upgrade message
		$first_backup_promo = array();
		if ($settings['membership'] == 'free' && $saved_size = $pressbackup->sessionRead('saved', true) && $pressbackup->sessionCheck('first.backup')){

			$first_backup_promo = $pbsrv->getPromotion($saved_size, 'first_backup');

			$pressbackup->sessionDelete('first.backup');
			$pressbackup->sessionDelete('saved');
		}

		//set messages
		elseif($pressbackup->sessionCheck('general_msg') || $pressbackup->sessionCheck('general_msg', true)){
			$msg->set('info', $pressbackup->sessionRead('general_msg'));
			$msg->set('info', $pressbackup->sessionRead('general_msg', true));
			$pressbackup->sessionDelete('general_msg');
			$pressbackup->sessionDelete('general_msg', true);
		}

		//set errors
		elseif($pressbackup->sessionCheck('error_msg') || $pressbackup->sessionCheck('error_msg', true)){
			$msg->set('error', $pressbackup->sessionRead('error_msg'));
			$msg->set('error', $pressbackup->sessionRead('error_msg', true));
			$pressbackup->sessionDelete('error_msg');
			$pressbackup->sessionDelete('error_msg', true);
		}


		//info to promo on dashboard
		$dash_promo = $pbsrv->getPromotion(get_option('pressbackup.backupFullSize'), 'dash');

		$pressbackup->viewSet('settings', $settings);

		$pressbackup->viewSet('from', $from);
		$pressbackup->viewSet('service', $service);
		$pressbackup->viewSet('reload', $reload);

		$pressbackup->viewSet('backup_type', $sch->activatedTasks($settings));
		$pressbackup->viewSet('backup_list', $backup_list);
		$pressbackup->viewSet('backup_paginator', $backup_paginator);

		$pressbackup->viewSet('timezone_string', $misc->timezoneString());
		$pressbackup->viewSet('next_scheduled_job', wp_next_scheduled('pressbackup.cron.doBackupAndSaveIt'));
		$pressbackup->viewSet('first_backup_promo', $first_backup_promo);
		$pressbackup->viewSet('dash_promo', $dash_promo);
	}


	/**
	 * Shows upload form for restore a backup
	 */
	public function uploadAndRestoreBackup ()
	{
		global $pressbackup;

		$settings= get_option('pressbackup.preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Msg.php');
		$msg = new MsgPBLib();

		//set error from previus upload
		if($pressbackup->sessionCheck('error_msg')){
			$msg->set('error', $pressbackup->sessionRead('error_msg'));
			$pressbackup->sessionDelete('error_msg');
		}

		//check for folder permissions
		$permissions = array();
		$disable = false;
		$msgs = false;

		$dir = WP_CONTENT_DIR . DS;
		if( !@is_writable($dir) ) { $permissions[] ='wp_content'; }
		if( !@is_writable($dir . 'themes' . DS ) ) { $permissions[] ='themes'; }
		if( !@is_writable($dir . 'plugins' . DS ) ) { $permissions[] ='plugins'; }
		if( @file_exists($dir . 'uploads' ) && !@is_writable($dir . 'uploads' . DS ) ) { $permissions[] ='uploads'; }


		if($permissions) {
			$denied_perms="<b>".join('</b>, <b>', $permissions)."</b>";
			$msgs = sprintf(__('Permissions denied to write on %1$s folder/s','pressbackup'),$denied_perms);
			$msgs .= "<br>";
			$msgs .= __('Pleace change the permissions of those folders to 777 (read-write for all)','pressbackup');
			$msgs .= "<br><br> ";
			$msgs .= __('At the moment only the database can be restored','pressbackup');
			$msg->set('error', $msgs);
			$disable = false;
		}

		$pressbackup->viewSet('upload_size', $misc->getByteSize($misc->uploadMaxFilesize(),'mb'));
		$pressbackup->viewSet('settings', $settings);
		$pressbackup->viewSet('disable_ulpoad', $disable);
	}

	//------------------------------------------------------------------------------------

	/**
	 * Start a manual backup
	 * create a schedule action (background proccess)
	 *
	 * @reloadPage string: specify what type of manual backup we are doing
	 * @to_include string: ignore stored setings and backup what this var says
	 */

	public function backupStart ($reloadPage ='backupDownload', $to_include=null)
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$pressbackup->import('SimpleCurl.php');
		$curl = new SimpleCurlPBLib();

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$sch->remove('pressbackup.main.doBackupAndSaveIt');
		$sch->remove('pressbackup.main.doBackupAndDownloadIt');

		//clean log && tmp dir
		$misc->cleanTmpFordes();

		if($to_include){
			$pressbackup->sessionWrite('backup.type',$to_include,true);
		}

		$auxCompBack = $settings['compatibility']['background'];

		//background creation type: SOFT
		if ($auxCompBack == 10 && $reloadPage == "backupDownload") {
			$sch->add('+4 seconds', 'pressbackup.main.doBackupAndDownloadIt');
		}

		elseif ($auxCompBack == 10 && $reloadPage == "dashboard") {
			$sch->add('+4 seconds', 'pressbackup.main.doBackupAndSaveIt');
		}

		//background creation type: MIDIUM
		if ($auxCompBack == 20 && $reloadPage == "backupDownload") {
			$curl->call(array(
				'url' => get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressbackup.ajax.doBackupAndDownloadIt',
				'cookie' => $_COOKIE,
				'timeout' => 4,
			));
		}

		elseif ($auxCompBack == 20 && $reloadPage == "dashboard") {
			$curl->call(array(
				'url' => get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressbackup.ajax.doBackupAndSaveIt',
				'cookie' => $_COOKIE,
				'timeout' => 4,
			));
		}

		//background creation type: HARD
		elseif ($auxCompBack == 30) {

			if ($reloadPage == "backupDownload") {
				$action =  'pressbackup.remote.doBackupAndDownloadIt';
			}
			elseif ($reloadPage == "dashboard") {
				$action = 'pressbackup.remote.doBackupAndSaveIt';
			}

			$srv = $misc->serviceObject('pressbackup');
			$srv->pingStart($action);
		}

		$pressbackup->sessionWrite('dash.reload', $reloadPage);
		$pressbackup->redirect(array('function' => 'dashboard'));
	}

	/**
	 * Create and send a backup to a storage service (S3, PPro, etc )
	 * this is the background proccess called from
	 * backup start or via scheduled task (cron job)
	 */
	public function createBackupAndSaveIt()
	{
		//try to cut the conection
		@ignore_user_abort(true);
		@set_time_limit(0);
		@ob_end_clean();
		@ob_start();
		header('Status: 204 No Content', true);
		header('HTTP/1.1 204 No Content', true);
		header('Content-type: text/html', true);
		header('Content-Length: 0', true);
		header('Connection: close', true);
		@ob_end_flush();
		@ob_flush();
		@flush();
		if(defined('STDIN')){ @fclose(STDIN); }
		if(defined('STDOUT')){ @fclose(STDOUT); }
		if(defined('STDERR')){ @fclose(STDERR); }

		global $pressbackup;

		if ($_SERVER['HTTP_USER_AGENT'] == 'Perecedero/Misc/SimpleCurl/PHP' && isset($_POST['type']) && preg_match('/^[1357]+(,)?[1357]?(,)?[1357]?(,)?[1357]?$/', $_POST['type'])) {
			$pressbackup->sessionWrite('backup.type',$_POST['type'],true);
		}

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$statusA = $misc->getLogFile('create.log');
		$statusB = $misc->getLogFile('sent.log');

		//salir si esta esta creando (statusA) y el log no tiene mas de 12 horas
		if( !$statusB && $statusA && !in_array($statusA, array('finish', 'fail')) ){
			if ($misc->mtimeLogFile('create.log') > strtotime('now - 12 hours')){
				return true;
			}
		}

		//salir si esta esta enviando (statusB) y el log no tiene mas de 12 horas
		if( $statusB && !in_array($statusB, array('finish', 'fail'))){
			if ($misc->mtimeLogFile('sent.log') > strtotime('now - 12 hours')){
				return true;
			}
		}

		$settings= get_option('pressbackup.preferences');

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$sch->remove('pressbackup.main.doBackupAndSaveIt');
		$sch->remove('pressbackup.cron.doBackupAndSaveIt');

		$customBackup = $pressbackup->sessionCheck('backup.type',true);

		//set backup type on "cron" event
		if ( !$customBackup ){
			$tasksDone = $settings['backup']['type'] = $sch->tasksToRunNow($settings);
			update_option('pressbackup.preferences', $settings);
		}

		//set backup type on "backup now"  or  similar event
		if($customBackup){
			$settings['backup']['type'] = $pressbackup->sessionRead('backup.type',true);
			update_option('pressbackup.preferences', $settings);
		}


		//make and save backup
		set_error_handler(array($this, 'creation_eh'));
		register_shutdown_function(array($this, 'shutdown') );

		if($file = $bm->create()) {
			$bm->save($file);
		}

		restore_error_handler();

		//delete files from tmp folder
		$misc->perpareFolder($pressbackup->path['PBKTMP']);

		unset($settings['backup']['type']);
		$pressbackup->sessionDelete('backup.type',true);
		update_option('pressbackup.preferences', $settings);

		//renew cron
		$sch->add();

		return true;
	}

	function shutdown()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		if(!is_null($e = error_get_last())){
			$misc->writeLogFile('create.fail', $misc->getLogFile('create.fail') . "<br> \n" . var_export($e, true) );
		}
		else {
			$misc->writeLogFile('create.fail', $misc->getLogFile('create.fail') . "<br> \n" . 'shutdown correctly' );
		}
	}

	/**
	 * Create a backup to downloading it latter
	 * this is the background proccess called from
	 * backup start. The ajax checker tell de browser when
	 * this procces finish and redirect the user to donwload backup
	 */
	public function createBackupAndDownloadIt ()
	{
		//try to cut the conection
		@ignore_user_abort(true);
		@set_time_limit(0);
		@ob_end_clean();
		@ob_start();
		header('Status: 204 No Content', true);
		header('HTTP/1.1 204 No Content', true);
		header('Content-type: text/html', true);
		header('Content-Length: 0', true);
		header('Connection: close', true);
		@ob_end_flush();
		@ob_flush();
		@flush();
		if(defined('STDIN')){ @fclose(STDIN); }
		if(defined('STDOUT')){ @fclose(STDOUT); }
		if(defined('STDERR')){ @fclose(STDERR); }

		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$statusA = $misc->getLogFile('create.log');
		$statusB = $misc->getLogFile('sent.log');

		//si no esta enviando (statusB) , y  esta creando (statusA)
		if( !$statusB && $statusA && !in_array($statusA, array('finish', 'fail')) ){
			return true;
		}

		if( $statusB && !in_array($statusB, array('finish', 'fail'))){
			return true;
		}

		$settings= get_option('pressbackup.preferences');

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$sch->remove('pressbackup.main.doBackupAndDownloadIt');

		$customBackup = $pressbackup->sessionCheck('backup.type',true);

		//set backup type on "backup now" otherwise it will backup the predefined settings
		//set backup type on "cron" event
		if ( !$customBackup ){
			$tasksDone = $settings['backup']['type'] = $sch->tasksToRunNow($settings);
			update_option('pressbackup.preferences', $settings);
		}

		//set backup type on "backup now"  or  similar event
		if($customBackup){
			$settings['backup']['type'] = $pressbackup->sessionRead('backup.type',true);
			update_option('pressbackup.preferences', $settings);
		}

		//create the file
		set_error_handler(array($this, 'creation_eh'));
		$zip_file_created = $bm->create();
		restore_error_handler();

		if($zip_file_created){
			$misc->writeLogFile('create.return', $zip_file_created);
		}

		//restore activated types on backup now
		unset($settings['backup']['type']);
		$pressbackup->sessionDelete('backup.type',true);
		update_option('pressbackup.preferences', $settings);

		exit;
	}


	/**
	 * Stop backup creation/send process
	 * This function delete TMP folder content. This action produce
	 * an error and an abort on the background process.
	 */
	public function backupCancel ()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$misc->cleanTmpFordes();

		$pressbackup->redirect(array('function' => 'dashboard'));
	}

	//------------------------------------------------------------------------------------

	/**
	 * Call to restore if uploaded backups its valid
	 * or save the errors and display upload form again
	 */
	public function backupRestoreFromUpload ()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();


		//return if exists upload errors
		if(!$misc->checkBackupUpload() || !$misc->checkBackupIntegrity($_FILES['backup']['tmp_name'])){
			$pressbackup->redirect(array('controller'=>'main', 'function'=>'uploadAndRestoreBackup'));
		}

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		//restore from uploaded file
		$bm->restore(array('tmp_name'=>$_FILES['backup']['tmp_name']));

		//delete files from tmp folder
		$misc->perpareFolder($pressbackup->path['PBKTMP']);

		$pressbackup->redirect(array('function' => 'dashboard'));exit;
	}

	/**
	 * Restore a backup previously stored by backup_upload
	 * or  S3/PPro get function ( from backup function lib)
	 */
	function backupRestoreFromService ($name = null)
	{
		global $pressbackup;

		//check for folder permissions on wp-content folder and subfollders
		$permissions = array ();
		$dir = WP_CONTENT_DIR . DS;

		if( !@is_writable($dir) ) { $permissions[] ='wp_content'; }
		if( !@is_writable($dir . 'themes' . DS ) ) { $permissions[] ='themes'; }
		if( !@is_writable($dir . 'plugins' . DS ) ) { $permissions[] ='plugins'; }
		if( @file_exists($dir . 'uploads' ) && !@is_writable($dir . 'uploads' . DS ) ) { $permissions[] ='uploads'; }

		if($permissions) {
			$denied_perms="<b>".join('</b>, <b>', $permissions)."</b>";
			$msg = sprintf(__('Permissions denied to write on %1$s folder/s','pressbackup'),$denied_perms);
			$msg .= "<br/> ";
			$msg .= __("Please, change the permissions of those folders to 777 if you want to be able to restore a backup",'pressbackup');
			$pressbackup->sessionWrite('error_msg', $msg);
			$pressbackup->redirect(array('function' => 'dashboard')); exit;
		}

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		//get the file and store it on a tmp folder
		$tmp_name = $bm->get($name);

		//go back on bad file
		if(!$tmp_name || !$misc->checkBackupIntegrity($tmp_name)){
			$pressbackup->redirect(array('function' => 'dashboard')); exit;
		}

		//r-r-r-restore it
		if($bm->restore(array('tmp_name'=>$tmp_name))) {
			//delete files from tmp folder
			$misc->perpareFolder($pressbackup->path['PBKTMP']);
		}

		$pressbackup->redirect(array('function' => 'dashboard')); exit;
	}

	/**
	 * Get a backup from backup list to download it later
	 */
	function backupGet ($name=null)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		//get current service
		$enabled_services = $misc->currentService();
		$service = ($saved_service = $pressbackup->sessionRead('dash.service'))?$saved_service:$enabled_services[0]['id'];

		//Exclusive action for pressbackup memberships
		if ($service == 'pressbackup'){

			//make it available on our servers
			$resource = $bm->get2($name);

			//go back on bad file
			if(!$resource){
				$pressbackup->redirect(array('function' => 'dashboard')); exit;
			}

			//download it from our servers
			$pressbackup->redirect($resource['download']); exit;
		}

		//get the file and store it on a tmp folder
		$tmp_name = $bm->get($name, $service);

		//go back on bad file
		if(!$tmp_name || !$misc->checkBackupIntegrity($tmp_name)){
			$pressbackup->redirect(array('function' => 'dashboard')); exit;
		}

		$pressbackup->redirect(array('function' => 'backupDownload', base64_encode($tmp_name)));
	}


	/**
	 * Download a backup
	 * the backup was presviously stored by createBackupAndDownloadIt
	 */
	public function backupDownload ($file =null)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		//clean output buffer (where echos and prints are)
		@ob_end_clean();

		$file = base64_decode($file);

		//fix for no occidental characers, basename and pathinfo remove them from $file
		$pathinfo = explode(DS, $file);
		$name = array_pop($pathinfo);

		//set clean layout and set the file name to the view
		$pressbackup->viewSetLayout('blank');
		$pressbackup->viewSet('file', $file);
		$pressbackup->viewSet('name', $name);

		//clean all tmp folder after download
		register_shutdown_function (array($misc, 'cleanTmpFordes'));
	}


	/**
	 * Remove a backup From de backup list
	 *
	 * @name string: name of the backup to remove
	 */
	public function backupDelete ($name=null)
	{
		global $pressbackup;

		$pressbackup->import('BackupsManager.php');
		$bm = new BackupsManagerPBLib();

		//Delete the file
		$bm->delete($name);

		//redirect to the correct dashboard
		$pressbackup->redirect(array('function' => 'dashboard'));
	}


	//------------------------------------------------------------------------------------


	/**
	 * Check the status of the backup now process
	 *
	 * This function its called via Ajax and inform what
	 * is the process doing. And most important when
	 * the process finish.
	 */
	public function cronBackupStatus ()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		//get background process status
		$response = $misc->backgroundStatus();

		//clean output buffer
		ob_end_clean();

		//send json
		header('Status: 200 OK', true);
		header('HTTP/1.1 200 OK', true);
		header("Content-type: application/json", true);
		echo $response;
		exit;
	}



	/**
	 * Background process error handler
	 *
	 * This function register the errors ocurred on the background process
	 */
	function creation_eh($level, $message, $file, $line, $context)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$misc->writeLogFile('create.fail', $misc->getLogFile('create.fail') . "<br> \n" .$message );
		return false;
	}


	//------------------------------------------------------------------------------------

	/**
	 * Shows host information && background status (for report an error)
	 */
	public function reportInfo( $resetScheduler = false, $cleanTMPFolders = false, $cleanErrorLog = false)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		if ($resetScheduler) {
			$pressbackup->import('Scheduler.php');
			$sch = new SchedulerPBLib();
			$sch->remove('all');
			$pressbackup->redirect(array('function' => 'reportInfo'));
		}

		if ($cleanTMPFolders) {
			$misc->cleanTmpFordes();
			$pressbackup->redirect(array('function' => 'reportInfo'));
		}

		if ($cleanErrorLog) {
			$misc->deleteLogFile('create.fail');
			$pressbackup->redirect(array('function' => 'reportInfo'));
		}

		//basic settings info
		$services = $misc->currentService();
		$enabled_services = array(); foreach ($services as $s){ $enabled_services[] = $s['id'];  }

		$plugin_info = get_plugin_data($pressbackup->status['plugin.fullpath'] . DS . $pressbackup->status['plugin.mainfile']);
		$basicInfo = array(
			'Host'=> array(
				'modules' =>  get_loaded_extensions(),
				'type' => $_SERVER['SERVER_SOFTWARE'],
				'sapi' => php_sapi_name(),
				'port' => $_SERVER['SERVER_PORT'],
				'mem_max' => ini_get('memory_limit'),
				'mem_used' => memory_get_peak_usage(true),
				'tmp_dir' => $pressbackup->path['systmp'],
				'tmp_free' =>  disk_free_space($pressbackup->path['systmp']),
			),
			'User' => array(
				'browser' => $_SERVER['HTTP_USER_AGENT']
			),
			'WP'=> array(
				'version' => get_bloginfo ('version'),
				'url' => get_bloginfo ('wpurl'),
			),
			'Plugin'=> array(
				'version' => $plugin_info['Version'],
				'service' => join(',',$enabled_services),
			),
		);

		$logs = array(
			'errors' => $misc->getLogFile('create.fail'),
			'creation' => $misc->getLogFile('create.log'),
			'save' => $misc->getLogFile('sent.log'),
		);

		//folder contents
		$tmp_dir_path = $pressbackup->path['PBKTMP'] . DS;
		$files = array();
		if(is_dir($tmp_dir_path)) {

			$tmp_dir = scandir($tmp_dir_path);
			for($i=0; $i < count($tmp_dir); $i++){

				if(in_array($tmp_dir[$i], array('.', '..'))){continue;}

				$files[]=array(
					'name' => $tmp_dir[$i],
					'size' => filesize ($tmp_dir_path.DS.$tmp_dir[$i]),
					'download'=> base64_encode($tmp_dir_path.DS.$tmp_dir[$i]),
				);
			}
		}

		$pressbackup->viewSet('info', $basicInfo);
		$pressbackup->viewSet('logs', $logs);
		$pressbackup->viewSet('files', $files);
	}

}
?>
