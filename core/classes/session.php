<?php
if (!session_id()) {
	session_start();
}
/**
 * Description of session
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Session
{
	const PREFIX = 'session_';
	
	static public function get($name)
	{
		return isset($_SESSION[self::PREFIX.$name])
				? $_SESSION[self::PREFIX.$name]
				: null;
	}
	
	static public function set($name, $value)
	{
		return $_SESSION[self::PREFIX.$name] = $value;
	}
	
	static public function delete($name)
	{
		if (isset($_SESSION[self::PREFIX.$name])) {
			unset($_SESSION[self::PREFIX.$name]);
		}
	}
	
	static public function destroy()
	{
		if (session_id()) {
			foreach ($_SESSION as $k=>$v) {
				unset($_SESSION[$k]);
			}
			session_destroy();
		}
	}
}
