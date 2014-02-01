<?php

class MiscPBLib
{
	/**
	 * Local Reference to the framework core object
	 *
	 * @var object
	 * @access private
	 */
	private $fp = null;

	/**
	 * Constructor.
	 *
	 * @param object FramePress Core
	 * @access public
	 */
	public function __construct()
	{
		global $pressbackup;
		$this->fp = &$pressbackup;
	}

	//------------------------------------------------------------------------------------

	/**
	 * Create a Zip File from a dir, using php zip functions or a zip shell program
	 */
	public function zip($type='shell', $args = array())
	{
		$excludedir = $this->fp->path['systmp'] . '/*';
		if ($type == 'shell' && $bin = $this->checkShell('zip')) {
			$compression = (isset($args['compression'])) ? '-' . $args['compression'] : '';
			$cmd = 'cd ' . $args['context_dir'] . ';';
			$cmd .= $bin . ' -r -q ' . $compression . ' ' . $args['zip'] . ' ' . $args['dir'] . ' -x ' . $excludedir . ';';
			$cmd .= 'chmod 0777 ' . $args['zip'] . ';';
			$cmd .= $bin . ' -T ' . $args['zip'] . ';';
			$res = $this->ShellRun($cmd);

			if (!$res || strpos(strtolower($res[0]), 'ok') === false) {
				$this->fp->sessionWrite( 'error_msg',  __("Creation via shell zip fail. Please see compatibility tab",'pressbackup'), true);
				return false;
			}

			return true;
		}

		if ($type == 'php') {

			//create zip file
			$zip = new ZipArchive();
			if ($zip->open($args['zip'], ZIPARCHIVE::OVERWRITE) !== true) {
				$this->fp->sessionWrite( 'error_msg',  __("Can not create: ",'pressbackup') . $args['zip'], true);
				return false;
			}

			//add files to the zip
			//error messages will be set in zipFolder function
			@chdir($args['context_dir']);
			if (!$this->zipFolder($args['dir'], $zip)) {
				$zip->close();
				return false;
			}
			$zip->close();

			//check if it there not empty folder
			if (!file_exists($args['zip'])) {
				$this->fp->sessionWrite( 'error_msg',  __("Omitting empty or unreadable folder: ",'pressbackup') . $args['dir'], true);
				return true;
			}

			//check if it was created and filled without problems
			if ($zip->open($args['zip'], ZIPARCHIVE::CHECKCONS) !== TRUE) {
				$this->fp->sessionWrite( 'error_msg',  __("Zip file corrupt: ",'pressbackup') . $args['zip'], true);
				$zip->close();
				return false;
			}
			$zip->close();

			return true;
		}

		return false;
	}

	/*
	 * Add a folder and subfolders to a Zip file using php zip functions
	 */
	public function zipFolder($dir, &$zipArchive)
	{
		if (!is_dir($dir) || !$dh = opendir($dir)) {
			$this->fp->sessionWrite( 'error_msg',  __("Can not open dir: ",'pressbackup') . $dir, true);
			return false;
		}

		$excludedir = $this->fp->path['systmp'] . '/';
		if ( $dir == $excludedir ) { return true; }

		// Loop through all the files
		while (($file = readdir($dh)) !== false) {

			//exclude wrong files
			if (($file == '.') || ($file == '..') || ( is_file( $dir . $file ) && !@is_readable($dir . $file) ) ) {
				continue;
			}

			//If it's a folder, run the function again!
			if (is_dir($dir . $file) && !$this->zipFolder($dir . $file . DS, $zipArchive)) {
				closedir($dh);
				return false;
			}

			//write log file so foreground process can know what we are doing
			$this->writeLogFile('create.log', sprintf(__('Backing up folder %1$s','pressbackup'), $dir));

			//else add it to de zip
			if (is_file($dir . $file) && !$zipArchive->addFile($dir . $file)) {
				$this->fp->sessionWrite( 'error_msg',  __("Can not add file: ",'pressbackup') . $dir . $file, true);
				closedir($dh);
				return false;
			}
		}
		closedir($dh);
		return true;
	}

	//------------------------------------------------------------------------------------

	/*
	 * Run a php file from using php shell program
	 */
	public function php($args=array())
	{
		if (isset($args['file']) && !is_file($args['file'])) {
			return false;
		}
		if (isset($args['args']) && !file_exists($args['args'])) {
			return false;
		}

		$bin = $this->checkShell('php');
		$cmd = $bin . ' ' . $args['file'] . ' "' . $args['args'] . '"  > /dev/null  2>&1 &';

		return $this->ShellRun($cmd);
	}

