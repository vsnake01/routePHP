<?php

/**
 * Description of config
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Config
{
	protected $config = array();
	
	public function __construct()
	{
		$class = strtolower(str_replace('\\', '/', get_class($this)));
		$file = PATH_ETC.'/'.$class.'.php';
		
		$local_config = array ();
		
		if (file_exists($file)) {
			include $file;
			$local_config = $config;
		}
		
		$file = PATH_ETC.'/global.php';
		
		if (file_exists($file)) {
			include $file;
			$config = array_merge($config, $local_config);
		}
		
		if (isset ($config)) {
			foreach ($config as $k=>$v) {
				$this->config[$k] = $v;
			}
		}
	}
	
	protected function getConfig($name) {
		if (isset($this->config[$name])) {
			return $this->config[$name];
		}
		return null;
	}
	
	public function getVar($group, $name) {
		$path = realpath(PATH_VAR.'/'.$group.'/'.str_replace('\\', '/', strtolower(get_class($this))).'/'.$name);
		if (stristr(PATH_VAR, $path) === null) {
			return false;
		}
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		// Try to detect with brandname
		$path = realpath(PATH_VAR.'/'.$group.'/'.str_replace('\\', '/', strtolower(get_class($this))).'/'.BRAND.'/'.$name);
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		
		// Try to detect without class
		$path = realpath(PATH_VAR.'/'.$group.'/'.$name);
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		
		// Try to detect without class with brand
		$path = realpath(PATH_VAR.'/'.$group.'/'.BRAND.'/'.$name);
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		
		return null;
	}
}
