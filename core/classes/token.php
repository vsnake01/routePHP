<?php

/**
 * Translations and text provider
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Token extends Base
{
	const BASE_LANG = 'en';
	
	private $lang = 'en';
	
	public function __construct($lang='en')
	{
		parent::__construct();
		$this->lang = $lang;
	}
	
	public function get($name, $lang=null)
	{
		$key = md5($name);
		
		$ret = FileStorage::get($key, 'lang-'.$this->lang);
		
		if (!$ret) {
			$val = $name;
			
			$query = "select * from tokens where token_hash=:token_hash and lang=:lang";
			$st = $this->db()->prepare($query);

			$st->bindValue(':token_hash', $key, PDO::PARAM_STR);
			$st->bindValue(':lang', $this->lang, PDO::PARAM_STR);

			$st->execute();

			if ($t = $st->fetch(PDO::FETCH_ASSOC)) {
				$val = $t['token_value'];
			} else {
				$st = $this->db()->prepare(
						"insert into tokens "
						."(token_hash, token_value, lang) "
						."values "
						."(:token_hash, :token_value, :lang)"
					);

				$st->bindValue(':token_hash', $key, PDO::PARAM_STR);
				$st->bindValue(':token_value', $val, PDO::PARAM_LOB);
				$st->bindValue(':lang', $this->lang, PDO::PARAM_STR);

				$st->execute();
			}
			
			FileStorage::create($key, $val, 'lang-'.$this->lang, true);

			return $val;
		}
		
		return $ret;
	}
	
	public function setLang($lang='en')
	{
		$this->lang = $lang;
	}
	
	public function queueRun($task) {
		$info = explode(':', $task['params']);
		
		if (!isset($info[1])) {
			return false;
		}
		
		if ($info[0] == 'update') {
			$lang = $info[1];
			
			$query = "
				select
				  t1.token_hash, t2.token_value
				from
				  tokens t1
				left join tokens t2 on t1.token_hash=t2.token_hash
				where t1.lang='en' and t2.lang=:lang";
			
			$st = $this->db()->prepare($query);
			$st->bindValue(':lang', $lang);
			$st->execute();
			
			while ($f = $st->fetch(PDO::FETCH_ASSOC)) {
				FileStorage::create(
						$f['token_hash'],
						$f['token_value'],
						'lang-'.$lang,
						true);
			}
			
			return true;
		}
		
		return false;
	}
}
