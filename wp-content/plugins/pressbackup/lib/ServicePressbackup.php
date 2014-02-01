<?php


class ServicePressbackupPBLib
{
	private $API_URL = 'https://pressbackup.com/pro';

	private $API_VERSION = '2';

	private $authKey = null;

	/**
	 * Local Copy of SimpleCurl Lib
	 *
	 * @var object
	 * @access public
	 */
	private $Curl = null;

	/**
	 * Local Copy of SimpleCurl response for last call
	 *
	 * @var object
	 * @access public
	 */
	public $response = null;

	/**
	 * Constructor.
	 *
	 * @param object FramePress Core
	 * @access public
	 */
	public function __construct($credentials = null )
	{
		global $pressbackup;

		$pressbackup->import('SimpleCurl.php');
		$this->Curl = new SimpleCurlPBLib();

		$this->setAuth($credentials);
	}

	//----------------------------------------------------------------------------------------

	public function auth()
	{
		return $this->call(array(
			'url' => $this->apiURL('AUTH'),
			'parse.response' => 'json',
		));
	}

	public function authToken($token='')
	{
		return $this->call(array(
			'url' => $this->apiURL('AUTH.TOKEN'),
			'parse.response' => false,
			'post' => array('token'=> $token),
		));
	}

	public function checkPutFile($file)
	{
		return $this->call(array(
			'url' => $this->apiURL('CHKPUTBACKUP'),
			'parse.response' => 'json',
			'post' => array('size' => filesize($file)),
		));
	}

	public function putFile($file)
	{
		//fix for no occidental characers, basename and pathinfo remove them from $file
		$pathinfo = explode(DS, $file);
		$name = array_pop($pathinfo);

		if(!$res = $this->checkPutFile($file)) {
			return false;
		}

		if(!isset($res['check']) || $res['check'] != 'pass'){
			return false;
		}

		return $this->call(array(
			'url' => $this->apiURL('PUTBACKUP'),
			'parse.response' => false,
			'upload.file.PUT' => $file,
			'header' => array(
				'Content-Type: application/zip',
				'File-Name: '.base64_encode($name)
			),
		));
	}

	public function getFile($file, $saveon)
	{
		return $this->call(array(
			'url' => $this->apiURL('GETBACKUP'),
			'save.output.in' => $saveon,
			'post' => array('file' => $file),
		));
	}

	public function getFile2($file)
	{
		return $this->call(array(
			'url' => $this->apiURL('GETBACKUP2'),
			'parse.response' => 'json',
			'post' => array('file' => $file),
		));
	}

	public  function deleteFile($file)
	{
		return $this->call(array(
			'url' => $this->apiURL('DELETEBACKUP'),
			'parse.response' => false,
			'post' => array('file' => $file),
		));
	}

	public function getFilesList()
	{
		return $this->call(array(
			'url' => $this->apiURL('GETBACKUPSLIST'),
			'parse.response' => 'json',
		));
	}

	public function getLimit($limit = null)
	{
		$res = $this->call(array(
			'url' => $this->apiURL('GETLIMIT'),
			'parse.response' => 'json',
			'post' => array('limit_type' => $limit),
		));
		return $res[$limit];
	}

	public function getPromotion($size = '0', $from= null)
	{
		$res = $this->call(array(
			'url' => $this->apiURL('GETPROMOTION'),
			'parse.response' => 'json',
			'post' => array('size' => $size, 'from' =>$from),
			'timeout' => 3
		));

		if($this->response['code'] != '200'){
			return array(
				'url' => 'https://pressbackup.com/pro/account/before_upgrade/pro/2',
				'text' => 'Upgrade to PRO now!',
			);
		}

		return $res;
	}

