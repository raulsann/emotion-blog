<?php

class OAUthPBLib
{
	private $oauth_params = array();

	private $oauth_values = array();

	private $keys = array('consumer_key'=>null, 'shared_secret'=>null);

	private $tokens = null;

	public function __construct($consumer_key = null, $shared_secret = null)
	{
		$this->keys['consumer_key'] = $consumer_key;
		$this->keys['shared_secret'] = $shared_secret;
	}

	public function setTokens($oauthToken, $oauthTokenSecret)
	{
		$this->tokens = array('oauth_token'=>$oauthToken, 'oauth_token_secret'=>$oauthTokenSecret);
	}

	public function init()
	{
		date_default_timezone_set('UTC');

		$timestamp = strtotime("now");
		$nonce = $timestamp + $this->randomFloat();

		//load default values
		$this->oauth_params =array();

		$this->oauth_values = array(
			"oauth_nonce|" . $nonce,
			"oauth_signature_method|" . "HMAC-SHA1",
			"oauth_timestamp|" . $timestamp,
			"oauth_consumer_key|" . $this->keys['consumer_key'],
			"oauth_version|" . "1.0"
		);

		if($this->tokens ) {
			$a_pair = array_shift($this->oauth_values);
			array_push($this->oauth_values, 'oauth_token' . '|' . $this->tokens['oauth_token']);
			array_push($this->oauth_values, $a_pair);
		}
	}

	public function setValues($key, $value)
	{
		//set the pair name|value
		$a_pair= array_shift($this->oauth_values);
		array_push($this->oauth_values, $key.'|'.$value);
		array_push($this->oauth_values, $a_pair);
	}

	public function setParams($key, $value)
	{
		array_push($this->oauth_params, rawurlencode($key).'|'.rawurlencode($value));
	}

	public function calcSignature($method, $url)
	{
		// read RFC 5849 (oAuth 1.0)
		// http://tools.ietf.org/html/rfc5849#section-3.4.1

		//url without get params
		$url_aux = explode('?',$url);
		$urlWithoutParams = $url_aux[0];

		//oauth token secret (if user is loged-in)
		$ots = '';
		if($this->tokens) {
			$ots = $this->tokens['oauth_token_secret'];
		}

		//first base string setings method & base string URI
		$base_string = array ($method, urlencode($urlWithoutParams)); 

		//Request Parameters
		$params = array_merge($this->oauth_params, $this->oauth_values); 
		sort($params);
		for($i=0; $i< count($params); $i++){
			$params[$i] = str_replace("|","=",$params[$i]);
		}
		$params = implode("&",$params);
		$params = urlencode($params);

		//add request parameters to base string
		array_push($base_string, $params);
		$base_string = implode("&",$base_string);

		//use HMAC-SHA1 to encript it 
		$signature = $this->hmac_sha1($this->keys['shared_secret']."&".$ots, $base_string);
		$signature = base64_encode($signature);

		return $signature;
	}

	public function makeHeader(){

		//get values
		$values = $this->oauth_values;

		//make it as name="value"
		for($i =0; $i< count($values); $i++)
		{
			$a_pair = explode("|",$values[$i]);
			$a_pair[1]= '"' . rawurlencode($a_pair[1]) .  '"';
			$values[$i] = implode('=',$a_pair);
		}

		//add info
		$values[0]='OAuth ' . $values[0];

		//make header
		$heather = implode(', ',$values);
		return $heather;
	}

	public function getPosBody(){
		$result = array();

		$params = $this->oauth_params;
		for($i=0; $i< count($params); $i++) 
		{
			$a_pair = explode("|",$params[$i]);

			$result[$a_pair[0]] = $a_pair[1];

			$params[$i] = implode('=',$a_pair);
		}

		ksort($result);

		$params = implode('&',$params);

		return $params;
	}


	private function randomFloat($min = 0, $max = 1)
	{
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}

	private function hmac_sha1($key, $data)
	{
		return pack('H*', sha1(
		(str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad($key, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $data))));
	}


}



?>
