<?php

/**
* Backup Functions lib for Pressbackup.
*
* This Class provide the functionality to mannage backups
* functions for creation, restoring, get and save from S3 and Ppro
*
* Licensed under The GPL v2 License
* Redistributions of files must retain the above copyright notice.
*
* @link			http://pressbackup.com
* @package		libs
* @subpackage	libs.backups
* @since		0.1
* @license		GPL v2 License
*/

class BackupsManagerPBLib
{
	/**
	 * Local Reference to the framework core object
	 *
	 * @var object
	 * @access private
	 */
	private $fp = null;

	/**
	 * Local Copy to Misc Lib
	 *
	 * @var object
	 * @access public
	 */
	public $Misc = null;

	/**
	 * Constructor.
	 *
	 * @param object FramePress Core
	 * @access public
	 */
	public function __construct ()
	{
		global $pressbackup;
		$this->fp = &$pressbackup;

		$pressbackup->import('Misc.php');
		$this->Misc = new MiscPBLib();
	}

	//----------------------------------------------------------------------------------------

	/**
	 * Create a Backup file of the system
	 *
	 * Create a backup file with the content specified on settings
	 */
	public function create ()
	{
		//set infinite time for this action
		set_time_limit (0);

		date_default_timezone_set($this->Misc->timezoneString());

		//tools and info
		$settings=get_option('pressbackup.preferences');

		//clean log && tmp dir
		$this->Misc->cleanTmpFordes();

		//write log file so foreground process can know what we are doing
		$this->Misc->writeLogFile('create.log', 'start');

		//notice pressbackup about the process start,
		//and also send updated data about enabled services and time zone
		$srv = $this->Misc->serviceObject('pressbackup');
		$services = $this->Misc->currentService();
		$enabled_services = array(); foreach ($services as $s){ $enabled_services[] = $s['id'];  }
		$srv->pingStarted($enabled_services, $this->Misc->timezoneString());

		//zip files and export db
		if( !$this->backupDB() || !$this->backupFiles() ){
			return false;
		}

		//Zip container: file name
		$backup_file_type = str_replace(',', '-', $settings['backup']['type']);
		$blog_name = str_replace(' ', '-', $this->Misc->normalizeString(strtolower(get_bloginfo( 'name' ))));
		$zip_file_name = $blog_name.'-backup_'.$backup_file_type.'_'.date('M-d-His').'.zip';

		//what type of zip creation must we use?
		$type = 'shell';
		if( $settings['compatibility']['zip'] == 10 ) { $type = 'php'; }

		//Create xip container
		$res = $this->Misc->zip($type, array(
			'context_dir'=>$this->fp->path['systmp'],
			'dir' => basename( $this->fp->path['PBKTMP']) . DS,
			'zip' => $this->fp->path['PBKTMP'] . DS . $zip_file_name,
			'compression' => 0
		));

		//check response of zip creation
		if( !$res ) {
			//inform error
			$this->fp->sessionWrite( 'error_msg', __("Zip file is corrupt - creation failed",'pressbackup'), true);

			//write log file so foreground process can know what we are doing
			//something went wrong
			$this->Misc->writeLogFile('create.log', 'fail');

			return false;
		}

		//write log file so foreground process can know what we are doing
		//all was OK!
		$this->Misc->writeLogFile('create.log', 'finish');
		return $this->fp->path['PBKTMP'] . DS . $zip_file_name;
	}

