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

require_once('../system/init.php');

HTTP::no_cache_header();

if (file_exists(AWS_PATH . 'config/install.lock.php'))
{
	H::redirect_msg(load_class('core_lang')->_t('您的程序已经安装, 要重新安装请删除 system/config/install.lock.php'));
}

@set_time_limit(0);

TPL::assign('page_title', 'WeCenter - Install');
TPL::assign('static_url', '../static');

switch ($_POST['step'])
{
	default :
		$system_require = array();

		if (version_compare(PHP_VERSION, ENVIRONMENT_PHP_VERSION, '>=') AND get_cfg_var('safe_mode') == false)
		{
			$system_require['php'] = TRUE;
		}

		if (class_exists('PDO', false))
		{
			if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
			{
				$system_require['db'] = 'PDO_MYSQL';
			}
		}

		if (!$system_require['db'] AND function_exists('mysqli_close'))
		{
			$system_require['db'] = 'MySQLi';
		}

		if (function_exists('session_start'))
		{
			$system_require['session'] = TRUE;
		}

		if (function_exists('iconv'))
		{
			$system_require['convert_encoding'] = TRUE;
		}

		if (isset($_COOKIE))
		{
			$system_require['cookie'] = TRUE;
		}

		if (function_exists('gd_info'))
		{
			$system_require['image_lib'] = 'GD';
		}

		if ($system_require['image_lib'] AND class_exists('Imagick', false))
		{
			$system_require['image_lib'] = 'ImageMagick';
		}

		if (function_exists('ctype_xdigit'))
		{
			$system_require['ctype'] = TRUE;
		}

		if (function_exists('curl_exec'))
		{
			$system_require['curl'] = TRUE;
		}

		if (function_exists('imageftbbox'))
		{
			$system_require['ft_font'] = TRUE;
		}


		if (function_exists('gzcompress'))
		{
			$system_require['zlib'] = TRUE;
		}

		// 检测 AWS_PATH 是否有写权限
		if (is_really_writable(AWS_PATH) OR defined('IN_SAE'))
		{
			$system_require['config_writable_core'] = TRUE;
		}

		// 检测 AWS_PATH /config/ 是否有写权限
		if (is_really_writable(AWS_PATH . 'config/') OR defined('IN_SAE'))
		{
			$system_require['config_writable_config'] = TRUE;
		}

		$base_dir = str_replace("\\", "",dirname(dirname($_SERVER['PHP_SELF'])));

		if (!defined('IN_SAE'))
		{
			if (!@file_get_contents('http://api.weibo.com/'))
			{
				$error_messages[] = load_class('core_lang')->_t('你的主机无法与微博通讯, 相关功能将不能使用');
			}

			if (!@gethostbyname('graph.qq.com'))
			{
				$error_messages[] = load_class('core_lang')->_t('你的主机无法与 QQ 通讯, QQ 登录功能将不能使用');
			}
		}

		TPL::assign('error_messages', $error_messages);
		TPL::assign('system_require', $system_require);
		TPL::assign('base_dir', $base_dir);

		TPL::output('install/index');
		break;

	case 2 :
		if (!defined('IN_SAE'))
		{
			$data_dir = array(
				'tmp',
				'cache',
				'uploads'
			);

			foreach ($data_dir as $key => $dir_name)
			{
				if (! is_dir(ROOT_PATH . $dir_name))
				{
					if (! @mkdir(ROOT_PATH . $dir_name))
					{
						$error_messages[] = load_class('core_lang')->_t('目录: %s 无法创建，请将网站根目录权限设置为 777, 或者创建这个目录设置权限为 777', ROOT_PATH . $dir_name);
					}
				}
			}

			if (! is_really_writable(AWS_PATH))
			{
				$error_messages[] = load_class('core_lang')->_t('目录: %s 无法写入，请将此目录权限设置为 777', AWS_PATH);
			}
		}

		if (class_exists('PDO', false))
		{
			if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
			{
				TPL::assign('pdo_support', TRUE);
			}
		}

		if (function_exists('mysqli_close'))
		{
			TPL::assign('mysqi_support', TRUE);
		}

		TPL::assign('error_messages', $error_messages);
		TPL::output('install/settings');
		break;

	case 3 :
		if (defined('IN_SAE'))
		{
			$db_config = array(
			  'host' => SAE_MYSQL_HOST_M . ':' . SAE_MYSQL_PORT,
			  'username' =>  SAE_MYSQL_USER,
			  'password' => SAE_MYSQL_PASS,
			  'dbname' => SAE_MYSQL_DB,
			  'charset' => 'utf8'
			);
		}
		else
		{
			$db_config = array(
				'charset' => 'utf8',
				'host' => $_POST['db_host'],
				'username' => $_POST['db_username'],
				'password' => $_POST['db_password'],
				'dbname' => $_POST['db_dbname']
			);

			if ($_POST['db_port'])
			{
				$db_config['port'] = $_POST['db_port'];
			}

			if ($_POST['db_driver'])
			{
				$db_driver = $_POST['db_driver'];
			}
			else if (class_exists('PDO', false))
			{
				if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
				{
					$db_driver = 'PDO_MYSQL';
				}
			}
		}

		if (!$db_driver)
		{
			$db_driver = 'MySQLi';
		}

		if (!$_POST['db_engine'])
		{
			$_POST['db_engine'] = 'MyISAM';
		}

		try
		{
			$db = Zend_Db::factory($db_driver, $db_config);
		}
		catch (Exception $e)
		{
			H::redirect_msg(load_class('core_lang')->_t('数据库连接失败, 错误信息:') . ' ' . strip_tags($e->getMessage()), './');
		}

		try
		{
			$tables = $db->fetchAll('SHOW TABLES');
		}
		catch (Exception $e)
		{
			H::redirect_msg(load_class('core_lang')->_t('数据库连接失败, 错误信息:') . ' ' . strip_tags($e->getMessage()), './');
		}

		if (number_format($db->getServerVersion(), 1) < 5)
		{
			H::redirect_msg(load_class('core_lang')->_t('安装中止: WeCenter 要求使用 MySQL 5.0 以上版本的数据库支持, 您的服务器当前 MySQL 版本为: %s', $db->getServerVersion()), './');
		}

		if (!$_POST['db_prefix'] AND count($tables) > 0)
		{
			H::redirect_msg(load_class('core_lang')->_t('数据库已经存在数据表, 不允许安装, 如要重新安装请先清空数据表'), './');
		}

		foreach ($tables AS $key => $table_info)
		{
			if (!is_array($table_info))
			{
				break;
			}

			foreach ($table_info AS $_key => $table)
			{
				if (substr($table, 0, strlen($_POST['db_prefix'])) == $_POST['db_prefix'])
				{
					H::redirect_msg(load_class('core_lang')->_t('数据库已经存在相同前缀的数据表, 不允许安装, 如要重新安装请先清空数据表'), './');

					break;
				}
			}
		}

		if (!defined('IN_SAE'))
		{
			$config = array(
				'charset' => 'utf8',
				'prefix' => $_POST['db_prefix'],
				'driver' => $db_driver,
				'master' => $db_config,
				'slave' => false
			);

			if ($_POST['db_port'])
			{
				$config['port'] = $_POST['db_port'];
			}

			load_class('core_config')->set('database', $config);
		}

		// 创建数据表
		$db_table_querys = explode(";\r", str_replace(array('[#DB_PREFIX#]', '[#DB_ENGINE#]', "\n"), array($_POST['db_prefix'], $_POST['db_engine'], "\r"), file_get_contents(ROOT_PATH . 'install/db/mysql.sql')));

		foreach ($db_table_querys as $_sql)
		{
			if ($query_string = trim(str_replace(array(
				"\r",
				"\n",
				"\t"
			), '', $_sql)))
			{
				$db->query($query_string);
			}
		}

		$db->insert($_POST['db_prefix'] . 'system_setting', array(
			'varname' => 'db_engine',
			'value' => 's:' . strlen($_POST['db_engine']) . ':"' . $_POST['db_engine'] . '";',
		));

		TPL::output('install/final');
		break;

	case 4 :
		$db = load_class('core_db')->setObject('master');
		$db_prefix = load_class('core_config')->get('database')->prefix;

		$salt = fetch_salt(4);

		$data = array(
			'user_name' => $_POST['user_name'],
			'password' => compile_password($_POST['password'], $salt),
			'email' => $_POST['email'],
			'salt' => $salt,
			'group_id' => 1,
			'reputation_group' => 5,
			'valid_email' => 1,
			'is_first_login' => 1,
			'reg_time' => time(),
			'reg_ip' => ip2long(fetch_ip()),
			'last_login' => time(),
			'last_ip' => ip2long(fetch_ip()),
			'last_active' => time(),
			'invitation_available' => 10,
			'integral' => 2000
		);

		$db->insert($db_prefix . 'users', $data);
		$db->insert($db_prefix . 'users_attrib', array('uid' => 1, 'signature' => ''));

		$db->insert($db_prefix . 'integral_log', array(
			 'uid' => 1,
			 'action' => 'REGISTER',
			 'integral' => 2000,
			 'note' => load_class('core_lang')->_t('初始资本'),
			 'balance' => 2000,
			 'time' => time()
		));

		//加载网站配置
		$base_dir = dirname(dirname($_SERVER['PHP_SELF']));
		$base_dir = ($base_dir == DIRECTORY_SEPARATOR) ? '' : $base_dir;

		$insert_query = file_get_contents(ROOT_PATH . 'install/db/system_setting.sql');

		$insert_query = str_replace('[#DB_PREFIX#]', $db_prefix, $insert_query);
		if (defined('IN_SAE'))
		{
			$insert_query = str_replace('[#UPLOAD_URL#]', serialize($_POST['upload_url']), $insert_query);
			$insert_query = str_replace('[#UPLOAD_DIR#]', serialize('saestor://uploads'), $insert_query);
		}
		else
		{
			$base_url = base_url();

			if (substr($base_url, -8) == '/install')
			{
				$base_url = substr_replace($base_url, '', -8);
			}

			$insert_query = str_replace('[#UPLOAD_URL#]', serialize($base_url . "/uploads"), $insert_query);
			$insert_query = str_replace('[#UPLOAD_DIR#]', serialize(str_replace("\\", "/", ROOT_PATH) . "uploads"), $insert_query);
		}

		$insert_query = str_replace('[#FROM_EMAIL#]', serialize($_POST['email']), $insert_query);
		$insert_query = str_replace('[#DB_VERSION#]', serialize(G_VERSION_BUILD), $insert_query);

		//$db->query($insert_query);

		$sql_query = str_replace("\n", "\r", $insert_query);

		$db_table_querys = explode(";\r", $sql_query);

		foreach ($db_table_querys as $_sql)
		{
			if ($query_string = trim(str_replace(array(
				"\r",
				"\n",
				"\t"
			), '', $_sql)))
			{
				try {
					$db->query($query_string);
				}
				catch (Exception $e)
				{
					die('SQL Error: ' . $e->getMessage() . '<br /><br />Query: ' . $query_string);
				}
			}
		}

		$db->insert($db_prefix . 'system_setting', array(
			'varname' => 'register_agreement',
			'value' => serialize(file_get_contents(ROOT_PATH . 'install/db/register_agreement.txt')),
		));

		if (!defined('IN_SAE'))
		{
			$config_file = file_get_contents(AWS_PATH . 'config.dist.php');
			$config_file = str_replace('{G_COOKIE_PREFIX}', fetch_salt(3) . '_', $config_file);
			$config_file = str_replace('{G_SECUKEY}', fetch_salt(12), $config_file);
			$config_file = str_replace('{G_COOKIE_HASH_KEY}', fetch_salt(15), $config_file);

			file_put_contents(AWS_PATH . 'config.inc.php', $config_file);
			file_put_contents(AWS_PATH . 'config/install.lock.php', time());
		}

		TPL::output('install/success');
		break;
}
