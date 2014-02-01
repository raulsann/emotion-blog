<?php

/**
 * Settings Controller for Pressbackup.
 *
 * This Class provide a interface to display and manage Plugin settings
 * Also Manage the configurattion wizard
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link			http://pressbackup.com
 * @package			controlers
 * @subpackage		controlers.settings
 * @since			0.1
 * @license			GPL v2 License
 */

class PressbackupSettings
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
	 * by pressing on settings menu link
	 */
	public function index()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		if ($settings['configured']) {
			$pressbackup->redirect(array('function' => 'configSettings'));
		} else {
			$pressbackup->redirect(array('function' => 'wizardInit'));
		}
	}

	//----------------------------------------------------------------------------------------

	/**
	 * Start The configuration wizard
	 * Check all that all nedded libs are intalled
	 * Also check for perms on tmp folders
	 */
	public function wizardInit($show_email_field = false)
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Msg.php');
		$msg = new MsgPBLib();

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		//initials
		$error = array();
		$data = array();
		$show_page = 'welcome.for.registered.users';
		$need_to_update_preferences = false;
		$blog_url = get_bloginfo( 'wpurl' );

		//check for Zip Creation
		$shellzip = $misc->checkShell('zip');
		$phpzip = class_exists('ZipArchive');
		if (!$phpzip && !$shellzip) {
			$error['zip'] = __('PHP-ZIP Extension missing: You probably don\'t have the php-zip extension installed. You will need to contact your hosting provider and ask them to install it','pressbackup');
			$show_page = 'wizard.errors';
		}
		elseif (!$phpzip) {
			$settings['compatibility']['zip'] = 20;
			$need_to_update_preferences = true;
		}
		else {
			$settings['compatibility']['zip'] = 10;
			$need_to_update_preferences = true;
		}

		//Check for SSL
		$parsed_url = parse_url($blog_url);
		if($parsed_url['scheme'] == 'https'){
			$settings['compatibility']['background'] = 20;
			$need_to_update_preferences = true;
		}

		//Check tmp dir creation
		if (!file_exists($pressbackup->path['PBKTMP']) && !mkdir($pressbackup->path['PBKTMP'])) {
			$error['tmpdir'] = sprintf(__('We were unable to create the FileStore directory "%1$s" Please make sure the permissions are correct on this directory or its parent directory.','pressbackup'), $pressbackup->path['PBKTMP']);
			$show_page = 'wizard.errors';
		} else {
			@chmod($pressbackup->path['PBKTMP'], 0777);
		}

		//Check log dir creation
		if (!file_exists($pressbackup->path['LOGTMP']) && !mkdir($pressbackup->path['LOGTMP'])) {
			$error['logdir'] = sprintf(__('We were unable to create the FileStore directory "%1$s" Please make sure the permissions are correct on this directory or its parent directory.','pressbackup'), $pressbackup->path['LOGTMP']);
			$show_page = 'wizard.errors';
		} else {
			@chmod($pressbackup->path['LOGTMP'], 0777);
		}

		// Check for CURL
		if (!extension_loaded('curl')) {
			$error['curl'] = __('PHP-CURL Extension missing: You probably don\'t have the php-curl extension installed.  You will need to contact your hosting provider and ask them to install it for you','pressbackup');
			$show_page = 'wizard.errors';
		}

		if(!isset($error['curl'])) {
			//Check if user is member of pressbackup
			$srv = $misc->serviceObject('pressbackup', array(null, null));
			$res = $srv->auth();

			//Check if user need to create a account
			if($srv->response['code'] != '200'){
				$res_fail=@json_decode($srv->response['body'], true);

				if($res_fail['code'] == '1000') {
					$show_page = 'welcome.for.new.users';
				} else {
					$error['auth'] =  __($res_fail['msg'], 'pressbackup');
					$show_page = 'wizard.errors';
				}
			}

			//if all was ok then save user info
			else {
				$settings['membership']=$res['package'];
				$settings['Service']['pressbackup']['credential'] = base64_encode($res['username'] . '|AllYouNeedIsLove|' . $res['authkey']);
				$need_to_update_preferences = true;
			}

			// Check CURL local
			$pressbackup->import('SimpleCurl.php');
			$curl = new SimpleCurlPBLib();
			$curl->call(array(
				'url' => $blog_url,
				'parse.response' => false
			));
			if(!in_array($curl->response['code'], array(200, 301))) {
				$error['curl'] = sprintf(__('CURL Permission error: You probably don\'t have permission to run curl locally.  You will need to contact your hosting provider and ask them if "loopback HTTP requests are enabled.". Error code: %1$s','pressbackup'), $curl->response['code']);
				$show_page = 'wizard.errors';
			}

			//validate  Cron Jobs
			$checkCron = get_option('pressbackup.wizard.cronTaskStatus');
			//ya corrio el cron y no paso nada entonces el ajax lo pone como fail
			if ($checkCron == "fail") {
				//informamos al usuario del error (pero como warning y lo dejamos seguir)
				$warning_msg = __('Could not create a Cron Job Backup.', 'pressbackup');
				$msg->set('warning', $warning_msg);
				//actualizamos el valor a success para que no vuelva a hacer el chequeo
				update_option('pressbackup.wizard.cronTaskStatus', 'success');
				//para las futuras creaciones de process en bg, usamos tipo medium
				$settings['compatibility']['background'] = 20;
				$need_to_update_preferences = true;

			}
			//si no hay otros errores y todavia no se testeo el cron  checkear
			elseif ($show_page != 'wizard.errors' && $checkCron  != "success" ){

				$sch->add('+4 seconds', 'pressbackup.wizard.doCronTask');
				$pressbackup->viewSet('check_cron', true);
			}

			//get user info to create form
			$ud = get_userdata(get_current_user_id());
			if(isset($ud->data)){
				$username =  $ud->data->user_login;
				$user_email = $ud->data->user_email;
			} else {
				 $username = $ud->user_login;
				 $user_email = $ud->user_email;
			}

			//create default data to show in form
			$values = $default_values = array('User' =>array('username' => $username, 'email' => $user_email, 'password' => '', 'password_confirm' => '', 'first_name' => '', 'last_name' => ''));
			if($data = $pressbackup->sessionRead('createData')){
				$values['User'] = array_merge($default_values['User'], $data['User']);
				$pressbackup->sessionDelete('createData');
			}

			//set errors from user create
			if ($errors = $pressbackup->sessionRead('errors')) {
				foreach($errors as $e){ $msg->set('error', __($e, 'pressbackup')); }
				$pressbackup->sessionDelete('errors');
			}
		}

		//update preferences
		if($need_to_update_preferences){
			update_option('pressbackup.preferences', $settings);
		}

		//Alert the user about possible problems
		if (strpos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
			$warning_msg = __('Sorry your web Server (Windows IIS) is not compatible with Pressbackup. You are welcome to use Pressbackup, but we cannot provide any support or guarantees for your system. PressBackup is designed to run on Linux, BSD, and other Unix based systems','pressbackup');
			$msg->set('warning', $warning_msg);
		}

		if (ini_get('safe_mode')) {
			$warning_msg = __('Safe Mode is enabled. - You will need to contact your hosting provider and ask them if it is absolutely necessary for you to have safe_mode enabled. Most hosts should be able to disable it for you, or give you a good reason why it can\'t be disabled','pressbackup');
			$msg->set('warning', $warning_msg);
		}

		$pressbackup->viewSet('show_email_field', $show_email_field);
		$pressbackup->viewSet('data', $values);
		$pressbackup->viewSet('error', $error);
		$pressbackup->viewSet('show_page', $show_page);
	}

	/*
	 * CreateAccount via API
	 */
	public function createAccount ($type = null)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('pressbackup', array(null, null));

		$srv->createAccount($type);
		if($srv->response['code'] != '200'){
			$res=@json_decode($srv->response['body'], true);
			$pressbackup->sessionWrite('errors', $res['invalidFields']);
			$pressbackup->sessionWrite('createData', $_POST['data']);
		}

		$show_email_field = isset($res['invalidFields']['email']);
		$pressbackup->redirect(array('function' => 'wizardInit', $show_email_field));
	}

	/*
	 * CreateAccount via API
	 *  this function redirect to pressbackup site to can register this blog
	 */
	public function registerSite ()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('pressbackup', array(null, null));
		$srv->registerSite(); exit;
	}

	/**
	 * Store the credentials if they are correct
	 * or save the error and return to form page
	 */
	public function wizardSetDefaultService()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$settings['Service']['pressbackup']['enabled'] = true;

		update_option('pressbackup.preferences', $settings);

		$this->wizardSetDefaultSettings();
	}

	/**
	 * Store the settings if they are correct
	 * or save the error and return to form page
	 */
	private function wizardSetDefaultSettings()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$srv = $misc->serviceObject('pressbackup');

		$curdate = strtotime($misc->midnightUTC());
		$time = ($t =$srv->getLimit('time'))?$t:24;
		$copies = ($c =$srv->getLimit('copies'))?$c:array('pressbackup' => 7, 'others' => 7);
		$settings['backup']['db']= array('time' => $time, 'last_date' => $curdate);
		$settings['backup']['plugins']= array('time' => 24, 'last_date' => $curdate);
		$settings['backup']['themes']= array('time' => 24, 'last_date' => $curdate);
		$settings['backup']['uploads']= array('time' => 24, 'last_date' => $curdate);
		$settings['backup']['copies'] = $srv->getLimit('copies');

		$settings['configured'] = 1;
		$settings['show.updated.msg'] = false;

		update_option('pressbackup.preferences', $settings);

		//create a first new
		$sch->add('+3 seconds');

		//ignore settings and make a first backup with all
		$pressbackup->sessionWrite('backup.type', '7,3,5,1', true);

		//tell dash to load ajax to chk backup status
		$pressbackup->sessionWrite('dash.reload', 'dashboard');
		$pressbackup->sessionWrite('first.backup', true);

		//send info about the configuration and the selected services
		$services = $misc->currentService();
		$enabled_services = array(); foreach ($services as $s){ $enabled_services[] = $s['id'];  }
		$srv->pingConfigured($enabled_services, $misc->timezoneString());

		$pressbackup->redirect(array('controller'=>'main', 'function'=>'dashboard'));
	}

	//----------------------------------------------------------------------------------------

	public function wizardStartCronTask ()
	{
		update_option('pressbackup.wizard.cronTaskStatus', 'success');
	}

	public function wizardChkCronTaskStatus ()
	{
		$status = get_option('pressbackup.wizard.cronTaskStatus');

		$response = array('status'=> 'fail');
		if ($status == 'success') {
			$response = array('status'=> 'ok');
		}

		//clean output buffer
		ob_end_clean();

		//send json
		header('Status: 200 OK', true);
		header('HTTP/1.1 200 OK', true);
		header("Content-type: application/json", true);
		echo json_encode($response);
		exit;
	}

	//when wizardChkCronTaskStatus ajax response is X times 'fail'
	//this function must be called to set the status of the process
	//to fail
	public function wizardSetCronTaskStatusFail ()
	{
		global $pressbackup;

		update_option('pressbackup.wizard.cronTaskStatus', 'fail');
		$pressbackup->redirect(array('controller'=>'settings', 'function'=>'wizardInit'));
	}

	//----------------------------------------------------------------------------------------

	/**
	 * Show the form to modify the service:
	 * S3, Pressbackup Pro, localhost, Dropbox, etc
	 */
	public function configService()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Msg.php');
		$msg = new MsgPBLib();

		//set errors and data
		if ($error = $pressbackup->sessionRead('error_msg')) {
			$msg->set('error',$error);
			$data = $pressbackup->sessionRead('data');
			$pressbackup->sessionDelete('data');
			$pressbackup->sessionDelete('error_msg');
		}
		//if no error, display settings info
		else {
			$settings = get_option('pressbackup.preferences');
			$enabled_services = $misc->currentService();

			$dataService = $dataS3 = $dataLocal = array();
			foreach($enabled_services as $service){
				$dataService['service'][] = $service['id'];
				if ($service['id'] == 's3') {
					$credentials = explode('|AllYouNeedIsLove|', base64_decode( $service['credentials']));
					$dataS3['s3']['accessKey'] = $credentials[0];
					$dataS3['s3']['secretKey'] = $credentials[1];
					$dataS3['s3']['region'] = $service['region'];
				}
				if ($service['id'] == 'local') {
					$dataLocal['local']['path'] = $service['path'];
				}
			}
			$data = array_merge($dataService, $dataS3, $dataLocal);
		}

		$srv = $misc->serviceObject('pressbackup');
		$pressbackup->viewSet('data', $data);
		$pressbackup->viewSet('servicesLimit', $srv->getLimit('services'));
	}

	/**
	 * Store the modified credentials if they are correct
	 * or save the error and return to form page
	 */
	public function configServiceSave()
	{
		global $pressbackup;

		//Exit if wrong parameters
		if (!isset($_POST['data']) || !$this->checkFieldsService($_POST['data'])) {
			$pressbackup->sessionWrite('data', $_POST['data']);
			$pressbackup->redirect(array('function' => 'configService'));
		}

		$settings = get_option('pressbackup.preferences');

		$settings['Service']['pressbackup']['enabled'] = false;
		$settings['Service']['s3']['enabled'] = false;
		$settings['Service']['local']['enabled'] = false;
		$settings['Service']['dropbox']['enabled'] = false;

		if ( in_array('pressbackup', $_POST['data']['service']) ) {

			$settings['Service']['pressbackup']['enabled'] = true;
		}

		if (in_array('s3', $_POST['data']['service'])) {

			$PS3 = $_POST['data']['s3'];
			$settings['Service']['s3']['bucket_name'] = $PS3['bucketname'];
			$settings['Service']['s3']['region'] = $PS3['region'];
			$settings['Service']['s3']['credential'] = base64_encode($PS3['accessKey'] . '|AllYouNeedIsLove|' . $PS3['secretKey']);
			$settings['Service']['s3']['enabled'] = true;
		}

		if (in_array('local', $_POST['data']['service'])) {

			$PLOCAL = $_POST['data']['local'];
			$settings['Service']['local']['path'] = $PLOCAL['path'];
			$settings['Service']['local']['enabled'] = true;
		}

		update_option('pressbackup.preferences', $settings);

		//add schedule backup job, for users from versions <=1.5.1
		$pressbackup->import('Scheduler.php');
		$sch = new SchedulerPBLib();
		$sch->add();


		//send info about the configuration and the selected services
		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();
		$srv = $misc->serviceObject('pressbackup');
		$services = $misc->currentService();
		$enabled_services = array(); foreach ($services as $s){ $enabled_services[] = $s['id'];  }
		$srv->pingConfigured($enabled_services, $misc->timezoneString());


		$pressbackup->sessionDelete('dash.service');

		if (in_array('dropbox', $_POST['data']['service'])) {
			$srv = $misc->serviceObject('dropbox', array(null, null));
			$srv->auth(array('menu_type'=>'menu', 'controller' => 'settings', 'function' => 'dropboxAuthorize'));
		} else {
			$pressbackup->sessionWrite('general_msg', __('Service Saved!','pressbackup'));
			$pressbackup->redirect(array('controller'=> 'main', 'function' => 'dashboard'));
		}
	}

	/**
	 * Show the form to modify prefered settings
	 * as backup type, schedule time, etc
	 */
	public function configSettings()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$pressbackup->import('Msg.php');
		$msg = new MsgPBLib();

		if ($error = $pressbackup->sessionRead('error_msg')) {
			$msg->set('error',$error);
			$pressbackup->sessionDelete('error_msg');
		}

		$srv = $misc->serviceObject('pressbackup');

		//check that settings limits are correct
		$res = $srv->getLimit('time');
		if($srv->response['code'] != '200'){
			$res=@json_decode($srv->response['body'], true);
			$msg->set('error',__($res['msg'],'pressbackup'));
		}

		$pressbackup->viewSet('settings', get_option('pressbackup.preferences'));
		$pressbackup->viewSet('time', $res);
	}

	/**
	 * Store the modified settings if they are correct
	 * or save the error and return to form page
	 */
	public function configSettingsSave()
	{
		global $pressbackup;

		//exit if no data or invalid
		if (!isset($_POST['data']) || !$this->checkFieldsSettings($_POST['data'])) {
			$pressbackup->redirect(array('function' => 'configSettings'));
		}
		$data = $_POST['data']['backup'];

		$settings = get_option('pressbackup.preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('pressbackup');

		$time = $srv->getLimit('time');
		//time
		$curdate = strtotime($misc->midnightUTC());
		$settings['backup']['db']['time'] = ($data['db']['time'] == 0)? 0 : max($data['db']['time'], $time);
		$settings['backup']['plugins']['time'] = ($data['plugins']['time'] == 0)? 0 : max($data['plugins']['time'], $time);
		$settings['backup']['themes']['time'] = ($data['themes']['time'] == 0)? 0 : max($data['themes']['time'], $time);
		$settings['backup']['uploads']['time'] = ($data['uploads']['time'] == 0)? 0 : max($data['uploads']['time'], $time);

		update_option('pressbackup.preferences', $settings);

		$pressbackup->sessionWrite('general_msg', __('Preferences Saved!','pressbackup'));
		$pressbackup->redirect(array('controller'=> 'main', 'function' => 'dashboard'));
	}


	/**
	 * Show the form to modify prefered conpatibility settings
	 */
	public function configCompatibility()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$pressbackup->viewSet('settings', $settings);
	}

	/**
	 * Store the modified settings if they are correct
	 * or save the error and return to form page
	 */
	public function configCompatibilitySave()
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		if (isset($_POST['data']['compatibility']['background'])) {
			$settings['compatibility']['background'] = $_POST['data']['compatibility']['background'];
		}

		if (isset($_POST['data']['compatibility']['zip'])) {
			$settings['compatibility']['zip'] = $_POST['data']['compatibility']['zip'];
		}

		update_option('pressbackup.preferences', $settings);

		$pressbackup->sessionWrite('general_msg', __('Preferences Saved!','pressbackup'));
		$pressbackup->redirect(array('controller' => 'main', 'function' => 'dashboard'));
	}


	/**
	 * Store the settings if they are correct
	 * or save the error and return to form page
	 *
	 * @token_s string: token generated on revios step
	 */
	public function dropboxAuthorize($token_secret)
	{
		global $pressbackup;

		$settings = get_option('pressbackup.preferences');

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('dropbox', array(null, null));

		//get real tokens
		$token = $_GET['oauth_token'];
		$permanentTokens = $srv->getAccessToken($token, $token_secret);

		//store tokens
		$settings['Service']['dropbox']['credential'] = base64_encode($permanentTokens['oauth_token'] . '|AllYouNeedIsLove|' . $permanentTokens['oauth_token_secret']);
		$settings['Service']['dropbox']['enabled'] = true;

		update_option('pressbackup.preferences', $settings);

		if($settings['configured']){
			$pressbackup->sessionWrite('general_msg', __('Service Saved!','pressbackup'));
			$pressbackup->redirect(array('controller' => 'main', 'function' => 'dashboard'));
		} else {
			$this->wizardSetDefaultSettings();
		}
	}

	/**
	 * Check if credentials are valid
	 * for S3 or Pressbackup PRO account
	 *
	 * @args array: credentials sent via post
	 */
	public function checkFieldsService($args)
	{
		global $pressbackup;

		if(!isset($args['service'])) {
			$pressbackup->sessionWrite('error_msg', __('Please select at least one storage service','pressbackup'));
			return false;
		}

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('pressbackup', array(null, null));

		$res = $srv->getLimit('services');
		if($srv->response['code'] != '200'){
			$res=@json_decode($srv->response['body'], true);
			$pressbackup->sessionWrite('error_msg', __($res['msg'],'pressbackup'));
			return false;
		}


		if(count($args['service']) > $res) {
			$pressbackup->sessionWrite('error_msg', __('Please upgrade your membership to allow multiple storage services','pressbackup'));
			return false;
		}

		if (in_array('s3', $args['service'])) {
			$PS3 = $args['s3'];

			//check empty data
			if (!isset($PS3['accessKey']) || empty($PS3['accessKey']) || !isset($PS3['secretKey']) || empty($PS3['secretKey'])) {
				$pressbackup->sessionWrite('error_msg', __('Empty Fields, please ty it again','pressbackup'));
				return false;
			}

			$srv = $misc->serviceObject('s3', array($PS3['accessKey'], $PS3['secretKey']));
			$buckets = $srv->listBuckets();

			//check if credentials are right
			if ($buckets === false) {
				$pressbackup->sessionWrite('error_msg', __('Incorrect S3 access or secret Keys ','pressbackup') . '<br>Amazon S3 message: ' .$srv::$error['message']);
				return false;
			}

			//check if bucket exists
			$the_bucket = $pressbackup->config['S3.bucketname'] . '-' . md5(uniqid(rand(), true));
			$create_bucket = true;

			for ($i = 0; $i < count($buckets); $i++) {
				if (strpos($buckets[$i], $pressbackup->config['S3.bucketname']) !== false) {
					$the_bucket = $buckets[$i];
					$create_bucket = false;
					break;
				}
			}

			//create bucket if it not exist
			$region = ($PS3['region'] == 'EU') ? 'EU' : false;
			if ($create_bucket && !$srv->putBucket($the_bucket, ServiceS3PBLib::ACL_PRIVATE, $region)) {
				$pressbackup->sessionWrite('error_msg', __("Unable to create a bucket on S3: Service temporarily unavailable. Please try it again later.",'pressbackup'));
				return false;
			}

			$_POST['data']['s3']['bucketname'] = $the_bucket;
			$_POST['data']['s3']['region'] = $region;
		}


		if (in_array('local', $args['service'])) {

			$PLOCAL = $args['local'];

			$srv = $misc->serviceObject('local', null);


			if (!$srv->checkExists($PLOCAL['path'])) {
				$pressbackup->sessionWrite('error_msg', __("The specified folder does not exist.",'pressbackup'));
				return false;
			}

			if (!$srv->checkPermissions($PLOCAL['path'])) {
				$pressbackup->sessionWrite('error_msg', __("No write permissions for the selected folder",'pressbackup'));
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if fields for Settings are valid.
	 * The files are type of backup, schedule time, etc
	 *
	 * @args array: all fields sent via post
	 */
	public function checkFieldsSettings($args)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		$srv = $misc->serviceObject('pressbackup');

		//check that settings limits are correct
		$res = $srv->getLimit('time');
		if($srv->response['code'] != '200'){
			$res=@json_decode($srv->response['body'], true);
			$pressbackup->sessionWrite('error_msg', __($res['msg'],'pressbackup'));
			return false;
		}

		$at_least_one = false;

		//check that all times received they are digit, and that at least one is activated,
		foreach($args['backup'] as $type=>$data){
			if (isset($data['time']) && !preg_match("/^[[:digit:]]+$/", $data['time'])) {
				$pressbackup->sessionWrite('error_msg', __('Specify the scheduled backup time range','pressbackup'));
				return false;
			}
			if($data['time'] != 0){
				$at_least_one = true;
			}
		}

		if(!$at_least_one){
			$pressbackup->sessionWrite('error_msg', __('Specify what you would like to backup','pressbackup'));
			return false;
		}

		return true;
	}

	public function removeUpdateMsg()
	{
		global $pressbackup;

		$settings= get_option('pressbackup.preferences');
		$settings['show.updated.msg'] = false;
		update_option('pressbackup.preferences',$settings);
		//reload page
		$pressbackup->redirect($_SERVER['HTTP_REFERER']);
	}

	public function removePrefixMsg()
	{
		global $pressbackup;

		$settings= get_option('pressbackup.preferences');
		unset($settings['show.prefix.msg']);
		update_option('pressbackup.preferences',$settings);
		//reload page
		$pressbackup->redirect($_SERVER['HTTP_REFERER']);
	}

}
?>
