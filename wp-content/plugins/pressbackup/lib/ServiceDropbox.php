<?php

class ServiceDropboxPBLib
{
	/**
	 * Local Copy of SimpleCurl Lib
	 * 
	 * @var object
	 * @access public
	 */
	private $Curl = null;

	/**
	 * Local Copy of OAuth Lib
	 * 
	 * @var object
	 * @access public
	 */
	private $OAuth = null;

	/**
	 * Constructor.
	 *
	 * @param object FramePress Core
	 * @param array credentias user tokens for dropbox api
	 * @access public
	 */
	public function __construct( $credentials = null )
	{
		global $pressbackup;

		$pressbackup->import('SimpleCurl.php');
		$this->Curl = new SimpleCurlPBLib();

		$pressbackup->import('OAuth.php');
		$this->OAuth = new OAuthPBLib( "ri8fmrz5q9h6tlb","m7zcjk11doevbkj" );

		if ($credentials){
			$this->OAuth->setTokens($credentials[0], $credentials[1]);
		}
	}

	//callback  is an array that indicate where to redirect after authenticate 
	public function auth ( $callback = array() )
	{
		global $pressbackup;

		$url = "https://api.dropbox.com/1/oauth/request_token";
		$url_step2 = "https://www.dropbox.com/1/oauth/authorize";

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		$res =  $this->Curl->call(array(
			'url' => $url,
			'post' => "",
			'parse.response' => 'raw',
			'header' => array(
				'Content-Length: 0',
				'Authorization: '.$headers
			)
		));

		//parse & obtain the tokens on $tokens array
		$res = explode("&",$res);

		$tokens_aux = explode("=",$res[0]);
		$tokens['oauth_token_secret'] = $tokens_aux[1];

		$tokens_aux = explode("=",$res[1]);
		$tokens['oauth_token'] = $tokens_aux[1];

		//make the url to call
		$callback[0] = $tokens['oauth_token_secret'];
		$rawcallback = $pressbackup->router($callback);
		$rawcallback = urlencode(str_replace("&amp;","&",$rawcallback));

		$pressbackup->redirect($url_step2."?oauth_token=".$tokens['oauth_token']."&oauth_callback=".$rawcallback);
	}

	public function getAccessToken($oauth_token=null,$oauth_token_secret=null)
	{
		$url = "https://api.dropbox.com/1/oauth/access_token";

		//set provisional tokens
		$this->OAuth->setTokens($oauth_token,$oauth_token_secret);

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		$res =  $this->Curl->call(array(
			'url' => $url,
			'post' => "",
			'parse.response' => 'raw',
			'header' => array(
				'Content-Length: 0',
				'Authorization: '.$headers
			)
		));

		//parse & obtain the tokens on $tokens array
		$res = explode("&",$res);

		$tokens_aux = explode("=",$res[0]);
		$tokens['oauth_token_secret'] = $tokens_aux[1];

		$tokens_aux = explode("=",$res[1]);
		$tokens['oauth_token'] = $tokens_aux[1];

		//set permanent tokens
		$this->OAuth->setTokens($tokens['oauth_token'],$tokens['oauth_token_secret']);

		return $tokens;
	}

	public function getInfo(){

		$url = "https://api.dropbox.com/1/account/info";

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("GET", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		return $this->Curl->call(array(
			'url' => $url,
			'parse.response' => 'json',
			'header' => array( 'Authorization: '.$headers )
		));
	}

	public function putFile($file)
	{
		//fix for no occidental characers, basename and pathinfo remove them from $file
		$pathinfo = explode(DS, $file);
		$name = array_pop($pathinfo);
		$name = urlencode($name);

		$url = "https://api-content.dropbox.com/1/files_put/sandbox/".$name;

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("PUT", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		return $this->Curl->call(array(
			'url' => $url,
			'upload.file.PUT' => $file,
			'parse.response' => 'json',
			'header' => array( 'Authorization: '.$headers )
		));
	}

	public function getFile($name, $saveon)
	{
		$url = "https://api-content.dropbox.com/1/files/sandbox/".urlencode($name);

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("GET", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		return $this->Curl->call(array(
			'url' => $url,
			'save.output.in' => $saveon,
			'header' => array( 'Authorization: '.$headers )
		));
	}

	public function deleteFile($file)
	{
		$url = "https://api.dropbox.com/1/fileops/delete";

		$this->OAuth->init();

		$this->OAuth->setParams("root","sandbox");
		$this->OAuth->setParams("path",$file);

		$oauth_signature = $this->OAuth->calcSignature("POST", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();
		$post = $this->OAuth->getPosBody();

		return $this->Curl->call(array(
			'url' => $url,
			'post' => $post,
			'header' => array( 'Authorization: '.$headers )
		));
	}

	public function getFilesList()
	{
		$url = "https://api.dropbox.com/1/metadata/sandbox/";

		$this->OAuth->init();

		$oauth_signature = $this->OAuth->calcSignature("GET", $url);
		$this->OAuth->setValues('oauth_signature', $oauth_signature);

		$headers = $this->OAuth->makeHeader();

		return $this->Curl->call(array(
			'url' => $url,
			'parse.response' => 'json',
			'header' => array( 'Authorization: '.$headers )
		));
	}

}




?>
