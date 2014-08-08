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

if (! defined('AWS_PATH'))
{
	define('AWS_PATH', dirname(__FILE__) . '/');
}

if (defined('G_GZIP_COMPRESS') AND G_GZIP_COMPRESS === TRUE)
{
	if (@ini_get('zlib.output_compression') == FALSE)
	{
		if (extension_loaded('zlib'))
		{
			if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
			{
				ob_start('ob_gzhandler');
			}
		}
	}
}

require_once (AWS_PATH . 'init.php');

require_once (AWS_PATH . 'aws_app.inc.php');
require_once (AWS_PATH . 'aws_controller.inc.php');
require_once (AWS_PATH . 'aws_model.inc.php');