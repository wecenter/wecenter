<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_ANWSION', TRUE);
define('ENVIRONMENT_PHP_VERSION', '5.2.2');
//define('SYSTEM_LANG', 'en_US');

if (version_compare(PHP_VERSION, ENVIRONMENT_PHP_VERSION, '<'))
{
	die('Error: WeCenter require PHP version ' . ENVIRONMENT_PHP_VERSION . ' or newer');
}

if (version_compare(PHP_VERSION, '6.0', '>='))
{
	die('Error: WeCenter not support PHP version 6 currently');
}

define('START_TIME', microtime(TRUE));

if (function_exists('memory_get_usage'))
{
	define('MEMORY_USAGE_START', memory_get_usage());
}

if (! defined('AWS_PATH'))
{
	define('AWS_PATH', dirname(__FILE__) . '/');
}

if (defined('SAE_TMP_PATH'))
{
	define('IN_SAE', true);
}

define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');

if (defined('IN_SAE'))
{
	error_reporting(0);

	define('TEMP_PATH', rtrim(SAE_TMP_PATH, '/') . '/');
}
else
{
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

	define('TEMP_PATH', dirname(dirname(__FILE__)) . '/tmp/');
}

if (file_exists(AWS_PATH . 'enterprise.php'))
{
	require_once(AWS_PATH . 'enterprise.php');
}

if (function_exists('get_magic_quotes_gpc'))
{
	if (@get_magic_quotes_gpc()) // GPC 进行反向处理
	{
		if (! function_exists('stripslashes_gpc'))
		{
			function stripslashes_gpc(&$value)
			{
				$value = stripslashes($value);
			}
		}

		array_walk_recursive($_GET, 'stripslashes_gpc');
		array_walk_recursive($_POST, 'stripslashes_gpc');
		array_walk_recursive($_COOKIE, 'stripslashes_gpc');
		array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	}
}

require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');

array_walk_recursive($_GET, 'remove_invisible_characters');
array_walk_recursive($_POST, 'remove_invisible_characters');
array_walk_recursive($_COOKIE, 'remove_invisible_characters');
array_walk_recursive($_REQUEST, 'remove_invisible_characters');

if (@ini_get('register_globals'))
{
	if ($_REQUEST)
	{
		foreach ($_REQUEST AS $name => $value)
		{
			unset($$name);
		}
	}

	if ($_COOKIE)
	{
		foreach ($_COOKIE AS $name => $value)
		{
			unset($$name);
		}
	}
}

require_once(AWS_PATH . 'functions.app.php');

if (file_exists(AWS_PATH . 'config.inc.php'))
{
	require_once(AWS_PATH . 'config.inc.php');
}

load_class('core_autoload');

date_default_timezone_set('Etc/GMT-8');