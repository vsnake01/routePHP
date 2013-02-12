<?php

/**
 * Description of queue
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Queue extends Base
{
	private $taskMapper = array (
		'tokens'	=> '\\Token',
		'mail'		=> '\\Mailer',
		'deposit'	=> '\\Account',
		'withdraw'	=> '\\Account',
		'account'	=> '\\Account',
		'crm_push'	=> '\\CRM',
	);
	
	public function __construct($logger=true)
	{
		parent::__construct($logger);
	}
	
	public function run()
	{
		$task = $this->get();
		
		if (!$task) {
			return null;
		}
		
		if (!isset ($this->taskMapper[$task['task']])) {
			$query = "update queue set skipped=1 where id=:id";

			$st = $this->db()->prepare($query);

			$st->bindValue(':id', $task['id']);

			$st->execute();
			return null;
		}
		
		$o = new $this->taskMapper[$task['task']];
		
		if (!method_exists($o, 'queueRun')) {
			$query = "update queue set skipped=1 where id=:id";

			$st = $this->db()->prepare($query);

			$st->bindValue(':id', $task['id']);

			$st->execute();
			return false;
		}
		
		$ret = $o->queueRun($task);
		
		if (!$ret) {
			$query = "update queue set skipped=1 where id=:id";

			$st = $this->db()->prepare($query);

			$st->bindValue(':id', $task['id']);

			$st->execute();
			return;
		}
		
		if (isset ($ret['out_params'])) {
			$out_params = $ret['out_params'];
		} elseif (method_exists($o, 'getQueueOutParams')) {
			$out_params = $o->getQueueOutParams();
		} else {
			$out_params = '';
		}
		
		$status = isset($ret['status']) ? $ret['status'] : '';
		
		$query = "update queue set processed=1, status=:status, out_params=:params where id=:id";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':id', $task['id']);
		$st->bindValue(':params', $out_params);
		$st->bindValue(':status', $status);
		
		$st->execute();
	}
	
	public function get($id=null)
	{
		if ($id !== null) {
			$id = intval($id);
			$query = "select * from queue where id=$id";
		} else {
			$query = "select * from queue where processed=0 and skipped=0 and brand=:brand order by created limit 1";
		}
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':brand', BRAND);
		
		$st->execute();
		if (!$f=$st->fetch(PDO::FETCH_ASSOC)) {
			return false;
		}
		
		return $f;
	}
	
	public function close($id)
	{
		$id = intval($id);
		$manager = LOGGED;
		$query = "update queue set processed=1 where id=$id";
		
		
		if (!$this->db()->query($query)) {
			return false;
		}
		
		return true;
	}
	
	public function create($task, $params)
	{
		$query = "insert into queue (task, params, brand) values (:task, :params, :brand)";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':task', $task);
		$st->bindValue(':params', $params);
		$st->bindValue(':brand', BRAND);
		
		return $st->execute() ? true : false;
	}
	
	public function getList($task, $processed=false)
	{
		$processed = $processed ? 1 : 0;
		
		$query = "select id, created, params from queue where task=:task and processed=$processed and brand=:brand order by id asc limit 100";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':task', $task);
		$st->bindValue(':brand', BRAND);
		
		if (!$st->execute()) {
			return null;
		}
		
		$ret = array ();
		
		while ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $f;
		}
		
		return $ret;
	}
}