	public function createAccount($type = null)
	{
		$settings= get_option('pressbackup.preferences');

		$ud=get_userdata(get_current_user_id());

		if(isset($ud->data)){$userEmail = $ud->data->user_email;}else{$userEmail = $ud->user_email;}

		if(isset($_POST['data']['User']['email']) && !empty($_POST['data']['User']['email'])){ $userEmail = $_POST['data']['User']['email'];}

		$info = array(
			'User' => array(
				'username' => $_POST['data']['User']['username'],
				'password' => md5($_POST['data']['User']['password']),
				'first_name' => $_POST['data']['User']['first_name'],
				'last_name' => $_POST['data']['User']['last_name'],
				'email' => $userEmail,
			),
			'Site' => array (
				'title' => get_bloginfo('name'),
			),
			'Plugin' => array(
				'version' => $settings['version'],
			)
		);

		return $this->call(array(
			'url' => $this->apiURL('CREATEUSER'),
			'parse.response' => 'raw',
			'post' => array('info' =>base64_encode(json_encode($info))),
		));
	}

	public function registerSite ()
	{
		global $pressbackup;

		$pressbackup->redirect($this->API_URL . DS . 'sites' . DS . 'register');
	}

	public function pingInstalled()
	{
		return $this->call(array(
			'url' => $this->apiURL('PING.INSTALL'),
			'parse.response' => false,
		));
	}

	public function pingConfigured($enabled_services, $timezone, $version = null)
	{
		$settings= get_option('pressbackup.preferences');
		$enabled_services = join(',', $enabled_services);

		$ver = (!$version)?$settings['version']: $version;

		return $this->call(array(
			'url' => $this->apiURL('PING.CONFIG'),
			'parse.response' => false,
			'post'=>array('services' => $enabled_services, 'time_zone' => $timezone, 'version' =>  $ver),
		));
	}

	public function pingStart($action = null)
	{
		return $this->call(array(
			'url' => $this->apiURL('PING.START'),
			'parse.response' => false,
			'post'=>array('action' => $action),
			'timeout' => 4,
		));
	}

	public function pingStarted($enabled_services, $timezone)
	{
		$settings= get_option('pressbackup.preferences');
		$enabled_services = join(',', $enabled_services);

		return $this->call(array(
			'url' => $this->apiURL('PING.STARTED'),
			'parse.response' => false,
			'post'=>array(
				'services' => $enabled_services,
				'time_zone' => $timezone,
				'version' => $settings['version']
			),
		));
	}

	public function pingCreated($file)
	{
		$settings= get_option('pressbackup.preferences');

		return $this->call(array(
			'url' => $this->apiURL('PING.CREATED'),
			'parse.response' => false,
			'post'=>array(
				'size'=>filesize($file),
				'version' => $settings['version']
			),
		));
	}

	public function pingSaved($enabled_services, $file)
	{
		$enabled_services = join(',', $enabled_services);
		return $this->call(array(
			'url' => $this->apiURL('PING.SAVED'),
			'parse.response' => false,
			'post'=>array('services' => $enabled_services, 'size'=>filesize($file)),
		));
	}

	public function pingRemoved()
	{
		return $this->call(array(
			'url' => $this->apiURL('PING.REMOVED'),
			'parse.response' => false,
		));
	}

	public function ping()
	{
		//this ping was removed and new ones were added in replacement
		return true;
	}

	//----------------------------------------------------------------------------------------

	public function call ($args = array())
	{
		$def_headers = array(
			$this->gHeader('auth'),
			$this->gHeader('from'),
			$this->gHeader('date'),
		);

		if(!isset($args['header'])){
			$args['header'] = $def_headers;
		}else {
			$args['header'] = array_merge($def_headers, $args['header']);
		}

		$args['user-agent'] = $this->userAgent();

		$res = $this->Curl->call($args);
		$this->response = $this->Curl->response;
		return $res;
	}

	public function setAuth( $credentials = null)
	{
		if (!$credentials[0] || !$credentials[1] ){ return true; }
		$this->authKey=base64_encode($credentials[0].','.$credentials[1]);
	}

	private function apiURL( $action = null )
	{
		return $this->API_URL . '/api/' . $this->API_VERSION . '/' . $action;
	}

	private function gHeader( $type = null )
	{
		if ($type == 'from'){ return 'From: '.get_bloginfo( 'wpurl' ); }
		if ($type == 'auth' && $this->authKey){ return 'Auth: '.$this->authKey; }
		if ($type == 'date'){ return 'Date: '.date(DATE_ATOM); }
		return 'Forgot: toPassType';
	}

	private function userAgent()
	{
		return 'Presbackup/plugin/service/PHP '.get_bloginfo( 'wpurl' );
	}
}