	/**
	 * Checks if a given program exist on the server to run via shell command
	 * return bin name
	 */
	public function checkShell($type = 'zip')
	{

		if ($type == 'zip') {
			if (!$res = $this->ShellRun('whereis -b zip')) {
				return false;
			}

			$res = str_replace('zip: ', '', $res[0]);
			$binaries = explode(' ', $res);
			for ($i = 0; $i < count($binaries); $i++) {
				$res = $this->ShellRun($binaries[$i] . ' -T PBZipTest');
				if ($res && strpos(strtolower($res[1]), 'zip') !== false) {
					return $binaries[$i];
				}
			}
		} elseif ($type == 'php') {

			if (!$res = $this->ShellRun('whereis -b php')) {
				return false;
			}

			$res = str_replace('php: ', '', $res[0]);
			$binaries = explode(' ', $res);
			for ($i = 0; $i < count($binaries); $i++) {
				$res = $this->ShellRun($binaries[$i] . ' -r "echo \'hola\';"');
				if (isset($res[0]) && str_replace(array('\n', ''), '', $res[0]) == 'hola') {
					return $binaries[$i];
				}
			}
			return false;
		}
	}

	/**
	 * Run a shell program previously checked by checkshell
	 */
	private function ShellRun($cmd)
	{
		$output = array();
		$return_var = 1;
		@exec($cmd, $output, $return_var);
		return $output;
	}

	//------------------------------------------------------------------------------------

	public function perpareFolder($dir)
	{
		$this->actionFolder($dir . DS, array('function' => 'del'));
		@mkdir($dir);
		$this->actionFolder($dir . DS, array('function' => 'chmod', 'param' => array(0777)));
	}

	public function actionFolder($dir, $option)
	{
		if(!file_exists($dir)){
			return true;
		}

		//delete file
		if (is_file($dir) && $option['function'] == 'del') {
			return @unlink($dir);
		}

		//change file permissions
		elseif  (is_file($dir) && $option['function'] == 'chmod') {
			//see where are the permisssions to assign
			$perms = (isset($option['param']['file']))?$option['param']['file']:$option['param'][0];
			return @chmod($dir, $perms);
		}

		//change dir permissions
		if (is_dir($dir) && $option['function'] == 'chmod') {
			$perms = (isset($option['param']['dir']))?$option['param']['dir']:$option['param'][0];
			@chmod($dir, $perms);
		}

		$scan = @scandir($dir);
		foreach ($scan as $index => $path) {
			//omitir las entradas
			if (in_array($path, array('.', '..'))) { continue; }
			//recursion
			$this->actionFolder($dir . DS . $path, $option);
		}

		//apply del action to the folder if requested
		if (is_dir($dir) && $option['function'] == 'del') {
			return @rmdir($dir);
		}

	}

	//------------------------------------------------------------------------------------

	/*
	 * Normalize and sort the backup list
	 */
	public function msort($type='S3', $data=array(), $id="time")
	{
		//Normalize the array with de backup list
		//each service have different list format
		$normalized_array = array();
		if ($type == 'pressbackup') {
			foreach ($data as $item) {
				$normalized_array[] = $item;
			}
		} elseif ($type == 'dropbox') {
			foreach ($data['contents'] as $file) {
				$normalized_array[] = array('name' => urldecode(trim($file['path'], '/')), 'time' => strtotime($file['modified']), 'size' => $file['bytes'], 'hash' => "");
			}
		} elseif ($type == 's3') {
			foreach ($data as $item) {
				$normalized_array[] = $item;
			}
		} elseif ($type == 'local') {
			$normalized_array = $data;
		}

		//now sort it by $id
		$temp_array = array();
		while (count($normalized_array) > 0) {
			$lowest_id = 0;
			$index = 0;
			foreach ($normalized_array as $item) {
				if (isset($item[$id]) && $normalized_array[$lowest_id][$id]) {
					if (strcmp($item[$id], $normalized_array[$lowest_id][$id]) > 0) {
						$lowest_id = $index;
					}
				}
				$index++;
			}
			$temp_array[] = $normalized_array[$lowest_id];
			$normalized_array = array_merge(array_slice($normalized_array, 0, $lowest_id), array_slice($normalized_array, $lowest_id + 1));
		}
		return $temp_array;
	}

