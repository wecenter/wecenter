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

class AWS_APP
{
	private static $config;
	private static $db;
	private static $form;
	private static $upload;
	private static $image;
	private static $pagination;
	private static $cache;
	private static $lang;
	private static $session;
	private static $captcha;
	private static $mail;
	
	public static $session_type = 'file';
	
	private static $models = array();
	private static $plugins = array();
	
	public static $settings = array();
	public static $_debug = array();
	
	public static function run()
	{
		self::init();
		
		load_class('core_uri')->set_rewrite();
		
		if (!$app_dir = load_class('core_uri')->app_dir)
		{
			$app_dir = ROOT_PATH . 'app/home/';
		}

		// 传入应用目录,返回控制器对象
		$handle_controller = self::create_controller(load_class('core_uri')->controller, $app_dir);
		
		$action_method = load_class('core_uri')->action . '_action';
		
		// 判断
		if (! is_object($handle_controller) OR ! method_exists($handle_controller, $action_method))
		{
			HTTP::error_404();
		}
		
		if (method_exists($handle_controller, 'get_access_rule'))
		{
			$access_rule = $handle_controller->get_access_rule();
		}
		
		// 判断使用白名单还是黑名单,默认使用黑名单
		if ($access_rule)
		{			
			// 黑名单,黑名单中的检查 'white'白名单,白名单以外的检查 (默认是黑名单检查)
			if (isset($access_rule['rule_type']) AND $access_rule['rule_type'] == 'white')
			{
				if ((! $access_rule['actions']) OR (! in_array(load_class('core_uri')->action, $access_rule['actions'])))
				{
					self::login();
				}
			}
			else if (isset($access_rule['actions']) AND in_array(load_class('core_uri')->action, $access_rule['actions']))	// 非白就是黑名单
			{
				self::login();
			}
		
		}
		else
		{
			self::login();
		}
		
		// 执行
		$handle_controller->$action_method();
	}

	private static function init()
	{
		set_exception_handler(array('AWS_APP', 'exception_handle'));
		
		self::$config = load_class('core_config');
		self::$db = load_class('core_db');
		
		self::$plugins = load_class('core_plugins');
		
		self::$settings = self::model('setting')->get_settings();
		 
		if ((!defined('G_SESSION_SAVE') OR G_SESSION_SAVE == 'db') AND get_setting('db_version') > 20121123)
		{
			Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable(array(
			    'name' 					=> get_table('sessions'),
			    'primary'				=> 'id',
			    'modifiedColumn'		=> 'modified',
			    'dataColumn'			=> 'data',
			    'lifetimeColumn'		=> 'lifetime',
				//'authIdentityColumn'	=> 'uid'
			)));
			
			self::$session_type = 'db';
		}
		
		Zend_Session::setOptions(array(
			'name' => G_COOKIE_PREFIX . '_Session',
			'cookie_domain' => G_COOKIE_DOMAIN
		));
		
		if (G_SESSION_SAVE == 'file' AND G_SESSION_SAVE_PATH)
		{
			Zend_Session::setOptions(array(
				'save_path' => G_SESSION_SAVE_PATH
			));
		}
		
		Zend_Session::start();
		
		self::$session = new Zend_Session_Namespace(G_COOKIE_PREFIX . '_Anwsion');
		
		if ($default_timezone = get_setting('default_timezone'))
		{
			date_default_timezone_set($default_timezone);
		}

		$img_url = get_setting('img_url');
		$base_url = get_setting('base_url');
		
		! empty($img_url) ? define('G_STATIC_URL', $img_url) : define('G_STATIC_URL', $base_url . '/static');
		
