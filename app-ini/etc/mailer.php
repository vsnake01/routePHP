<?php
$config = array (
	'transport' => 'smtp', // This only supported. See Mailer class
	'options' => array (
					'host' => '',
					'port' => '',
					'auth' => true,
					'username' => '',
					'password' => '',
					'encryption' => 'tls',
				),
	'from' => Appearence::get('email_from'),
	'header' => '',
	'footer' => '',
	'images' => array (
		Appearence::get('welcomelogo') => array (
			'src' => 'logo.jpg',
			'mime' => 'image/jpeg'
		)
	)
);
