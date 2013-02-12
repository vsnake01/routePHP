<?php

/**
 * Description of appearence
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */

class Appearence {
	
	public static $config = null;
	
	public static function get()
	{
		$args = func_get_args();
		
		if (!$args) {
			return null;
		}
		
		self::readConfig();
		
		$c = self::$config;
		
		foreach ($args as $name) {
			if (isset ($c[$name])) {
				$c = $c[$name];
			} else {
				$c = null;
			}
		}
		
		return $c;
	}
	
	private static function readConfig()
	{
		if (self::$config) {
			return true;
		}
		
		include PATH_ETC.'/appearence.php';
		self::$config = isset($config[BRAND]) ? $config[BRAND] : $config;
	}
}