		if (self::config()->get('system')->debug)
		{
			if ($cornd_timer = self::cache()->getGroup('crond'))
			{				
				foreach ($cornd_timer AS $cornd_tag)
				{
					if ($cornd_runtime = self::cache()->get($cornd_tag))
					{
						AWS_APP::debug_log('crond', 0, 'Tag: ' . str_replace('crond_timer_', '', $cornd_tag) . ', Last run time: ' . date('Y-m-d H:i:s', $cornd_runtime));
					}
				}
			}
		}
	}
	
	public static function create_controller($controller, $app_dir)
	{
		if (trim($app_dir) == '' OR trim($controller, '/') === '')
		{
			return false;
		}
		
		$class_file = $app_dir . $controller . '.php';
		
		if (! file_exists($class_file))
		{
			return false;
		}
		
		if (! class_exists($controller, false))
		{
			require_once ($class_file);
		}
		
		if (class_exists($controller, false))
		{
			return new $controller();
		}
		
		return false;
	}
	
	public static function exception_handle(Exception $exception)
    {
        show_error("Application error\n------\nMessage: " . $exception->getMessage() . "\n------\nBuild: " . G_VERSION . " " . G_VERSION_BUILD . "\nPHP Version: " . PHP_VERSION . "\nUser Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n------\n" . $exception->__toString());
    }
    
	/**
   	* 格式化弹出信息组件返回值
   	* 
   	* @param $rsm 		结果数据,可以为空
   	* @param $errno		错误代码，默认为 0 无错误，其它值为相应的错误代码
   	* @param $err		错误信息
   	* 
   	* @return 返回标准的 RSM 数据
   	*/
	public static function RSM($rsm, $errno = 0, $err = '')
	{
		return array(
			'rsm' => $rsm, 
			'errno' => (int)$errno, 
			'err' => $err,
		);
	}
		
	public static function login()
	{
		if (! USER::get_client_uid())
		{
			if ($_POST['_post_type'] == 'ajax')
			{
				H::ajax_json_output(self::RSM(null, -1, AWS_APP::lang()->_t('会话超时, 请重新登录')));
			}
			else
			{
				HTTP::redirect('/account/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
			}
		}
	}

	public static function config()
	{
		return self::$config;
	}
	
	public static function upload()
	{
		if (!self::$upload)
		{
			self::$upload = load_class('core_upload');
		}
		
		return self::$upload;
	}
	
	public static function image()
	{
		if (!self::$image)
		{
			self::$image = load_class('core_image');
		}
		
		return self::$image;
	}
	
	public static function lang()
	{
		if (!self::$lang)
		{
			self::$lang = load_class('core_lang');
		}
		
		return self::$lang;
	}
	
	public static function captcha()
	{
		if (!self::$captcha)
		{
			self::$captcha = load_class('core_captcha');
		}
		
		return self::$captcha;
	}
	
	public static function cache()
	{
		if (!self::$cache)
		{
			self::$cache = load_class('core_cache');
		}
		
		return self::$cache;
	}
		
	public static function form()
	{
		if (!self::$form)
		{
			self::$form = load_class('core_form');
		}
		
		return self::$form;
	}
	
	public static function mail()
	{
		if (!self::$mail)
		{
			self::$mail = load_class('core_mail');
		}
		
		return self::$mail;
	}
	
	public static function plugins()
	{
		return self::$plugins;
	}
	
	public static function pagination()
	{
		if (!self::$pagination)
		{
			self::$pagination = load_class('core_pagination');
		}
		
		return self::$pagination;
	}
	
	public static function session()
	{
		return self::$session;
	}
	
	public static function db($db_object_name = 'master')
	{
		if (!self::$db)
		{
			return false;
		}
		
		return self::$db->setObject($db_object_name);
	}
	
	public static function debug_log($type, $expend_time, $message)
	{
		self::$_debug[$type][] = array(
			'expend_time' => $expend_time,
			'log_time' => microtime(true),
			'message' => $message
		);
	}

	public static function model($model_class = null)
	{
		if (!$model_class)
		{
			$model_class = 'AWS_MODEL';
		}
		else if (! strstr($model_class, '_class'))
		{
			$model_class .= '_class';
		}
		
		if (! isset(self::$models[$model_class]))
		{
			$model = new $model_class();
			self::$models[$model_class] = $model;
		}
		
		return self::$models[$model_class];
	}
}