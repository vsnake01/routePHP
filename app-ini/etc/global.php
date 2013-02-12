<?php
$config = array (
	'db'	=> array (
		'dsn'		=> 'mysql:dbname=routephp;host=localhost',
		'username'	=> 'root',
		'password'	=> '',
		'options'	=> array (
							'database.params.charset'=>'utf8',
							PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
						),
	),
	'theme' => array (
		'name' => 'default',
	)
);