	/**
	 * Create a Backup file of wp-content folders
	 */
	private function backupFiles()
	{
		//tools and info
		$settings=get_option('pressbackup.preferences');

		//read what to backup
		$backup_file_type = explode(',', $settings['backup']['type']);

		//uploads
		if(in_array('1', $backup_file_type) && file_exists(WP_CONTENT_DIR. DS.'uploads'))
		{

			//write log file so foreground process can know what we are doing
			//begining upload backup!
			$this->Misc->writeLogFile('create.log', __('Creating Uploads folder backup','pressbackup'));

			//what type of zip creation must we use?
			$type = 'shell';
			if( $settings['compatibility']['zip'] == 10 ) { $type = 'php'; }

			//Create uploads zip container
			$res = $this->Misc->zip($type, array(
				'context_dir' => WP_CONTENT_DIR,
				'dir' => 'uploads'.DS,
				'zip' => $this->fp->path['PBKTMP'].DS.'uploads.zip'
			));

			//check response of zip creation
			if( !$res ) {
				//write log file so foreground process can know what we are doing
				//something went wrong
				$this->Misc->writeLogFile('create.log', 'fail');
				return false;
			}
		}

		//plugins
		if(in_array('3', $backup_file_type))
		{
			//write log file so foreground process can know what we are doing
			//begining plugins backup!
			$this->Misc->writeLogFile('create.log', __('Creating Plugins folder backup','pressbackup'));

			//what type of zip creation must we use?
			$type = 'shell';
			if( $settings['compatibility']['zip'] == 10 ) { $type = 'php'; }

			//Create uploads zip container
			$res = $this->Misc->zip($type, array(
				'context_dir' => WP_CONTENT_DIR,
				'dir' => 'plugins'.DS,
				'zip' => $this->fp->path['PBKTMP'].DS.'plugins.zip'
			));

			//check response of zip creation
			if( !$res ) {
				//write log file so foreground process can know what we are doing
				//something went wrong
				$this->Misc->writeLogFile('create.log', 'fail');
				return false;
			}
		}

		//themes
		if(in_array('5', $backup_file_type))
		{
			//write log file so foreground process can know what we are doing
			//begining themes backup!
			$this->Misc->writeLogFile('create.log', __('Creating Themes folder backup','pressbackup'));

			//what type of zip creation must we use?
			$type = 'shell';
			if( $settings['compatibility']['zip'] == 10 ) { $type = 'php'; }

			//Create uploads zip container
			$res = $this->Misc->zip($type, array(
				'context_dir' => WP_CONTENT_DIR,
				'dir' => 'themes'.DS,
				'zip' => $this->fp->path['PBKTMP'].DS.'themes.zip'
			));

			//check response of zip creation
			if( !$res ) {
				//write log file so foreground process can know what we are doing
				//something went wrong
				$this->Misc->writeLogFile('create.log', 'fail');
				return false;
			}
		}
		return true;
	}