	/**
	 * Filter the backup list by this site or all sites
	*/
	public function filterFiles($type='all_sites', $backup_files)
	{
		if ($type == 'all_sites') {
			return $backup_files;
		}

		$blog_name = $this->normalizeString(strtolower(trim(get_bloginfo('name')))) . ' backup';
		$filtered_array = array();
		for ($i = 0; $i < count($backup_files); $i++) {
			$backup_from = explode('_', $backup_files[$i]['name']);
			$from= $this->normalizeString(strtolower(str_replace('-', ' ', $backup_from[0])));
			if ( $from == $blog_name) {
				$filtered_array[] = $backup_files[$i];
			}
		}
		return $filtered_array;
	}

	//------------------------------------------------------------------------------------

	/*
	 * Check for the maximun permited size for file upload
	 */
	public function uploadMaxFilesize()
	{
		if (!$filesize = ini_get('upload_max_filesize')) {
			$filesize = "5M";
		}

		if (!$postsize = ini_get('post_max_size')) {
			$postsize = "5M";
		}

		preg_match('!\d+!', $filesize, $matches);
		$filesize = (isset($matches[0]))?$matches[0]:$filesize;

		preg_match('!\d+!', $postsize, $matches);
		$postsize = (isset($matches[0]))?$matches[0]:$postsize;

		return min($filesize, $postsize) * 1024  * 1024;
	}

	/*
	 * get a size in the best/request unit
	 */
	public function getByteSize($size = 0, $unit = null)
	{
		$scan = array (
			'gb' => 1073741824,
			'mb' => 1048576,
			'kb' => 1024,
			'b' => 1
		);

		if (!$size) {
			return 0;
		}

		if ($unit) {
			return round($size / $scan[$unit], 2);
		}

		foreach ($scan as $unit => $factor) {
			if (strlen($size) > strlen($unit) && strtolower(substr($size, strlen($size) - strlen($unit))) == $unit) {
				return substr($size, 0, strlen($size) - strlen($unit)) * $factor;
			}
		}
		return $size;
	}

	//------------------------------------------------------------------------------------

	/*
	 * get current storage service settings
	 */
	public function currentService()
	{
		$settings = get_option('pressbackup.preferences');

		$PS3 = $settings['Service']['s3'];
		$PBCK = $settings['Service']['pressbackup'];
		$PDBOX = $settings['Service']['dropbox'];
		$PLOCAL = $settings['Service']['local'];

		$services = array();

		foreach($settings['Service'] as $name => $sSettings){
			if ($sSettings['enabled']) {
				$services[] = $this->service($name, $settings);
			}
		}

		return $services;
	}

	/*
	 * Return specific storage service settings
	 */
	public function service( $id = null, $settings = null)
	{
		if(!$settings) {
			$settings = get_option('pressbackup.preferences');
		}

		$PS3 = $settings['Service']['s3'];
		$PPRO = $settings['Service']['pressbackup'];
		$PDBOX = $settings['Service']['dropbox'];
		$PLOCAL = $settings['Service']['local'];

		$pref = array (

			's3' => array(
				'id' => 's3',
				'name' => 'Amazon S3',
				'credentials' => $PS3['credential'],
				'bucket_name' => $PS3['bucket_name'],
				'region' => $PS3['region'],
			),

			'dropbox' => array(
				'id' => 'dropbox',
				'name' => 'Dropbox',
				'credentials' => $PDBOX['credential'],
			),

			'pressbackup' => array(
				'id' => 'pressbackup',
				'name' => 'PressBackup',
				'credentials' => $PPRO['credential'],
			),


			'local' => array(
				'id' => 'local',
				'name' => 'Local Host',
				'path' => $PLOCAL['path'],
			),
		);

		if ( $id ) {
			return $pref[$id];
		} else {
			return $pref;
		}
	}

	/*
	 * Return specific storage service settings
	 */
	public function serviceObject( $id = null, $credentials = null)
	{
		if(!$credentials && in_array($id, array('s3', 'pressbackup', 'dropbox'))) {
			$service = $this->service($id);
			$credentials = explode('|AllYouNeedIsLove|', base64_decode( $service['credentials']));
		}
		if(!$credentials && $id == 'local') {
			$service = $this->service($id);
			$credentials = $service['path'];
		}

		$object = null;

		//retrive backups from Amazon S3
		if( $id == 's3')
		{
			$this->fp->import('ServiceS3.php');
			$object = new ServiceS3PBLib($credentials[0], $credentials[1]);
		}

		//retrive backups from Pro
		elseif( $id == 'pressbackup')
		{
			$this->fp->import('ServicePressbackup.php');
			$object = new ServicePressbackupPBLib($credentials);
		}

		//retrive backups from Pro
		elseif( $id == 'dropbox' )
		{
			$this->fp->import('ServiceDropbox.php');
			$object = new ServiceDropboxPBLib($credentials);
		}

		//Delete backup from localhost
		elseif( $id == 'local' )
		{
			$this->fp->import('ServiceLocal.php');
			$object = new ServiceLocalPBLib($credentials);
		}

		return $object;
	}

