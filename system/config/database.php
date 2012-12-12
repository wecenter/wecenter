<?php

$config['charset'] = 'utf8';
$config['prefix'] = 'aws_';
$config['driver'] = 'MySQLi';

$config['master'] = array(
	'host' => '127.0.0.1',
	'username' => 'source_idunion',
	'password' => 'sourcedf2012',
	'dbname' => 'source4'
);

$config['slave'] = false;