	/**
	 * Create a Backup file with the SQL dump
	 */
	private function backupDB ()
	{
		//tools and info
		global $wpdb;
		$settings=get_option('pressbackup.preferences');

		//maximun multimple inserts
		$insert_max = 50;

		//read backup preferences
		$backup_db_type = explode(',', $settings['backup']['type']);

		//Return if no DB backup needed
		if(!in_array('7', $backup_db_type)) { return true; }

		//write log file so foreground process can know what we are doing
		$this->Misc->writeLogFile('create.log', __('Creating Database backup','pressbackup'));

		//save .httaccess for this server---porque aca??????
		try { @copy( ABSPATH. '.htaccess', $this->fp->path['PBKTMP'] . DS . '.htaccess'); } catch (Exception $e) { }

		//save server URL for this Database (for magrations)
		$file = $this->fp->path['PBKTMP'] . DS . 'server';
		if(!$fh=fopen( $file, 'w')) {
			$this->fp->sessionWrite( 'error_msg',  __("can not create file to store server for this SQL dump",'pressbackup'), true);
			$this->Misc->writeLogFile('create.log', 'fail');
			return false;
		}

		fwrite($fh, get_bloginfo( 'wpurl' ));
		fwrite($fh, $wpdb->prefix);
		fclose($fh);

		//1A - Create database.sql file
		$file = $this->fp->path['PBKTMP'] . DS . 'database.sql';
		if(!$fh=fopen( $file, 'w')) {
			$this->fp->sessionWrite( 'error_msg',  __("can not create file for SQL dump",'pressbackup'), true);
			$this->Misc->writeLogFile('create.log', 'fail');
			return false;
		}

		//1B - Dump SQL header
		$file_header= '-- PressBackup SQL Dump'."\n".
		'-- version 1.2'."\n".
		'-- http://www.infinimedia.com'."\n\n".
		'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";'."\n\n";
		fwrite($fh, $file_header."\n\n");


		//1C - Get all tables in the DB
		$DB_tables = $wpdb->get_results('SHOW TABLES');
		if(!$DB_tables){
			$this->fp->sessionWrite( 'error_msg',  __("Database Server return empty/wrong data",'pressbackup'), true);
			$this->Misc->writeLogFile('create.log', 'fail');
			return false;
		}

		//Loop into tables and save each table  structure and data
		for($i=0; $i<count($DB_tables); $i++) {

			$index_tables = 'Tables_in_'.DB_NAME;
			$table_now = $DB_tables[$i]->$index_tables;

			//write log file so foreground process can know what we are doing
			$this->Misc->writeLogFile('create.log', sprintf(__('Creating Database backup: Table  %1$s','pressbackup'), $table_now));

			//2A - Get table estructure
			$query = $wpdb->get_results('SHOW CREATE TABLE ' . $table_now);
			if(!$query){
				$this->fp->sessionWrite( 'error_msg',  __("Database Server return empty/wrong data",'pressbackup'), true);
				$this->Misc->writeLogFile('create.log', 'fail');
				return false;
			}

			//2B - Dump  CREATE TABLE  SQL
			$index = 'Create Table';
			$table_structure = $query[0]->$index;
			$table_structure = 'DROP TABLE IF EXISTS `'.$table_now.'`;' . "\n" . $table_structure;
			fwrite($fh, "\n\n".$table_structure.";\n\n");

			//3A Get fields list for this table
			$describe_table = $wpdb->get_results('DESCRIBE ' . $table_now);
			if(!$describe_table){
				$this->fp->sessionWrite( 'error_msg',  __("Database Server return empty/wrong data",'pressbackup'), true);
				$this->Misc->writeLogFile('create.log', 'fail');
				return false;
			}

			//3B Generate INSERT INTO header  (with 3A info)
			$insert_header=$fields=array();
			for ($j=0; $j< count($describe_table); $j++) {
				$insert_header[$j] = '`'. $describe_table[$j]->Field.'`';
				$fields[$j]=$describe_table[$j]->Field;
			}
			$insert_header = 'INSERT INTO `'.$table_now.'` ( '. join(',', $insert_header). ') VALUES ';

			//3C count rows for this table
			$inserts_count = $wpdb->get_results('SELECT COUNT(*) AS cant FROM  `'.$table_now.'`');
			$cant = $inserts_count[0]->cant;

			//Jump to next table if this table is empty
			if($cant === 0){ break; }

			//Loop into paged table rows
			$insert_pages = ceil ($cant / $insert_max);
			for ($p = 0; $p < $insert_pages; $p ++)
			{
				//3D - Get row for this page
				$rows = $wpdb->get_results('SELECT * FROM  `'. $table_now .'` LIMIT '.($p * $insert_max).','.$insert_max);
				if(!$rows){
					$this->fp->sessionWrite( 'error_msg',  __("Database Server return empty/wrong data",'pressbackup'), true);
					$this->Misc->writeLogFile('create.log', 'fail');
					return false;
				}

				//3E - Clean rows data and format insert_data
				$insert_data = array();
				for ($j=0; $j< count($rows); $j++) {
					$insert_data[$j] = array();
					for ($k=0; $k< count($fields); $k++) {
						$insert_data[$j][] = '\''.mysql_real_escape_string($rows[$j]->$fields[$k]).'\'';
					}
					$insert_data[$j] = '('. join(',', $insert_data[$j]) . ')';
				}

				//3F - Dump INSERT INTO sql header and data
				if($insert_data) {
					fwrite($fh, "\n".$insert_header."\n".join(",\n", $insert_data).';'."\n\n");
				}
			}
		}
		fclose($fh);

		return true;
	}

	//----------------------------------------------------------------------------------------

