<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/

class core_autoload
{
	private static $aliases = array(
		'TPL'				=> 'class/cls_template.inc.php',	
		'FORMAT'			=> 'class/cls_format.inc.php',
		'HTTP'				=> 'class/cls_http.inc.php',
		'H'					=> 'class/cls_helper.inc.php',
		'USER'				=> 'class/cls_user.inc.php',
		'ACTION_LOG'		=> 'class/cls_action_log_class.inc.php',
	);
	
	public function __construct()
	{
		set_include_path(AWS_PATH);
		
		foreach (self::$aliases AS $key => $val)
		{
			self::$aliases[$key] = AWS_PATH . $val;
		}
		
		spl_autoload_register(array($this, 'loader'));
	}
    
    private static function loader($class_name)
	{
		$require_file = AWS_PATH . preg_replace('#_+#', '/', $class_name) . '.php';
		
		if (file_exists($require_file))
		{
			return require_once $require_file;
		}
		
		if (class_exists('AWS_APP', false))
		{
			self::$aliases = array_merge(self::$aliases, AWS_APP::plugins()->model());
		}
		
		if (isset(self::$aliases[$class_name]))
		{
			return require_once self::$aliases[$class_name];
		}
		// 查找 models 目录
		else if (file_exists(ROOT_PATH . 'models/' . str_replace(array('_class', '_'), array('', '/'), $class_name) . '.php'))
		{
			return require_once ROOT_PATH . 'models/' . str_replace(array('_class', '_'), array('', '/'), $class_name) . '.php';
		}
		// 查找 class
		else if (file_exists(AWS_PATH . 'class/' . $class_name . '.inc.php'))
		{
			return require_once AWS_PATH . 'class/' . $class_name . '.inc.php';
		}
	}
}