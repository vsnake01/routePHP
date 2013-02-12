<?php
spl_autoload_register("MainLoad");

if(in_array("__autoload", spl_autoload_functions()))
	spl_autoload_register("__autoload");

define ('PATH_CORE', __DIR__);
define ('PATH_APP', __DIR__.'/../app');

define ('PATH_ETC', realpath(PATH_APP.'/etc'));
define ('PATH_CLASSES', realpath(PATH_CORE.'/classes'));
define ('PATH_MODULES', realpath(PATH_APP.'/modules'));
define ('PATH_CORE_MODULES', realpath(PATH_CORE.'/modules'));
define ('PATH_ROUTES', realpath(PATH_APP.'/routes'));
define ('PATH_VIEWS', realpath(PATH_APP.'/views'));
define ('PATH_EXT', realpath(PATH_CORE.'/ext'));
define ('PATH_UTL', realpath(PATH_APP.'/utl'));
define ('PATH_CORE_UTL', realpath(PATH_CORE.'/utl'));
define ('PATH_WWW', realpath(__DIR__.'/../www'));
define ('PATH_VAR', realpath(PATH_APP.'/var'));
define ('PATH_LOG', realpath(PATH_VAR.'/log'));
define ('DEFAULT_COUNTRY', \Country::getCode('Cyprus'));

define ('LOGGED', \Session::get('logged'));
define ('LOGGED_TYPE', \Session::get('logged_type'));
define ('LOGGED_NAME', \Session::get('logged_name'));
define ('LOGGED_EMAIL', \Session::get('logged_email'));

// Include project config
require_once PATH_APP.'/config.php';

try
{
	if (!defined('APP_LEVEL')) {
		return;
	}
	if (APP_LEVEL == 'WEB') {
		new Dispatcher;
	}
	if (APP_LEVEL == 'SCHEDULER') {
		$q = new Queue();
		for ($i=0;$i<15;$i++) {
			$q->run();
		}
	}
	
}
catch (AppException $ex)
{
	// some shit happens
	$logger = new Logger('bootstrap');
	$logger->error("\n".$ex->getTraceAsString());
}
catch (PDOException $ex) {
	$logger = new Logger('sql');
	$logger->error(
			"\n"
			.$ex->getMessage()
			."\n"
			.$ex->getTraceAsString());
}

function MainLoad($className)
{
	$paths = array (
		PATH_MODULES,
		PATH_CORE_MODULES,
		PATH_CLASSES,
		PATH_UTL,
		PATH_CORE_UTL,
	);
	
	$className = strtolower($classNameCase = $className);

	if (!substr($className, 0, 1) != '\\') {
		$className = '\\' . $className;
	}
	
	$classNameFile = str_replace ('\\', '/', $className).'.php';
	
	if (preg_match ('|^\/routes|', $classNameFile)) {
		// Load Route
		$classNameFile = str_replace ('/routes', '', $classNameFile);
		if (file_exists (PATH_ROUTES.$classNameFile)) {
			include_once PATH_ROUTES.$classNameFile;
			return;
		}
	} else {
		foreach ($paths as $path) {
			if (file_exists ($path.$classNameFile)) {
				include_once $path.$classNameFile;
				return;
			}
		}
	}
	ExtLoad($classNameCase);
	if (function_exists ('AppLoader')) {
		AppLoader($classNameCase);
	}
}

function ExtLoad($className) {
	$paths = array (
		PATH_EXT,
		PATH_EXT.'/monolog/src',
	);
	
	if (!substr($className, 0, 1) != '\\') {
		$className = '\\' . $className;
	}
	
	$classNameFile = str_replace ('\\', '/', $className).'.php';
	
	foreach ($paths as $path) {
		if (file_exists ($path.$classNameFile)) {
			include_once $path.$classNameFile;
			break;
		}
	}
}