	/**
	 * Save a File on the correct storage service
	 *
	 * This is a short cut for save on * functions
	 */
	public function save ($zip_file)
	{
		set_time_limit (0);

		//tools and info
		$settings=get_option('pressbackup.preferences');

		//write log file so foreground process can know what we are doing
		//begining backup storage!
		$this->Misc->writeLogFile('sent.log', 'start');

		$srv = $this->Misc->serviceObject('pressbackup');

		//notice pressbackup about the creation,
		$srv->pingCreated($zip_file);

		//save the filesize if is a full backup
		if($settings['backup']['type'] == '7,5,3,1') {
			update_option('pressbackup.backupFullSize', filesize($zip_file));
		}

		$services = $this->Misc->currentService();

		$es = array();
		foreach ($services as $service){

			$es[] = $service['id'];

			if ($service['id'] == 'pressbackup' && !$this->saveOnPressbackup($zip_file)){
				return false;
			}

			elseif ($service['id'] == 'dropbox' && !$this->saveOnDropbox($zip_file)){
				return false;
			}

			elseif ($service['id'] == 's3' && !$this->saveOnS3($zip_file)){
				return false;
			}

			elseif ($service['id'] == 'local' && !$this->saveOnLocal($zip_file)){
				return false;
			}

		}

		//notice pressbackup about the save
		$srv->pingSaved($es, $zip_file);

		//save massage
		$this->fp->sessionWrite( 'general_msg', __('Backup saved!','pressbackup'), true);
		$this->fp->sessionWrite( 'saved', filesize($zip_file), true);
		$this->Misc->writeLogFile('sent.log', 'finish');
		return true;
	}

	private function saveOnPressbackup($zip_file)
	{
		//get Service object && settings
		$service = $this->Misc->service('pressbackup');
		$srv = $this->Misc->serviceObject('pressbackup');

		//send file
		if(!$srv->putFile($zip_file)) {

			$message = __('Connection with Pressbackup Pro fail. Try again later','pressbackup');
			if ($srv->response['body'] && $aux = @json_decode($srv->response['body'], true)) {
				if(isset($aux['code']) && in_array($aux['code'], array('2001', '2002'))){
					$msg =  __('Oh no! Looks like your site exceeds the capacity on your account. Please click here to find out about upgrading to a paid plan', 'pressbackup');
					$message = '<a href="http://pressbackup.com/pro/account/billing" target="_blank">' . $msg . '</a>';
				}else{
					$message = __($aux['msg'], 'pressbackup');
				}
			}

			$this->fp->sessionWrite( 'error_msg', $message, true);
			$this->Misc->writeLogFile('sent.log', 'fail');
			return false;
		}

		//check the number of backup stored
		$bucket_files = $srv->getFilesList();
		$this->removeExcessBackups($service, $bucket_files);
		return true;
	}

	private function saveOnDropbox($zip_file)
	{
		//get Service object && settings
		$service = $this->Misc->service('dropbox');
		$srv = $this->Misc->serviceObject('dropbox');

		//send file
		if(!$srv->putFile($zip_file)) {
			$this->fp->sessionWrite( 'error_msg',  __("Connection with Dropbox fail. Try again later",'pressbackup'), true);
			$this->Misc->writeLogFile('sent.log', 'fail');
			return false;
		}

		//check the number of backup stored
		$bucket_files = $srv->getFilesList();
		$this->removeExcessBackups($service, $bucket_files);
		return true;
	}

	private function saveOnS3($zip_file)
	{
		//get Service object && settings
		$service = $this->Misc->service('s3');
		$srv = $this->Misc->serviceObject('s3');

		//fix for no occidental characers, basename and pathinfo remove them from $zip_file
		$pathinfo = explode(DS, $zip_file);
		$name = array_pop($pathinfo);

		//send file
		if(!$srv->putObjectFile($zip_file, $service['bucket_name'], $name, ServiceS3PBLib::ACL_PRIVATE))
		{
			$this->fp->sessionWrite( 'error_msg',  __("can not save file on S3",'pressbackup'), true);
			$this->Misc->writeLogFile('sent.log', 'fail');
			return false;
		}

		//check the number of backup stored
		$bucket_files = $srv->getBucket( $service['bucket_name']);
		$this->removeExcessBackups($service, $bucket_files);
		return true;
	}

