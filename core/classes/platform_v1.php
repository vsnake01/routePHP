<?php

/**
 * Description of platform
 * WUSERREAD|login=2088375613
 * WUSERCREATE|group=TTC-000_03_1_US|name=Valentin V Balt|password=25069489|investor=59276900|email=valentin.balt@gmail.com|country=CY|state=default|city=default|address=default|comment=dummy|phone=96403487|phone_password=62545748|zipcode=default|id=27|leverage=100|agent=default|send_reports=1
 * WCHANGEBALANCE|login=2088375621|amount=100
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Platform_v1 extends Config
{
	private $logger;
	private $servers = null;
	private $hosts = null;
	private $transports = null;
	
	public function __construct() {
		parent::__construct();
		
		$this->logger = new Logger(get_class());
		
		$this->servers = $this->getConfig('servers');
		$this->hosts = $this->getConfig('hosts');
		$this->transports = $this->getConfig('transports');
	}
	
	public function create($server, $fields)
	{
		
		$params = $this->getDummyData($this->servers[$server], 'create');
		
		foreach ($params['params'] as $k=>$v) {
			if (isset($fields[$k])) {
				$params['params'][$k] = $fields[$k];
			}
		}
		
		$ret = $this->sendQuery($server, $params);
		
		if ($ret['status'] != 'ok') {
			return false;
		}
		
		return $ret['value'];
	}
	
	public function update($server, $login, $params)
	{
		
	}
	
	public function balance($server, $login, $amount)
	{
		$params = array (
			'header' => 'WCHANGEBALANCE',
			'params' => array (
				'login' => $login,
				'amount' => doubleval($amount)
			)
		);
		
		// TODO: обработка ошибки
		$res = $this->sendQuery($server, $params);
		
		if (!$res) {
			return false;
		}
		
		if ($res['status'] != 'ok') {
			return false;
		}
		
		return $res['value'];
	}
	
	public function checkBalance($server, $login)
	{
		$params = array (
			'header' => 'WUSERREAD',
			'params' => array (
				'login' => $login,
			)
		);
		
		// TODO: обработка ошибки
		$res = $this->sendQuery($server, $params);
		
		if (!$res) {
			return false;
		}
		
		if ($res['status'] != 'ok') {
			return false;
		}
		
		return $res['value'];
	}
	
	private function sendQuery($server, $params)
	{
		// We have Telnet API only
		// Make query string
		
		$res = null;
		
		if ($this->servers[$server] == 'telnetAPI') {
			// make query
			$query = $params['header'];
			foreach ($params['params'] as $k=>$v) {
				//$k = strtoupper($k);
				if (!$v) {
					continue;
				}
				$query .= "|$k=$v";
			}
			$res = $this->sendTelnetAPI($this->hosts[$server], $this->transports['telnetAPI']['port'], $query);
		}
		
		if (!isset($res['status'])) {
			$res['status'] = 'error';
		}
		
		return $res;
	}
	
	private function sendTelnetAPI($host, $port, $query)
	{
		$errno = $errstr = '';
		$this->logger->error ("Connect to telnet API: $host:$port");
		$c = @fsockopen($host, $port, $errno, $errstr, 15);
		
		if (!$c){
			$this->logger->error ("Can not open connection to telnet API: $host:$port");
			return false;
		}
		
		$this->logger->debug ("Send query to telnet API: $query");
		
		$ret = '';
		if ( fwrite($c, "$query\nQUIT\n" ) !== false ) {
			while ( !feof($c) ) {
				$line=fgets($c, 128);
				$ret .= chop($line)."\n";
				$this->logger->debug ("Get some data from telnet API: $line [" . strlen($line).']');
			} 
		} else {
			$this->logger->debug ("Error write data to telnet API");
		}
		fclose($c);
		
		$this->logger->debug ("Get answer from telnet API: $ret");
		
		// We need to return array of params
		// We expect here something like 
		// RESULT bla=bla
		
		$out = array();
		
		
		$ret = explode("$", $ret);
		$ret = explode('|', $ret[0]);
		$st = explode(' ', $ret[0]);
		
		if ($st[0] == 'OK') {
			$out['status'] = 'ok';
			$out['value'] = $st[1];
		} else {
			$out['status'] = 'error';
			$out['value'] = $ret[0];
		}

		$this->logger->debug ("Return formatted answer from telnet API: ". print_r ($out, true));
		return $out;
	}
	
	private function getDummyData($proto, $action)
	{
		if ($proto == 'telnetAPI') {
			if ($action == 'create') {
				return 
					array (
						//'header' => "WNEWACCOUNT MASTER={$this->transports['telnetAPI']['password']}|IP=127.0.0.1",
						'header' => "WUSERCREATE",
						'params' => array (
							'group' => 'TTC-000_03_1_US',
							'name' => 'Client',
							'password' => rand(11111111,99999999),
							'investor' => rand(11111111,99999999),
							'email' => 'info@example.org',
							'country' => 'cy',
							'state' => 'default',
							'city' => 'default',
							'address' => 'default',
							'comment' => 'dummy',
							'phone' => 'default',
							'phone_password' => rand(11111111,99999999),
							'status' => '',
							'zipcode' => 'default',
							'id' => '',
							'leverage' => '1',
							'agent' => 'default',
							'send_reports' => '1',
							'deposit' => '0',
						)
				);
			}
		}
		
		return null;
	}
}
