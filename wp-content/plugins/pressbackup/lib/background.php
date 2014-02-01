<?php
	require_once 'SimpleCurl.php';

	if ( isset($argv) && isset($argc)  && $argc == 2 && file_exists($argv[1]) )	{

		$info = file_get_contents( $argv[1] );
		$info = base64_decode($info);
		$info = explode('__AYNIL__',$info);

		$curl = new SimpleCurlPBLib();
		//create backup file
		$args = array(
			'url' => $info[0],
			'cookie'=>$info[1],
			'timeout'=>3,
			'return.header'=>false,
			'return.body' => false,
			'parse.response' => false
		);
		$curl->call($args);
	}

?>