	private function saveOnLocal($zip_file)
	{
		//get Service object && settings
		$service = $this->Misc->service('local');
		$srv = $this->Misc->serviceObject('local');

		//send file
		if(!$srv->putFile($zip_file)) {
			$this->fp->sessionWrite( 'error_msg',  __("Can not store the file in the local host",'pressbackup'), true);
			$this->Misc->writeLogFile('sent.log', 'fail');
			return false;
		}

		//check the number of backup stored
		$bucket_files = $srv->getFilesList();
		$this->removeExcessBackups($service, $bucket_files);
		return true;
	}


	private function removeExcessBackups ($service, $backups)
	{
		//settings
		$settings=get_option('pressbackup.preferences');

		$backups = @$this->Misc->msort($service['id'], $backups);
		$backups = $this->Misc->filterFiles('this_site', $backups);

		$copies = ($service['id'] == 'pressbackup')?$settings['backup']['copies']['pressbackup']:$settings['backup']['copies']['others'];

		if($copies !== 'All' && count($backups) > $copies) {
			$file =  array_pop($backups);
			$this->delete($file['name'], array('vervose' => false, 'service'=>$service['id']));
		}
	}

	//----------------------------------------------------------------------------------------

	public function restore ($args)
	{
		//set infinite time for this action
		set_time_limit (0);

		//clean log && tmp dir
		$this->Misc->cleanTmpFordes();

		//try to open the zip file
		$zip = new ZipArchive();
		if(!$zip->open($args['tmp_name'])===true) {
			$this->fp->sessionWrite( 'error_msg',  __("Backup seems corrupt. Process aborted",'pressbackup'));
			return false;
		}

		//shortcuts
		$PBKTMP = $this->fp->path['PBKTMP'];
		$SYSTMP = $this->fp->path['systmp'];

		//on a migration or blog name change tmp folder change its name
		$oldTmpFolderName = dirname ($zip->getNameIndex(0));
		$newTmpFolderName = basename($PBKTMP);

		//this will use/create the tmp folder with the old name
		$ok_extract = $zip->extractTo($SYSTMP);
		$zip->close();

		if(!$ok_extract) {
			$this->fp->sessionWrite( 'error_msg', sprintf(__('Unable to write backup files on "%1$s". Process aborted','pressbackup'), $SYSTMP));
			return false;
		}

		//move files from old forder to new one ( for migrations )
		if( $oldTmpFolderName != $newTmpFolderName ){

			$scan = @scandir($SYSTMP . DS . $oldTmpFolderName);
			foreach ($scan as $index => $path) {
				if (in_array($path, array('.', '..'))) { continue; }
				copy($SYSTMP . DS . $oldTmpFolderName . DS .$path, $SYSTMP . DS . $newTmpFolderName . DS .$path);
				@unlink( $SYSTMP . DS . $oldTmpFolderName . DS .$path );
			}
		}

		//restore backup
		$resDB =$this->restoreDB();
		$resFiles =$this->restoreFiles();

		//wp_content files and .sql file are missing from the zip
		if(is_null($resDB) && is_null($resFiles)) {
			$this->fp->sessionWrite( 'error_msg',  __("The backup file is invalid or it is empty. Restore process aborted",'pressbackup'));
			return false;
		}
		//there was some error, the error message was generated on the respective functions
		elseif(!$resDB || !$resFiles) {
			return false;
		}

		$this->fp->sessionWrite( 'general_msg',  __("System restored!",'pressbackup'));
		return true;
	}

