<?php

class whmcsApiClient {
	private $connection=null;
	private $url = null;
	private $is_logged = false;
	private $debug = false;
	private $api_identifier = null;
	private $api_secret = null;
	
	function __construct($whmcs_url, $api_identifier, $api_secret) {
		$this->api_identifier=$api_identifier;
		$this->api_secret=$api_secret;
		$this->url=$whmcs_url.'/includes/api.php';
	}
	
	public function call($api_call, $array=array(), $response_type='json') {
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => $api_call,
			'responsetype' => $response_type,
		);
		$postfields=array_merge($postfields, $array);
		
		// Call the API
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
		$response = curl_exec($ch);
		if (curl_error($ch)) {
			die('Unable to connect: ' . curl_errno($ch) . ' - ' . curl_error($ch));
		}
		curl_close($ch);

		// Decode response
		$jsonData = json_decode($response, true);

		// Dump array structure for inspection
// 		var_dump($jsonData);
		return $jsonData;
	}
}
