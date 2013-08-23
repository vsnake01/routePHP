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
		
		if (defined ('BRAND')) {
			$file_brand = PATH_ETC.'/brands/'.BRAND.'/'.$class.'.php';
		}
		$file = PATH_ETC.'/'.$class.'.php';
		
		$local_config = array ();
		
		if (file_exists($file)) {
			include $file;
			$local_config = array_merge ($local_config, $config);
		}
		if (file_exists($file_brand)) {
			include $file_brand;
			$local_config = array_merge_recursive_distinct ($local_config, $config);
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
	
	public function getVar($group, $name, $lang=null) {
		$path = array ();

		$path[] = realpath(PATH_VAR.'/'.$group.'/'.str_replace('\\', '/', strtolower(get_class($this))).'/'.$name);
		$path[] = realpath(PATH_VAR.'/'.$group.'/'.str_replace('\\', '/', strtolower(get_class($this))).'/'.BRAND.'/'.$name);
		$path[] = realpath(PATH_VAR.'/'.$group.'/'.$name);
		$path[] = realpath(PATH_VAR.'/'.$group.'/'.BRAND.'/'.$name);
		
		foreach ($path as $p) {
			if ($lang) {
				$pl = str_replace('/'.$name, '/'.$lang.'/'.$name, $p);
				if (($ret = $this->returnVar($pl)) !== null) {
					return $ret;
				}
			}
			if (($ret = $this->returnVar($p)) !== null) {
				return $ret;
			}
		}
		return null;
	}
	
	private function returnVar($path) {
		if (stristr(PATH_VAR, $path) === null) {
			return false;
		}
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		return null;
	}
}