	private function restoreFiles()
	{
		//this will flag is something was done.
		$something_done = null;

		//shortcuts
		$PBKTMP = $this->fp->path['PBKTMP'].DS;

		$zip = new ZipArchive();

		//restore themes
		if(file_exists($PBKTMP .'themes.zip')){

			//check file healt
			if($zip->open( $PBKTMP .'themes.zip') !== true) {
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore themes, themes backup is corrupt. Restore process aborted",'pressbackup'));
				return false;
			}

			//delete files file to replace it with backup ones
			$this->Misc->actionFolder(WP_CONTENT_DIR.DS.'themes' , array('function' => 'del'));

			//try to extract it
			if(!@$zip->extractTo(WP_CONTENT_DIR)){
				$zip->close();
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore themes folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
				return false;
			}
			$zip->close();

			$something_done = true;
		}

		//restore uploads
		if(file_exists($PBKTMP .'uploads.zip')){

			//check file healt
			if($zip->open( $PBKTMP .'uploads.zip') !== true) {
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore uploads folder, uploads backup is corrupt. Restore process aborted",'pressbackup'));
				return false;
			}

			//delete files file to replace it with backup ones
			$this->Misc->actionFolder(WP_CONTENT_DIR.DS.'uploads' , array('function' => 'del'));

			//try to extract it
			if(!@$zip->extractTo(WP_CONTENT_DIR))
			{
				$zip->close();
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore uploads folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
				return false;
			}

			$zip->close();

			$something_done = true;
		}

		//restore plugins
		if(file_exists($PBKTMP .'plugins.zip')){

			//check file healt
			if($zip->open( $PBKTMP .'plugins.zip') !== true) {
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore plugins folder, plugins backup is corrupt. Restore process aborted",'pressbackup'));
				return false;
			}

			//delete files file to replace it with backup ones
			$this->Misc->actionFolder(WP_CONTENT_DIR.DS.'plugins' , array('function' => 'del'));

			//try to extract it
			if(!@$zip->extractTo(WP_CONTENT_DIR)){
				$zip->close();
				$this->fp->sessionWrite( 'error_msg',  __("Can not restore plugins folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
				return false;
			}
			$zip->close();

			$something_done = true;
		}

		if($something_done){
			$this->Misc->actionFolder(WP_CONTENT_DIR , array('function' => 'chmod', 'param' => array('file' =>0644, 'dir'=>0755)));
		}

		return $something_done;
	}

	private function restoreDB()
	{
		require_once(ABSPATH . '/wp-admin/admin.php');
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

		//tools and info
		global $wpdb;
		global $wp_rewrite;

		//shortcuts
		$PBKTMP = $this->fp->path['PBKTMP'] . DS;

		//check if need to restore DB
		if(!file_exists($PBKTMP .'database.sql')){ return null; }

		//get old server name to can restore DB with it
		if(!$fn=fopen( $PBKTMP .'server', 'rb')) {
			$this->fp->sessionWrite( 'error_msg',  __("Can not restore database, missing files. Restore process aborted",'pressbackup'));
			return false;
		}

		//old  DB prefix and server URL
		$last_server = trim(fgets($fn));
		$last_prefix = trim(@fgets($fn));
		fclose($fn);

		$new_server = get_bloginfo( 'wpurl' );
		$new_prefix = $wpdb->prefix;

		//compatibility with old server file version
		if(!$last_prefix){$last_prefix = $new_prefix; }

		//get SQL dump
		if(!$DBdump = @fopen($PBKTMP .'database.sql', 'rb')) {
			$this->fp->sessionWrite( 'error_msg',  __("Can not restore database, missing files (.sql). Restore process aborted",'pressbackup'));
			return false;
		}

		//Read headers from .sql
		$query='';
		while (($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE(.)*/", $buffer) && !preg_match("/^DROP TABLE IF EXISTS(.)*/", $buffer)) {
			$query .= $buffer;
		}
		$query = str_replace($last_server, $new_server, $query);
		@$wpdb->query(trim($query));
		//ppr($query);


		//Read until EOF cuting sql into create and insert stataments
		while($buffer)
		{

			if(preg_match("/CREATE TABLE IF NOT EXISTS `(.*)` +\(/", $buffer, $mathces)){

				if(isset($mathces[1]) && $mathces[1]){
					$wpdb->query('DROP TABLE '.$mathces[1]);
					//ppr('DROP TABLE '.$mathces[1]);
				}

				$query = $buffer;
				while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE IF NOT EXISTS(.)*/", $buffer))
				{
					$query .= $buffer;
				}
				$query = str_replace($last_server, $new_server, $query);
				dbDelta( trim($query) );
				//ppr($query);

			}

			elseif(preg_match("/CREATE TABLE(.)*/", $buffer)){

				$query = $buffer;
				while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE(.)*/", $buffer) && !preg_match("/^DROP TABLE IF EXISTS(.)*/", $buffer)) {
					$query .= $buffer;
				}
				$query = str_replace($last_server, $new_server, $query);
				dbDelta( trim($query) );
				//ppr($query);

			}

			if(preg_match("/DROP TABLE IF EXISTS(.*)/", $buffer)){

				$wpdb->query($buffer);
				//ppr($buffer);

				while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE(.)*/", $buffer) && !preg_match("/^DROP TABLE IF EXISTS(.)*/", $buffer)) {
					continue;
				}
			}

			if(preg_match("/^INSERT INTO(.)*/", $buffer)) {

				//save insert header
				$header = $buffer;

				// start read insert values
				$i=0; $inserts='';
				while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE(.)*/", $buffer) && !preg_match("/^DROP TABLE IF EXISTS(.)*/", $buffer)) {
					$inserts .= $buffer; $i++;

					//inser 50 results at once
					if($i==50) {
						$inserts= trim($inserts);
						if( substr($inserts, -1) == ',') { $inserts = substr_replace($inserts, ';', -1, 1);}
						$inserts = str_replace($last_server, $new_server, $inserts);
						@$wpdb->query(trim($header.$inserts));
						//ppr($header.$inserts); ppr($header. ' plus 50 rows');
						$i=0; $inserts='';
					}
				}

				//if something remaing to save
				if(trim($inserts)) {
					$inserts= trim($inserts);
					if( substr($inserts, -1) == ',') { $inserts = substr_replace($inserts, ';', -1, 1);}
					$inserts = str_replace($last_server, $new_server, $inserts);
					@$wpdb->query(trim($header.$inserts));
					//ppr($header.$inserts); ppr($header. ' outstanding rows');
				}
			}

		}
		fclose($DBdump);

		//update options
		$siteopts = wp_load_alloptions();
		$this->updateSiteOptions($siteopts, $last_server, $new_server);

		//copy backed up .htaccess
		try { @copy($PBKTMP .'.htaccess', ABSPATH .'.htaccess') ; } catch (Exception $e) {}

		//remake .htaccess
		//see extra controller for this part
		$settings= get_option('pressbackup.preferences');
		$settings['restore']=true;
		if($last_prefix != $new_prefix){
			$settings['show.prefix.msg']=$last_prefix;
		}
		update_option('pressbackup.preferences',$settings);

		return true;
	}

