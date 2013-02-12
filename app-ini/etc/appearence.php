<?php
// See branding links at the end

$sharedConfig = array (
	'somekey' => array (
		'test' => 'Test Value',
	),
);
$config['default'] = array_merge($sharedConfig, array (
			'logo' => '/branding/default/logo.png',
			'maillogo' => '/branding/default/logo.png',
			'welcomelogo' => '/branding/default/logo.png',
			'company_name' => 'openPHP',
			'email_from' => 'test@default.com',
		)
	);