	//------------------------------------------------------------------------------------

	/*
	 * Return WP defined timesone in a way usable by php functions (datesettimezonestring)
	 */
	public function timezoneString ()
	{
		if( !$val = get_option( 'timezone_string' )){
			$val = ceil(get_option( 'gmt_offset' ));
			$val = ( $val < 0)? str_replace('-', '+', $val):'-'.ltrim(str_replace('+', '-', $val), '-');
			$val = 'Etc/GMT'.$val;
		}
		return $val;
	}

	/**
	 * Return last midnight UTC datetime in local timezone
	 *
	 * example: if local timezone is UTC+3
	 * this will return  [today] 03:00:00
	 */
	public function midnightUTC ()
	{
		$tz = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$midnight_tz = strtotime('today 00:00:00');
		date_default_timezone_set($tz);
		$midnight_UTC_tz = date('d M Y H:i:s', $midnight_tz);

		return $midnight_UTC_tz;
	}

	/*
	 * Remove acent characters, html entities and other weeds from a string
	 */
	public function normalizeString($string)
	{
		//aca se sacan las entidades html que posea para que en el siguiente paso no codifique erradamente los &
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

		//aca se colocan los caracteres especiales como entidades html
		$string = htmlentities($string, ENT_QUOTES, 'UTF-8');

		//aca se cambia el html entities de caracteres acentuados por la letra normal ö -> o
		$string = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $string);

		//aca se borran los html entities del tipo &#98567;
		$string = preg_replace('~&#[0-9]*;~i', '', $string);

