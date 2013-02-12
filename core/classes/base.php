<?php

/**
 * Description of base
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Base extends Config
{
	protected $logger = null;
	protected $dbo = null;
	
	public function __construct($logger=true)
	{
		parent::__construct();
		
		if ($logger) {
			$this->logger = new Logger(get_class($this));
		}
	}
	
	public function beginTransaction()
	{
		return $this->db()->beginTransaction();
	}
	
	public function commitTransaction()
	{
		return $this->db()->commit();
	}
	
	public function rollbackTransaction()
	{
		return $this->db()->rollBack();
	}
	
	/**
	 * Return instanse of DB class
	 * @return DB
	 */
	protected function db($prefix='db')
	{
		if (!$this->dbo) {
			if ($db = $this->getConfig($prefix)) {
				$this->dbo = DB::getInstance(
						$db['dsn'],
						$db['username'],
						$db['password'],
						$db['options']
					);
			}
		}
		return $this->dbo;
	}
	
	/**
	 * Return instanse of Token class
	 * @return Token
	 */
	protected function token()
	{
		if (!$this->token) {
			$this->token = new Token($this->db());
		}
		return $this->token;
	}
	
	protected function debugParams($params)
	{
		$str = "Arguments BEGIN:\n";
		foreach ($params as $k=>$v) {
			$str .= '['.print_r ($v, true)."]\n";
		}
		$str .= 'END Arguments';
		return $str; 
	}
	
	public function queueRun($task)
	{
		;
	}	
}
