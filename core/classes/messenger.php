<?php

/**
 * Description of messenger
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Messenger extends Base
{
	const MSG_FLASH		= 'flash';
	const MSG_NOTE		= 'note';
	const MSG_STATIC	= 'static';
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function create($user_id, $type, $message)
	{
		$user_id = intval($user_id);
		
		$query = "insert into messenger (`user_id`, `type`, `message`) values ";
		$query .= "(:user_id, :type, :message)";
		
		$st = $this->db()->prepare($query);
		if (!$st) {
			$this->error("Can not prepare new Message. $query");
			return false;
		}
		
		$st->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$st->bindValue(':type', $type, PDO::PARAM_STR);
		$st->bindValue(':message', $message, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->error("Can not insert new Message. $query");
			return false;
		}
		
		return true;
	}
	
	public function getFullList($user_id, $offset=0, $limit=10)
	{
		$user_id = intval($user_id);
		
		$offset = (int) $offset;
		$limit = (int) $limit;
		
		$query = "select * from messenger where user_id=:user_id order by id desc limit $offset,$limit";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		
		if (!$st->execute()) {
			$this->error("Can not get Full Messages list. $query");
			return false;
		}
		
		$ret = array ();
		
		while ($f = $st->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $f;
		}
		
		return $ret;
	}
	
	public function getList($user_id, $type=null, $seen=null, $closed=null)
	{
		$user_id = intval($user_id);
		
		$query = "select * from messenger where user_id=:user_id";
		
		if ($type !== null) {
			$query .= " and type=:type";
		}
		if ($seen !== null) {
			$query .= " and seen " . ($seen ? 'IS NOT NULL' : ' IS NULL');
		}
		if ($closed !== null) {
			$query .= " and closed " . ($closed ? 'IS NOT NULL' : ' IS NULL');
		}
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		
		if ($type !== null) {
			$st->bindValue(':type', $type, PDO::PARAM_STR);
		}
		
		if (!$st->execute()) {
			$this->error("Can not get Messages list. $query");
			return false;
		}
		
		$ret = array ();
		
		while ($f = $st->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $f;
		}
		
		return $ret;
	}
	
	public function setSeen($id, $user_id=null)
	{
		$id = intval($id);
		
		$query = "update messenger set seen=now() where id=$id";
		
		if ($user_id !== null) {
			$user_id = intval($user_id);
			$query .= " and user_id=$user_id";
		}
		
		$this->db()->query($query);
	}
	
	public function setClosed($id, $user_id=null)
	{
		$id = intval($id);
		
		$query = "update messenger set closed=now() where id=$id";
		
		if ($user_id !== null) {
			$user_id = intval($user_id);
			$query .= " and user_id=$user_id";
		}
		
		$this->db()->query($query);
	}
	
	static function getTypes()
	{
		return array (
			self::MSG_FLASH => 'Flash - One time only',
			self::MSG_NOTE => 'Note - Until user close it',
			self::MSG_STATIC => 'Static - Permament until manager close it',
		);
	}
	
	static function error($str)
	{
		\Session::set('message-type', 'alert-error');
		\Session::set('message', $str);
	}
	
	static function info($str)
	{
		\Session::set('message-type', '');
		\Session::set('message', $str);
	}
	
	static function warning($str)
	{
		\Session::set('message-type', 'alert-block');
		\Session::set('message', $str);
	}
	
	static function success($str)
	{
		\Session::set('message-type', 'alert-success');
		\Session::set('message', $str);
	}
	
	static function get()
	{
		return \Session::get('message');
	}
	
	static function getType()
	{
		return \Session::get('message-type');
	}
	
	static function delete()
	{
		return \Session::delete('message');
	}
	
}
