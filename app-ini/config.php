<?php
define ('DEVMODE', false);
define ('ADMINEMAIL', 'info@yourdomain.com');

/**
 * This function will be called with "class name" for the classes
 * you can define by yourself for your application
 * @param type $className
 */
function AppLoader($className)
{
	// autoload function for application-level classes
}

/*
 * BEGIN Branding stuff
 */
define ('BRAND_DEFAULT', 'default');

$brand = BRAND_DEFAULT;

if (!empty($argv[2])) {
	$brand = $argv[2];
} elseif (!empty($_SERVER['HTTP_HOST'])) {
	
	if (preg_match ('/somekey/i', $_SERVER['HTTP_HOST'])) {
		$brand = BRAND_SOMEKEY; // this is dummy constant
	}
}

define ('BRAND', $brand);
/*
 * END Branding stuff
 */
