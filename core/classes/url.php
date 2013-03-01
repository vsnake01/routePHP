<?php

/**
 * Description of url
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Url
{
	static public function isSuccess()
	{
		return preg_match ('/\/success/', $_SERVER['REQUEST_URI']);
	}
	
	static public function isError()
	{
		return preg_match ('/\/error/', $_SERVER['REQUEST_URI']);
	}
	
	static public function getBase()
	{
		return 
			'http' .
			(isset($_SERVER['HTTPS'])?'s':'') .
			'://' .
			$_SERVER['HTTP_HOST'];
	}
	
	static public function getPath()
	{
		$path = preg_replace ('/\?.*/', '', $_SERVER['REQUEST_URI']);
		
		return $path;
	}
	
	static public function getValuePath()
	{
		$path = preg_replace ('/\?.*/', '', $_SERVER['REQUEST_URI']);
		$path = explode ('/', $path);
		
		return self::getBase() . (isset($path[1]) ? '/'.$path[1] : '');
	}
	
	static public function getParams()
	{
		$path = explode('?', $_SERVER['REQUEST_URI']);
		
		return isset($path[1]) ? $path[1] : null;
	}

	static public function getURL()
	{
		$path = $_SERVER['REQUEST_URI'];
		
		return self::getBase().$path;
	}
	
	static public function getPart($num)
	{
		$parts = explode('/', self::getPath());
		
		if (isset ($parts[$num])) {
			return $parts[$num];
		}
		
		return false;
	}
}