		//aca se borran los demas html entities no deceados: &quot; etc etc
		$string = str_replace(array_values(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES )),'',$string);

		//aca se le sacan los vcaracteres "prohibidos"
		$string = str_replace(array('.', '-', '_', '|', ',', '\\', '/', '*', '?', '%', ':', '<', '>'), '', $string);

		return $string;
	}

	/**
	 * check if server is using https
	 */
	public function getCoxProtocol()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$scheme .= 's';
		}
		return $scheme;
	}

	/*
	 * Clean TMP folders
	 */
	public function cleanTmpFordes ()
	{
		//clean log && tmp dir
		$this->perpareFolder($this->fp->path['LOGTMP']);
		$this->perpareFolder($this->fp->path['PBKTMP']);
	}

	//------------------------------------------------------------------------------------

	/**
	 * Check backup upload
	 *
	 * Check if the upload of a backup was made correctly
	 * ej: see if no errors, or if uploaded file is a zip file etc
	 */
	public function checkBackupUpload()
	{
		if (!isset($_FILES['backup']) || $_FILES['backup']['error']==4)
		{
			$this->fp->sessionWrite('error_msg', sprintf(__('You sent an empty file or it is bigger than %1$s Mb. Please try it again','pressbackup'), $this->getByteSize($this->uploadMaxFilesize(), 'mb')));
			return false;
		}
		if ($_FILES['backup']['error']!=0)
		{
			$this->fp->sessionWrite( 'error_msg', sprintf(__('There was a problem, maybe the file is bigger than %1$s Mb. Please try it again','pressbackup'),$this->getByteSize($this->uploadMaxFilesize(), 'mb')));
			return false;
		}
		if ( !in_array($_FILES['backup']['type'], array('application/zip', 'application/x-zip-compressed', 'application/octet-stream')))
		{
			$this->fp->sessionWrite( 'error_msg', sprintf(__('Wrong file type: %1$s','pressbackup'),$_FILES['backup']['type']));
			return false;
		}
		if (!is_uploaded_file($_FILES['backup']['tmp_name']))
		{
			$this->fp->sessionWrite( 'error_msg', __('The file could not be uploaded correctly','pressbackup'));
			return false;
		}
		return true;
	}

	/**
	 * Check a backup integrity
	 *
	 * Check if the uploaded or geted backup
	 * can be opened without errors
	 */
	public function checkBackupIntegrity($zip_file)
	{
		if(!file_exists($zip_file)){
			$this->fp->sessionWrite( 'error_msg', __('file not found','pressbackup'));
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zip_file) !== TRUE)
		{
			$this->fp->sessionWrite( 'error_msg', __('Sorry, but the file is corrupt','pressbackup'));
			return false;
		}


		$i=0; $found = false;
		while($entry = $zip->getNameIndex($i)){
			if(in_array(basename($entry), array('database.sql', 'plugins.zip', 'themes.zip', 'uploads.zip') )){
				$found = true; break;
			}
			$i++;
		}
		$zip->close();

		if (!$found){
			$this->fp->sessionWrite( 'error_msg', __('Sorry, but the file is not a valid backup','pressbackup'));
			return false;
		}

		return true;
	}

	//------------------------------------------------------------------------------------

	public function mtimeLogFile($fileName)
	{
		return filemtime( $this->fp->path['LOGTMP']. DS. $fileName);
	}

	/**
	 * Check if a log file exists and return the path
	 */
	public function checkLogFile($fileName)
	{
		return file_exists( $this->fp->path['LOGTMP']. DS. $fileName);
	}

	/**
	 * return the content of a log file after check if it exist
	 */
	public function getLogFile($fileName)
	{
		if ( $this->checkLogFile($fileName) ) {
			return @file_get_contents($this->fp->path['LOGTMP'] . DS . $fileName);
		}
		return null;
	}

	/**
	 * Delete a log file after check if it exist
	 */
	public function deleteLogFile($fileName)
	{
		return  @unlink($this->fp->path['LOGTMP'] . DS . $fileName);
	}

	/**
	 * write a log file after check if it exist
	 */
	public function writeLogFile($fileName, $data)
	{
		@file_put_contents($this->fp->path['LOGTMP'] . DS . $fileName, $data);
	}

	/**
	 * Check the status of the backup now process
	 *
	 * This function its called via Ajax and inform what
	 * is the process doing. And most important when
	 * the process finish.
	 */
	public function backgroundStatus()
	{
		//for what task we are checking, download or save?
		$task = $_POST['task'];

		//default response
		$response = '{"action": "wait", "status": "fail"}';


		$status = $this->getLogFile('create.log');
		//its creating the backup
		if( $status && !$this->checkLogFile('sent.log') ) {

			//creation process fail
			if($status == 'fail') {
				$this->deleteLogFile('create.log');
				$response = '{"action": "finish", "status": "fail"}';
			}

			//creation process finished and must return the name of the file to download
			elseif($status == 'finish' && $task == 'download') {

				$fileToDownload = $this->getLogFile('create.return');
				$this->deleteLogFile('create.log');
				$this->deleteLogFile('create.return');
				$response = '{"action": "finish", "status": "ok", "response": { "file":"'.base64_encode($fileToDownload).'"}}';
			}

			//creation process finished and so next step is send the file
			elseif($status == 'finish' && $task == 'save') {

				$this->deleteLogFile('create.log');
				$response = '{"action": "wait", "status": "ok", "task_now": "'.__('Start sending backup','pressbackup').'"}';
			}

			//creation proccess is running. $status has the running task ('creating upload backup', etc)
			else{
				$response = '{"action": "wait", "status": "ok", "task_now": "'.$status.'"}';
			}
		}

		//its sending the backup
		elseif( $status = $this->getLogFile('sent.log') ) {

			//remove previous step log
			$this->deleteLogFile('create.log');

			//save process fail
			if($status == 'fail') {
				$this->deleteLogFile('sent.log');
				$this->deleteLogFile('sent.percent');
				$response = '{"action": "finish", "status": "fail"}';
			}

			//send process finished
			elseif($status == 'finish') {
				$this->deleteLogFile('sent.log');
				$this->deleteLogFile('sent.percent');
				$response = '{"action": "finish", "status": "ok"}';
			}

			//sending
			elseif ($taskPercent = $this->getLogFile('sent.percent')) {

				$percent= explode('|', $taskPercent);
				if(($percent[1] - $percent[0]) < 15000) {
					$task_now = __("Running post-upload tasks, this may take a moment",'pressbackup');
				}else{
					$task_now = __("Sending backup",'pressbackup')." - ".$this->getByteSize($percent[0], 'mb').' MB / '.$this->getByteSize($percent[1], 'mb').' MB';
				}
				$response = '{"action": "wait", "status": "percent", "task_now": "'.$task_now.'", "response":{ "total": "'.$percent[1].'", "current":"'.$percent[0].'"} }';
			}
		}

		return $response;
	}
}
?>