	private function updateSiteOptions(array $options, $old_url, $new_url)
	{
		require_once ABSPATH .'wp-includes/functions.php';
		foreach ($options as $option_name => $option_value) {

			if (FALSE === strpos($option_value, $old_url)) {
				continue;
			}

			if (is_array($option_value)) {
				$this->updateSiteOptions($option_value, $old_url, $new_url);
			}

			// attempt to unserialize option_value
			if(!is_serialized($option_value)) {
				$newvalue = str_replace($old_url, $new_url, $option_value);
			} else {
				$newvalue = $this->updateSerializedOptions(maybe_unserialize($option_value), $old_url, $new_url);
			}

			update_option($option_name, $newvalue);
		}
	}

	private function updateSerializedOptions($data, $old_url, $new_url) {
		require_once ABSPATH .'wp-includes/functions.php';
		// ignore _site_transient_update_*
		if(is_object($data)){
			return $data;
		}

		foreach ($data as $key => $val) {
			if (is_array($val)) {
					$data[$key] = $this->updateSerializedOptions($val, $old_url, $new_url);
			} else {
				if (!strstr($val, $old_url)) {
					continue;
				}
				$data[$key] = str_replace($old_url, $new_url, $val);
			}
		}
		return $data;
	}

	//----------------------------------------------------------------------------------------

