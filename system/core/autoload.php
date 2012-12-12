<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/

class core_autoload
{
	private static $_gz_class = array(
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
		
		foreach (self::$_gz_class AS $key => $val)
		{
			self::$_gz_class[$key] = AWS_PATH . $val;
		}
		
		spl_autoload_register(array($this, 'loader'));
	}
    
    private static function loader($class_name)
	{
		$require_file = AWS_PATH . preg_replace('#_+#', '/', $class_name) . '.php';
		
		if (file_exists($require_file))
		{
			return require $require_file;
		}
		
		if (class_exists('AWS_APP', false))
		{
			self::$_gz_class = array_merge(self::$_gz_class, AWS_APP::plugins()->model());
		}
		
		// 如果内置有显示就读内置的
		if (isset(self::$_gz_class[$class_name]))
		{
			return require(self::$_gz_class[$class_name]);
		}
		// 查找 models 目录
		else if (file_exists(ROOT_PATH . 'models/' . str_replace(array('_class', '_'), array('', '/'), $class_name) . '.php'))
		{
			return require(ROOT_PATH . 'models/' . str_replace(array('_class', '_'), array('', '/'), $class_name) . '.php');
		}
		// 查找 includes
		else if (file_exists(AWS_PATH . 'class/' . $class_name . '.inc.php'))
		{
			return require(AWS_PATH . 'class/' . $class_name . '.inc.php');
		}
	}
}