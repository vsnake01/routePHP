<?php

/**
COMMAND 
WVER master=masterpass
ANSWER
OK

COMMAND
WACC_CREATE master=masterpass|name=John Charalambous|group=2
ANSWER
OK 2088375823|$

COMMAND
WACC_CREATE master=masterpass|name=John 
Charalambous|group=2|country=Cyprus|city=limassol|state=CY|address=Avstralis3|email=charalam
bousyiannis@yahoo.gr 
ANSWER
OK 2088375824|$

COMMAND 
WACC_READ master=masterpass|login=2088375833
ANSWER
login=2088375833|group=TTC-MS_05_1_EU|enable=1|enable_read_only=0|name=John 
Charalambous|country=Cyprus|city=limassol|state=CY|zipcode=|address=Avstralis3|phone=|email=
charalambousyiannis@yahoo.gr|status=|regdate=|leverage=200|balance=0.000000|agent_account=
0|$ 

COMMAND
WACC_UPD master=masterpass|login=2088375833| name=John Charalambous|country=Italy
ANSWER
OK 2088375833|$

COMMAND
WBAL_CHG master=masterpass|login=2088375833|amount=200
ANSWER
OK 2088375833|$

WACC_READ master=masterpass|login=2088375833
login=2088375833|group=TTC-MS_05_1_EU|enable=1|enable_read_only=0|name=John 
Charalambous|country=Italy|city=limassol|state=CY|zipcode=|address=Ikoniou12|phone=999999|e
mail=prostoixima@gmail.com|status=|regdate=|leverage=200|balance=200.000000|agent_account= COMMAND
WBAL_CHG master=masterpass|login=2088375833|amount=-200
ANSWER
OK 2088375833|$

WACC_READ master=masterpass|login=2088375833
login=2088375833|group=TTC-MS_05_1_EU|enable=1|enable_read_only=0|name=John 
Charalambous|country=Italy|city=limassol|state=CY|zipcode=|address=Ikoniou12|phone=999999|e
mail=prostoixima@gmail.com|status=|regdate=|leverage=200|balance=000.000000|agent_account= 

OTHER INFORMATIONS
masterpass  (Required)
group (Required command WACC_CREATE) 
*/
class Platform_v2 extends Config
{
	private $logger;
	private $servers = null;
	private $hosts = null;
	private $transports = null;
	
	const BALANCE = 'balance';
	
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
		$this->logger->error (__METHOD__ . ": $server $login $amount");
		
		// Check balance before operation
		$balance = $this->checkBalance($server, $login);
		
		if ($balance === false) {
			$this->logger->error (__METHOD__ . ": Can not check balance first time");
			return false;
		}
		
		if (doubleval($balance) + doubleval($amount) < 0) {
			$this->logger->error (__METHOD__ . ": Balance should not be null: " . $ret[self::BALANCE] . '+' . $amount);
			return false;
		}
		
		$params = array (
			'header' => 'WBAL_CHG',
			'params' => array (
				'login' => $login,
				'amount' => doubleval($amount)
			)
		);
		
		// TODO: обработка ошибки
		$res = $this->sendQuery($server, $params);
		
		if (!$res) {
			$this->logger->error (__METHOD__ . ": Error send query");
			return false;
		}
		
		if (!isset($res['status'])) {
			$this->logger->error (__METHOD__ . ": Wrong answer from query: ". var_export($res, true));
			return false;
		}
		
		if ($res['status'] != 'ok') {
			$this->logger->error (__METHOD__ . ": Wrong status from query: ". var_export($res, true));
			return false;
		}
		
		$balance = $this->checkBalance($server, $login);
		
		if ($balance === false) {
			$this->logger->error (__METHOD__ . ": Wrong answer from check balance second time");
			return false;
		}
		
		return $balance;
	}
	
	public function checkBalance($server, $login)
	{
		$params = array (
			'header' => "WACC_READ",
			'params' => array (
				'login' => $login,
			)
		);
		
		// TODO: обработка ошибки
		$res = $this->sendQuery($server, $params);
		
		if (!$res) {
			$this->logger->error (__METHOD__ . ": Empty response from query");
			return false;
		}
		
		if (!isset($res[self::BALANCE])) {
			$this->logger->error (__METHOD__ . ": Wrong response from query: ".var_export($res, true));
			return false;
		}
		
		return $res[self::BALANCE];
	}
	
	private function sendQuery($server, $params)
	{
		// We have Telnet API only
		// Make query string
		
		$res = null;
		
		if ($this->servers[$server] == 'telnetAPI') {
			// make query
			$query = $params['header']." master={$this->transports['telnetAPI']['password']}";
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
				$ret .= trim($line);
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
		
		foreach ($ret as $params) {
			if (preg_match ('/^OK\s/', $params)) {
				continue;
			}
			$p = explode('=', $params);
			if (isset ($p[1])) {
				$out[$p[0]] = $p[1];
			}
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
						'header' => "WACC_CREATE",
						'params' => array (
							'group' => '1',
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