	public function get ($file = null, $from = null)
	{
		//set infinite time for this action
		@set_time_limit (0);

		if(!$file){
			$this->fp->sessionWrite( 'error_msg',  __("Missing backup file name",'pressbackup'));
			return false;
		}

		//resolve were to get the file
		if($from){
			$service = $from;
		}
		elseif ($saved_service = $this->fp->sessionRead('dash.service')){
			$service = $saved_service;
		}
		else{
			$enabled_services = $this->Misc->currentService();
			$service = $enabled_services[0]['id'];
		}

		$srv = $this->Misc->serviceObject($service);

		//where to put backup file
		$stored_in = $store_in = $this->fp->path['systmp']. DS . $file;

		// succesfully transfer ?
		$transfer = false;

		//retrive backups from Pressbackup
		if( $service == 'pressbackup' ) {
			$transfer = $srv->getFile($file, $store_in);
		}

		//retrive backups from Dropbox
		elseif( $service == 'dropbox' ) {
			$transfer = $srv->getFile($file, $store_in);
		}

		//retrive backups from Amazon S3
		elseif( $service == 's3' ){
			$srvSettings = $this->Misc->service('s3');
			$transfer = $srv->getObject($srvSettings['bucket_name'], $file, $store_in);
		}

		//retrive backups from Local
		elseif( $service == 'local' ) {
			$transfer = $srv->getFile($file, $store_in);
		}


		if(!$transfer){
			$this->fp->sessionWrite( 'error_msg',  __('Failed to get file','pressbackup'));
			return false;
		}

		return $stored_in;
	}

	/* Only for pressbackup */
	public function get2 ($file='')
	{
		//set infinite time for this action
		set_time_limit (0);

		if(!$file){
			$this->fp->sessionWrite( 'error_msg',  __('Missing backup file name','pressbackup'));
			return false;
		}

		//get Service object && settings
		$srv = $this->Misc->serviceObject('pressbackup');

		//succesfully transfer ?
		$transfer = $srv->getFile2($file);

		if(!$transfer) {
			$this->fp->sessionWrite( 'error_msg',  __('Failed to get file from Pressbackup','pressbackup'));
			return false;
		}

		return $transfer;
	}

	//----------------------------------------------------------------------------------------

	public function delete($file='', $options = array())
	{
		$options = array_merge(array('vervose'=>true), $options);

		//set infinite time for this action
		set_time_limit (0);

		if(!$file){
			if($options['vervose']){ $this->fp->sessionWrite( 'error_msg',  __("Missing backup file name",'pressbackup')); }
			return false;
		}

		//deleted ?
		$deleted = false;

		//resolve were to delete the file
		if(isset($options['service'])){
			$service = $options['service'];
		}
		elseif ($saved_service = $this->fp->sessionRead('dash.service')){
			$service = $saved_service;
		}
		else{
			$enabled_services = $this->Misc->currentService();
			$service = $enabled_services[0]['id'];
		}

		$srv = $this->Misc->serviceObject($service);

		//Delete backups from Pressbackup
		if( $service == 'pressbackup') {
			$deleted = $srv->deleteFile($file);
		}

		//Delete backups from Dropbox
		elseif( $service == 'dropbox' ) {
			$deleted = $srv->deleteFile($file);
		}

		//Delete backups from Amazon S3
		elseif( $service == 's3' ) {
			$srvSettings = $this->Misc->service('s3');
			$deleted = $srv->deleteObject($srvSettings['bucket_name'],$file);
		}

		//Delete backup from localhost
		elseif( $service == 'local' ) {
			$deleted = $srv->deleteFile($file);
		}

		if(!$deleted) {
			return false;
		}

		if($options['vervose']){ $this->fp->sessionWrite( 'general_msg',  __('Backup deleted!','pressbackup')); }
		return true;
	}

}
?>
