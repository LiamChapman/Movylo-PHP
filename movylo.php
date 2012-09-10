<?php

/**
 * @class Movylo
 * @author Liam Chapman
 * @comments: Simple class to work with the Movylo API
 * @version 1.0
 * @status: work in progress!
 * @example: 
 *
 * $movylo = new Movylo($email, $password, $store_name);
 * if( $create = $movylo->request('create_store', 'store/msapiCreateStore') ) {
 * 		if( !$create->error ) {		
 * 			//DO STUFF
 *		} else {
 *			echo $create->error['message'];
 *		}//endif
 *	}//endif
 * 
 **/

class Movylo {

	private $time, $api_key, $shared_key, $type, $username, $password, $store_name;

	public function __construct($username, $password, $store_name, $env = 'development', $type = 'GET') {
		$this->time 		 = time();
		$this->api_user_id 	 = '';
		$this->shared_key	 = '';
		$this->username		 = $username;
		$this->password		 = $password;
		$this->store_name	 = trim(preg_replace('/\W+/', '-', $store_name)); //can't remember if you can use spaces :-S
		$this->env			 = $env;
		$this->type			 = $type;
	}
	
	public function store_check() {					
		$store_check  = '<?xml version="1.0"?>';
		$store_check .=  <<<REQUEST
						 <request timestamp="{$this->time}">
							 <store_name>{$this->store_name}</store_name>
						 </request>
REQUEST;
		return trim($store_check);
	}//end store_check
	
	public function create_store($api = 1) {
		$create  = '<?xml version="1.0"?>';
		$create .= <<<REQUEST
					<request timestamp="{$this->time}">
						<username>{$this->username}</username>
						<password>{$this->password}</password>
						<store_name>{$this->store_name}</store_name>
						<get_api_key>{$api}</get_api_key>
					</request>
REQUEST;
		return trim($create);
	}//end create_store

	public function authenticate() {
		$auth  = '<?xml version="1.0"?>';
		$auth .= <<<REQUEST
				 <request timestamp="{$this->time}">
				 	<username>{$this->username}</username>
					<password>{$this->password}</password>
				 </request>
REQUEST;
		return trim($auth);
	}//end authenticate

	public function hash($xml) {
		return hash_hmac('md5',$xml.$this->time,$this->shared_key);		
	}//end hash
	
	// e.g. $movylo->request('create_store', 'store/msapiCreateStore');
	public function request($method, $file=null) {
		switch($this->env) {
			case 'development':
				$http = 'http://';
				$url  = 'api.sandbox.movyloshop.com';
			break;

			case 'live':
				$http = 'https://';
				$url  = 'api.movyloshop.com';
			break;
		}//endswitch;			
		if(!is_null($file)) {		
			$api_call = $http . $url . '/'.$file.'.php'; # -> Build URL			
			if( $request = $this->curl($api_call, $this->{$method}() ) ) {	# -> Make Request		
				return $request;				
			} else {			
				return 'error';				
			}			
		} else {		
			return 'error';
		}//endif file null;
	}//end request
	
	public function curl($url, $data) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $this->api_user_id.":".$this->hash($data));
		return self::process( curl_exec($curl) );
		//curl_close() ?
	}//end curl;
	
	public function process($xml_response) {
		try {
			return new simpleXMLElement( trim( $xml_response ) );
		} catch(Exception $e) {
			return 'Caught exception: '.  $e->getMessage(). "\n";
		}
	}//end process

}
