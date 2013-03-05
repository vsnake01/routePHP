<?php

/**
 * Description of user
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class User extends Base
{
	private $userInfo=null;
	private $restrictedFields = array (
		'id', 'uid', 'deleted', 'verified', 'email',
		'type', 'created', 'activated', 'completed', 'password_secure'
	);
	private $masterFields = array (
		'id', 'uid', 'deleted', 'verified',
		'name', 'email', 'country', 'phone',
		'lang', 'password', 'password_secure', 'type', 'created',
		'activated', 'completed'
	);
	private $fieldTypes = array (
		'id' => 'INT',
		'uid' => 'STR',
		'deleted' => 'INT',
		'verified' => 'INT',
		'completed' => 'INT',
		'name' => 'STR',
		'email' => 'STR',
		'country' => 'STR',
		'phone' => 'STR',
		'lang' => 'STR',
		'password' => 'STR',
		'type' => 'STR',
		'created' => 'STR',
		'activated' => 'STR',
	);
	private $id=null;
		
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getFull()
	{
		return $this->userInfo;
	}
	
	public function findSimilar($id)
	{
		$id = intval($id);
		
		$query = "select name from users where id=$id";
		
		$st = $this->db()->query($query);
		if (!$f = $st->fetch(PDO::FETCH_ASSOC)) {
			return false;
		}
		
		$parts = explode(' ', $f['name']);
		
		foreach ($parts as $k=>$v) {
			$v = substr($this->db()->quote($v), 1, -1);
			$cond .= " and name like '%$v%'";
		}
		
		$query = "select * from users where id<>$id $cond and brand=(select brand from users where id=$id)";
		
		$ret = array ();
		
		$st = $this->db()->query($query);
		while ($f = $st->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $f;
		}
		
		return $ret;
	}
	
	public function setPassword($id, $password)
	{
		$query = "update users set password=:password where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':password', md5($password), PDO::PARAM_STR);
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			return false;
		}
		
		return true;
	}
	
	public function setPasswordSecure($id, $password)
	{
		$query = "update users set password_secure=:password where id=:id";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':password', md5($password), PDO::PARAM_STR);
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		
		if (!$st->execute()) {
			return false;
		}
		
		return true;
	}
	
	public function create($email, $password, $fields=null)
	{
		$this->logger->debug("Create profile: " . $this->debugParams(func_get_args()));
		
		$country = isset ($fields['country'])
					? (
						Country::isExist($fields['country'])
						? Country::getCode($fields['country'])
						: DEFAULT_COUNTRY
					)
					: DEFAULT_COUNTRY;
		
		$phone = isset ($fields['phone'])
					? $fields['phone']
					: '';
		
		$lang = isset ($fields['lang'])
					? $fields['lang']
					: 'en';

		$name = isset ($fields['name'])
					? $fields['name']
					: 'en';
		
		$query = 
				"INSERT INTO users "
				."(name, email, password, country, phone, lang, brand) "
				."values "
				."(:name, :email, :password, :country, :phone, :lang, :brand)";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->bindValue(':email', $email, PDO::PARAM_STR);
		$st->bindValue(':password', md5($password), PDO::PARAM_STR);
		$st->bindValue(':country', $country, PDO::PARAM_STR);
		$st->bindValue(':phone', $phone, PDO::PARAM_STR);
		$st->bindValue(':lang', $lang, PDO::PARAM_STR);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not create profile: " . $this->debugParams(func_get_args()));
			$this->logger->error("SQL: " . $st->queryString);
			return false;
		}
		$insertID = $this->db()->lastInsertId();
		
		$this->logger->debug("Added profile: $insertID");
		
		foreach ($fields as $k=>$v) {
			if (in_array ($k, $this->restrictedFields)) {
				continue;
			}
			if (in_array ($k, $this->masterFields)) {
				continue;
			}
			$query = "
				insert into users_info (user_id, `key`, `val`) values (:id, :key, :val)
				on duplicate key update `val`=:newval
				";
			if (!$st = $this->db()->prepare($query)) {
				$this->logger->error("Can not insert user info: $k $v");
				return false;
			}
			
			if (is_array ($v)) {
				$v = json_encode($v);
			}
			
			$st->bindValue(':id', $insertID, PDO::PARAM_INT);
			$st->bindValue(':key', $k, PDO::PARAM_STR);
			$st->bindValue(':val', $v, PDO::PARAM_STR);
			$st->bindValue(':newval', $v, PDO::PARAM_STR);
			
			if (!$st->execute()) {
				$this->logger->error("Can not nsert user info [statement]: $k $v; SQL: " . print_r ($st->errorInfo(), true));
				return false;
			}
		}

		return $insertID;
	}
	
	/**
	 * Attach a file from FileStorage to database
	 * @param int $id User ID
	 * @param string $name File name to display
	 * @param string $filename Filename from FileStorage
	 * @param string $file_type 'XXX' file type string: "jpg", "gif", "png"
	 * @param string $doc_type Optional. Type of document: "id", "address", "card", "other"
	 * @param string $expiration Optional. Date of expiration or null
	 * @param string $status Optional. Status of document: "new", "pending", "rejected", "expired", "approved"
	 * @param int $modified_by Optional. ID of user who create a document. Null by default.
	 * @return boolean ID of document on success or false on error
	 */
	public function attachFile($id, $name, $file_name, $file_type, $doc_type='other', $expiration=null, $status='new', $modified_by=null) {
		$query = "insert into documents 
			(user_id, name, file_name, file_type, doc_type, expiration, status, modified_by)
			values
			(:user_id, :name, :file_name, :file_type, :doc_type, :expiration, :status, :modified_by);";
		
		if (!$st = $this->db()->prepare($query)) {
			return false;
		}
		
		if ($modified_by === null) {
			$modified_by = $id;
		}
		
		$st->bindValue(':user_id', $id, PDO::PARAM_INT);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->bindValue(':file_name', $file_name, PDO::PARAM_STR);
		$st->bindValue(':file_type', $file_type, PDO::PARAM_STR);
		$st->bindValue(':doc_type', $doc_type, PDO::PARAM_STR);
		$st->bindValue(':expiration', $expiration, PDO::PARAM_STR);
		$st->bindValue(':status', $status, PDO::PARAM_STR);
		$st->bindValue(':modified_by', $modified_by, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			return false;
		}
		
		return $this->db()->lastInsertId();
	}
	
	public function getFiles($id)
	{
		$query = "select * from documents where user_id=:user_id";
		if (!$st = $this->db()->prepare($query)) {
			return false;
		}
		
		$st->bindValue(':user_id', $id, PDO::PARAM_INT);
		
		if (!$st->execute()) {
			return false;
		}
		
		$ret = array ();
		
		while ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $f;
		}
		
		return $ret;
	}
	
	/**
	 * Return uuid on exsiting ID
	 * @param int $id
	 * @return mixed String uuid on success or false on error
	 */
	public function getUID($id)
	{
		$query = "select uid from users where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not get uid on id: " . $this->debugParams(func_get_args()));
		}
		
		if ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			return $f['uid'];
		}
		
		$this->logger->error("Empty result on getUID: " . $this->debugParams(func_get_args()));
		
		return false;
	}
	
	public function delete($id)
	{
		$this->logger->debug("Delete profile: " . $this->debugParams(func_get_args()));

		$bid = $this->bindID($id);
		
		$query = "update users set deleted=1 where {$bid['id']}=:uid and brand=:brand";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':uid', $id, $bid['uid']);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		$res = $st->execute();
		
		if (!$res) {
			$this->logger->error("Can not delete profile: $id");
			return false;
		}
		return true;
	}
	
	public function load($id)
	{
		$bid = $this->bindID($id);
		
		$query = "select * from users where {$bid['id']}=:uid and brand=:brand";
		
		if (!$st = $this->db()->prepare($query)) {
			$this->logger->error("Can not load user: $id");
			return false;
		}
		
		$st->bindValue(':uid', $id, $bid['uid']);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not load user [statement]: $id");
			return false;
		}
		
		if (!$ret = $st->fetch(PDO::FETCH_ASSOC)) {
			$this->logger->error("Can not load user [fetch]: $id");
			return false;
		}
		
		// Convert bit fields
		$ret['deleted'] = ord($ret['deleted']) ? true : false;
		$ret['verified'] = ord($ret['verified']) ? true : false;
		$ret['completed'] = ord($ret['completed']) ? true : false;
		
		$query = "select * from users_info where user_id=:id";
		
		if (!$st = $this->db()->prepare($query)) {
			$this->logger->error("Can not load user info: $id");
			return false;
		}
		
		$st->bindValue(':id', $ret['id'], PDO::PARAM_INT);
		
		if (!$st->execute()) {
			$this->logger->error("Can not load user info [statement]: $id");
			return false;
		}
		
		while ($r=$st->fetch(PDO::FETCH_ASSOC)) {
			if (!in_array ($r['key'], $this->masterFields)) {
				$ret[$r['key']] = $r['val'];
			}
		}
		
		$this->userInfo = $ret;
		
		return $ret['id'];
	}
	
	public function get($name)
	{
		if ($this->userInfo && isset ($this->userInfo[$name])) {
			$v = json_decode($this->userInfo[$name], true);
			if ($v !== null) {
				$this->userInfo[$name] = $v;
			}
			return $this->userInfo[$name];
		}
		
		return null;
	}
	
	public function set($name, $value)
	{
		if (!$this->userInfo) {
			return false;
		}
		
		if (in_array ($name, $this->restrictedFields)) {
			return false;
		}
		
		$this->userInfo[$name] = $value;
		
		if ($name == 'id') {
			$this->id = $value;
		}
		
		return null;
	}
	
	public function verify($id, $status)
	{
		$status = $status ? 1 : 0;
		
		$query = "update users set verified=$status where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not verify user: $id, $status");
			return false;
		}
		
		return true;
	}
	
	public function complete($id, $status)
	{
		$status = $status ? 1 : 0;
		
		$query = "update users set completed=$status where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':id', $id);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not complete user: $id, $status");
			return false;
		}
		
		return true;
	}
	
	public function activate($id)
	{
		$query = "update users set activated=now() where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		$st->bindValue(':id', $id);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not activate user: $id");
			return false;
		}
		
		return true;
	}
	
	public function save()
	{
		if (!$this->userInfo) {
			return false;
		}
		
		$this->db()->beginTransaction();
		
		$query = "update users set name=:name, email=:email, country=:country, phone=:phone, lang=:lang where id=:id and brand=:brand";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':name', $this->userInfo['name'], PDO::PARAM_STR);
		$st->bindValue(':email', $this->userInfo['email'], PDO::PARAM_STR);
		$st->bindValue(':country', $this->userInfo['country'], PDO::PARAM_STR);
		$st->bindValue(':phone', $this->userInfo['phone'], PDO::PARAM_STR);
		$st->bindValue(':lang', $this->userInfo['lang'], PDO::PARAM_STR);
		$st->bindValue(':id', $this->userInfo['id'], PDO::PARAM_INT);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->db()->rollBack();
			$this->logger->error("Can not update user: $id");
			return false;
		}
		
		foreach ($this->userInfo as $k=>$v) {
			if (in_array ($k, $this->restrictedFields)) {
				continue;
			}
			if (in_array ($k, $this->masterFields)) {
				continue;
			}
			$query = "
				insert into users_info (user_id, `key`, `val`) values (:id, :key, :val)
				on duplicate key update `val`=:newval
				";
			if (!$st = $this->db()->prepare($query)) {
				$this->db()->rollBack();
				$this->logger->error("Can not update user info: $k $v");
				return false;
			}
			
			if (is_array ($v)) {
				$v = json_encode($v);
			}
			
			$st->bindValue(':id', $this->userInfo['id'], PDO::PARAM_INT);
			$st->bindValue(':key', $k, PDO::PARAM_STR);
			$st->bindValue(':val', $v, PDO::PARAM_STR);
			$st->bindValue(':newval', $v, PDO::PARAM_STR);
			
			if (!$st->execute()) {
				$this->db()->rollBack();
				$this->logger->error("Can not update user info [statement]: $k $v; SQL: " . print_r ($st->errorInfo(), true));
				return false;
			}
		}
		
		$this->db()->commit();
		return true;
	}
	
	/**
	 * Check id user credentials correct
	 * @param string $email
	 * @param string $password Optional. Passing NULL will provide check without password
	 * @return mixed If user correct, return ID or false on failure
	 */
	public function check($email, $password=null)
	{
		if ($password !== null) {
			$query = "select id from users where email=:email and password=:password and brand=:brand";
		} else {
			$query = "select id from users where email=:email and brand=:brand";
		}
		
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':email', $email, PDO::PARAM_STR);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		if ($password !== null) {
			$st->bindValue(':password', md5($password), PDO::PARAM_STR);
		}
		
		
		if (!$st->execute()) {
			return false;
		}
		
		if (!$res = $st->fetch(PDO::FETCH_ASSOC)) {
			return false;
		}
		
		unset($st);
		
		return $res['id'];
	}
	
	/**
	 * Check id user with secure password
	 * @param string $email
	 * @param string $password Optional. Passing NULL will provide check without password
	 * @return mixed If user correct, return ID or false on failure
	 */
	public function checkSecure($id, $password)
	{
		$query = "select id from users where id=:id and password_secure=:password and brand=:brand";
		
		$st = $this->db()->prepare($query);
		
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		$st->bindValue(':password', md5($password), PDO::PARAM_STR);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			return false;
		}
		
		if (!$res = $st->fetch(PDO::FETCH_ASSOC)) {
			return false;
		}
		
		unset($st);
		
		return true;
	}

	public function getCustomList($conditions=null, $page=1, $lpp=20)
	{
		$lpp = intval($lpp);
		$page = intval($page);
		if ($page < 1) {
			$page = 1;
		}
		if ($lpp > 1000) {
			$lpp = 1000;
		}
		$offset = ($page-1) * $lpp;
		
		$query = "select * from users where type='secure' and brand=:brand";
		$bind = array ();
		if ($conditions) {
			$cond = '';
			foreach ($conditions as $attr=>$value) {
				if (in_array ($attr, $this->masterFields)) {
					if (!is_array ($value)) {
						$value = array ('=', $value);
					}
					$cond .= ' and ' .$attr . ' '.$value[0].' :v'.$attr;
					$bind[$attr] = $value[1];
				}
			}
			$query .= $cond;
		}
		
		$query .= " limit $offset,$lpp";
		
		if (!$st = $this->db()->prepare($query)) {
			$this->logger->error("Can not get Custom List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		foreach ($bind as $k=>$v) {
			$st->bindValue(':v'.$k, $v, $this->fieldTypes[$k]=='INT' ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not execute Custom List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		$ret = array ();
		while ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			$ret[$f['id']] = $f;
		}
		
		return $ret;
	}
	
	public function getSearchList($srch, $page=1, $lpp=20)
	{
		$lpp = intval($lpp);
		$page = intval($page);
		if ($page < 1) {
			$page = 1;
		}
		if ($lpp > 1000) {
			$lpp = 1000;
		}
		$offset = ($page-1) * $lpp;

		$query = "
			select 
				distinct u.*
			from
				users u
			join users_info ui on u.id=ui.user_id
			where
				u.type='secure' and u.brand=:brand and
				(
					u.name like :srch or
					u.phone like :srch or
					u.email like :srch or
					u.country like :srch or
					ui.val like :srch
				)
			";
		
		$query .= " limit $offset,$lpp";
		
		if (!$st = $this->db()->prepare($query)) {
			$this->logger->error("Can not get Search List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		$st->bindValue(':srch', '%'.$srch.'%', PDO::PARAM_STR);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not execute Search List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		$ret = array ();
		while ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			$ret[$f['id']] = $f;
		}
		
		return $ret;
	}

	public function getCustomSearchList($conditions, $srch, $page=1, $lpp=20)
	{
		$lpp = intval($lpp);
		$page = intval($page);
		if ($page < 1) {
			$page = 1;
		}
		if ($lpp > 1000) {
			$lpp = 1000;
		}
		$offset = ($page-1) * $lpp;
		
		$query = "
			select 
				distinct u.*
			from
				users u
			left join users_info ui on u.id=ui.user_id
			where
				u.type='secure' and u.brand=:brand and
				(
					u.name like :srch or
					u.phone like :srch or
					u.email like :srch or
					u.country like :srch or
					ui.val like :srch
				)
			";
		$bind = array ();
		if ($conditions) {
			$cond = '';
			foreach ($conditions as $attr=>$value) {
				if (in_array ($attr, $this->masterFields)) {
					if (!is_array ($value)) {
						$value = array ('=', $value);
					}
					$cond .= ' and ' .$attr . ' '.$value[0].' :v'.$attr;
					$bind[$attr] = $value[1];
				}
			}
			$query .= $cond;
		}
		
		$query .= " limit $offset,$lpp";
		
		if (!$st = $this->db()->prepare($query)) {
			$this->logger->error("Can not get Custom Search List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		foreach ($bind as $k=>$v) {
			$st->bindValue(':v'.$k, $v, $this->fieldTypes[$k]=='INT' ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		
		$st->bindValue(':srch', '%'.$srch.'%', PDO::PARAM_STR);
		$st->bindValue(':brand', BRAND, PDO::PARAM_STR);
		
		if (!$st->execute()) {
			$this->logger->error("Can not execute Custom Search List: " . print_r ($st->errorInfo(), true));
			return false;
		}
		
		$ret = array ();
		while ($f=$st->fetch(PDO::FETCH_ASSOC)) {
			$ret[$f['id']] = $f;
		}
		
		return $ret;
	}
	
	private function isUID($id)
	{
		if (strlen($id) > 16) {
			return true;
		} else {
			return false;
		}
	}
	
	private function bindID($id)
	{
		if ($this->isUID($id)) {
			return array('id' => 'uid', 'uid' => PDO::PARAM_STR);
		} else {
			return array('id' => 'id', 'uid' => PDO::PARAM_INT);
		}
	}
	
}
