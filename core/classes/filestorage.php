<?php

/**
 * Provide access to database on files
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class FileStorage
{
	private static $basePath='filestorage';
	
	/**
	 * Check If it exists
	 * @param string $file File name in storage <MD5 Hash>.extension
	 * @param string $storageName Option. Storage name prefix
	 * @return true or false
	 */
	static public function check($file, $storageName='', $postfix='')
	{
		$path = self::makePath(substr($file, 0, 32), $storageName);
		
		if ($postfix) {
			$file = preg_replace('/\.(\w+)$/', '_'.$postfix.'.$1', $file);
		}
		
		return file_exists($path.'/'.$file);
	}
	/**
	 * Retrieve file contents from storage
	 * @param string $file File name in storage <MD5 Hash>.extension
	 * @param string $storageName Option. Storage name prefix
	 * @return null 
	 */
	static public function get($file, $storageName='', $postfix='')
	{
		$path = self::makePath(substr($file, 0, 32), $storageName);
		if ($postfix) {
			$file = preg_replace('/\.(\w+)$/', '_'.$postfix.'.$1', $file);
		}
		
		if (file_exists($path.'/'.$file)) {
			return file_get_contents($path.'/'.$file);
		}
		
		return null;
	}
	
	/**
	 * Return full path to file in storage
	 * @param string $file File name in storage <MD5 Hash>.extension
	 * @param string $storageName Optional. Storage name prefix
	 * @return mixed Full path on success or false on error
	 */
	static public function getName($file, $storageName='', $postfix='')
	{
		$path = self::makePath(substr($file, 0, 32), $storageName);
		
		if ($postfix) {
			$file = preg_replace('/\.(\w+)$/', '_'.$postfix.'.$1', $file);
		}
		
		if (file_exists($path.'/'.$file)) {
			return $path.'/'.$file;
		}
		
		return false;
	}
	
	/**
	 * Return full path to file in storage
	 * @param string $file File name in storage <MD5 Hash>.extension
	 * @param string $storageName Optional. Storage name prefix
	 * @return mixed Full path on success or false on error
	 */
	static public function getSize($file, $storageName='', $postfix='')
	{
		$path = self::makePath(substr($file, 0, 32), $storageName);
		
		if ($postfix) {
			$file = preg_replace('/\.(\w+)$/', '_'.$postfix.'.$1', $file);
		}
		
		if (file_exists($path.'/'.$file)) {
			return filesize($path.'/'.$file);
		}
		
		return false;
	}
	
	/**
	 * Return MIME type
	 * @param string $file File name in storage <MD5 Hash>.extension
	 * @param string $storageName Optional. Storage name prefix
	 * @return mixed MIME type of document
	 */
	static public function getMimetype($file, $storageName='', $postfix='')
	{
		$path = self::makePath(substr($file, 0, 32), $storageName);
		
		if ($postfix) {
			$file = preg_replace('/\.\w+$/', '_'.$postfix.'$0', $file);
		}
		
		if (file_exists($path.'/'.$file)) {
			
			$finfo = finfo_open();
			$fileinfo = finfo_file($finfo, $path.'/'.$file, FILEINFO_MIME);
			finfo_close($finfo);
			
			return $fileinfo;
		}
		
		return false;
	}

	/**
	 * Copy file into storage
	 * @param string $realfile Path to realfile
	 * @param string $filename Name of the file how it should looks like
	 * @param string $storageName Optional. Storage name prefix
	 * @return mixed New filename on success or false on error
	 */
	static public function copyFrom($realfile, $filename=null, $storageName='')
	{
		if (!file_exists($realfile)) {
			return false;
		}
		
		return self::create(
					$filename,
					file_get_contents($realfile),
					$storageName
				);
	}
	
	static public function createPath($filename, $storageName, $fileIsKey=false)
	{
		if ($fileIsKey) {
			$hash = substr($filename, 0, 32);
			$fileExt = '';
		} else {
			$hash = md5($filename . uniqid(time(), true));
			$fileExt = '.' . pathinfo($filename, PATHINFO_EXTENSION);
		}
		
		$fileExt = strtolower($fileExt);
		
		$path = self::makePath($hash, $storageName);
		
		return $path;
		
	}
	
	/**
	 * Put contents into storage
	 * @param string $filename Name of the file how it should looks like
	 * @param string $content Content of the file
	 * @param string $storageName Optional. Storage name prefix
	 * @param boolean $fileIsKey Optional. Pass true to use $filename as key to store
	 * @return mixed New filename on success or false on error
	 */
	static public function create($filename, $content, $storageName='', $fileIsKey=false, $postfix='')
	{
		if ($fileIsKey) {
			$hash = substr($filename, 0, 32);
			$fileExt = '';
		} else {
			$hash = md5($filename . uniqid(time(), true));
			$fileExt = '.' . pathinfo($filename, PATHINFO_EXTENSION);
		}
		
		$fileExt = strtolower($fileExt);
		
		$path = self::makePath($hash, $storageName);
		
		if ($postfix) {
			$hash .= '_'.$postfix;
		}
		
		@file_put_contents($path.'/'.$hash.$fileExt, $content);
		
		@chmod ($path.'/'.$hash.$fileExt, 0666);
		
		return $hash.$fileExt;
	}
	
	static public function makePath($hash, $storageName='')
	{
		$storageName = preg_replace('/[^a-z0-1\-\_]/', '', $storageName);
		
		if (!preg_match ('/[a-z0-9]+/', $hash, $m)) {
			return false;
		}
		if (strlen($m[0]) != 32) {
			return false;
		}
		$hash = $m[0];
		preg_match_all('/\w{2}/', $hash, $m);
		$path = implode('/', $m[0]);
		
		$fullPath = 
			PATH_VAR 
			. '/' . self::$basePath 
			. (BRAND ? '/'.BRAND : '') 
			. ($storageName ? '/'.$storageName : '') 
			. '/' .$path;
		
		if (!is_dir($fullPath)) {
			if (!@mkdir($fullPath, 0777, true)) {
				return false;
			}
		}
		
		return $fullPath;
	}
}
