<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package		WeCenter Framework
 * @author		WeCenter Dev Team
 * @copyright	Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license		http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since		Version 1.0
 * @filesource
 */

/**
 * WeCenter 系统函数类
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */

/**
 * 获取站点根目录 URL
 *
 * @return string
 */
function base_url()
{
	$clean_url = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : NULL;
	$clean_url = dirname(substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF']) - strlen($clean_url)));
	$clean_url = rtrim($_SERVER['HTTP_HOST'] . $clean_url, '/\\');

	if ((isset($_SERVER['HTTPS']) AND !in_array(strtolower($_SERVER['HTTPS']), array('off', 'no', 'false', 'disabled'))) OR $_SERVER['SERVER_PORT'] == 443)
	{
		$scheme = 'https';
	}
	else
	{
		$scheme = 'http';
	}

	return $scheme . '://' . $clean_url;
}

function base64_current_path()
{
	return base64_encode('/' . str_replace('/' . G_INDEX_SCRIPT, '', substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['PHP_SELF'])))));
}

/**
 * 根据特定规则对数组进行排序
 *
 * 提取多维数组的某个键名，以便把数组转换成一位数组进行排序（注意：不支持下标，否则排序会出错）
 *
 * @param  array
 * @param  string
 * @param  string
 * @return array
 */
function aasort($source_array, $order_field, $sort_type = 'DESC')
{
	if (! is_array($source_array) or sizeof($source_array) == 0)
	{
		return false;
	}

	foreach ($source_array as $array_key => $array_row)
	{
		$sort_array[$array_key] = $array_row[$order_field];
	}

	$sort_func = ($sort_type == 'ASC' ? 'asort' : 'arsort');

	$sort_func($sort_array);

	// 重组数组
	foreach ($sort_array as $key => $val)
	{
		$sorted_array[$key] = $source_array[$key];
	}

	return $sorted_array;
}

/**
 * 获取用户 IP
 *
 * @return string
 */
function fetch_ip()
{
	if ($_SERVER['HTTP_X_FORWARDED_FOR'] and valid_internal_ip($_SERVER['REMOTE_ADDR']))
	{
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}

	if ($ip_address)
	{
		if (strstr($ip_address, ','))
		{
			$x = explode(',', $ip_address);
			$ip_address = end($x);
		}
	}

	if (!valid_ip($ip_address) AND $_SERVER['REMOTE_ADDR'])
	{
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}

	if (!valid_ip($ip_address))
	{
		$ip_address = '0.0.0.0';
	}

	return $ip_address;
}

/**
 * 验证 IP 地址是否为内网 IP
 *
 * @param string
 * @return string
 */
function valid_internal_ip($ip)
{
	if (!valid_ip($ip))
	{
		return false;
	}

	$ip_address = explode('.', $ip);

	if ($ip_address[0] == 10)
	{
		return true;
	}

	if ($ip_address[0] == 172 and $ip_address[1] > 15 and $ip_address[1] < 32)
	{
		return true;
	}

	if ($ip_address[0] == 192 and $ip_address[1] == 168)
	{
		return true;
	}

	return false;
}

/**
 * 校验 IP 有效性
 *
 * @param  string
 * @return boolean
 */
function valid_ip($ip)
{
	return Zend_Validate::is($ip, 'Ip');
}

/**
 * 检查整型、字符串或数组内的字符串是否为纯数字（十进制数字，不包括负数和小数）
 *
 * @param integer or string or array
 * @return boolean
 */
function is_digits($num)
{
	if (!$num AND $num !== 0 AND $num !== '0')
	{
		return false;
	}

	if (is_array($num))
	{
		foreach ($num AS $val)
		{
			if (!is_digits($val))
			{
				return false;
			}
		}

		return true;
	}

	return Zend_Validate::is($num, 'Digits');
}

if (! function_exists('iconv'))
{
	/**
	 * 系统不开启 iconv 模块时, 自建 iconv(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string
	 */
	function iconv($from_encoding = 'GBK', $target_encoding = 'UTF-8', $string)
	{
		return convert_encoding($string, $from_encoding, $target_encoding);
	}
}

if (! function_exists('iconv_substr'))
{
	/**
	 * 系统不开启 iconv_substr 模块时, 自建 iconv_substr(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  string
	 * @return string
	 */
	function iconv_substr($string, $start, $length, $charset = 'UTF-8')
	{
		return mb_substr($string, $start, $length, $charset);
	}
}

if (! function_exists('iconv_strpos'))
{
	/**
	 * 系统不开启 iconv_substr 模块时, 自建 iconv_strpos(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  string
	 * @return string
	 */
	function iconv_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
	{
		return mb_strpos($haystack, $needle, $offset, $charset);
	}
}

/**
 * 兼容性转码
 *
 * 系统转换编码调用此函数, 会自动根据当前环境采用 iconv 或 MB String 处理
 *
 * @param  string
 * @param  string
 * @param  string
 * @return string
 */
function convert_encoding($string, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	if (function_exists('mb_convert_encoding'))
	{
		return mb_convert_encoding($string, str_replace('//IGNORE', '', strtoupper($target_encoding)), $from_encoding);
	}
	else
	{
		if (strtoupper($from_encoding) == 'UTF-16')
		{
			$from_encoding = 'UTF-16BE';
		}

		if (strtoupper($target_encoding) == 'UTF-16')
		{
			$target_encoding = 'UTF-16BE';
		}

		if (strtoupper($target_encoding) == 'GB2312' or strtoupper($target_encoding) == 'GBK')
		{
			$target_encoding .= '//IGNORE';
		}

		return iconv($from_encoding, $target_encoding, $string);
	}
}

/**
 * 兼容性转码 (数组)
 *
 * 系统转换编码调用此函数, 会自动根据当前环境采用 iconv 或 MB String 处理, 支持多维数组转码
 *
 * @param  array
 * @param  string
 * @param  string
 * @return array
 */
function convert_encoding_array($data, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	return eval('return ' . convert_encoding(var_export($data, true) . ';', $from_encoding, $target_encoding));
}

/**
 * 双字节语言版 strpos
 *
 * 使用方法同 strpos()
 *
 * @param  string
 * @param  string
 * @param  int
 * @param  string
 * @return string
 */
function cjk_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
{
	if (function_exists('iconv_strpos'))
	{
		return iconv_strpos($haystack, $needle, $offset, $charset);
	}

	return mb_strpos($haystack, $needle, $offset, $charset);
}

/**
 * 双字节语言版 substr
 *
 * 使用方法同 substr(), $dot 参数为截断后带上的字符串, 一般场景下使用省略号
 *
 * @param  string
 * @param  int
 * @param  int
 * @param  string
 * @param  string
 * @return string
 */
function cjk_substr($string, $start, $length, $charset = 'UTF-8', $dot = '')
{
	if (cjk_strlen($string, $charset) <= $length)
	{
		return $string;
	}

	if (function_exists('mb_substr'))
	{
		return mb_substr($string, $start, $length, $charset) . $dot;
	}
	else
	{
		return iconv_substr($string, $start, $length, $charset) . $dot;
	}
}

/**
 * 双字节语言版 strlen
 *
 * 使用方法同 strlen()
 *
 * @param  string
 * @param  string
 * @return string
 */
function cjk_strlen($string, $charset = 'UTF-8')
{
	if (function_exists('mb_strlen'))
	{
		return mb_strlen($string, $charset);
	}
	else
	{
		return iconv_strlen($string, $charset);
	}
}

/**
 * 递归创建目录
 *
 * 与 mkdir 不同之处在于支持一次性多级创建, 比如 /dir/sub/dir/
 *
 * @param  string
 * @param  int
 * @return boolean
 */
function make_dir($dir, $permission = 0777)
{
	$dir = rtrim($dir, '/') . '/';

	if (is_dir($dir))
	{
		return TRUE;
	}

	if (! make_dir(dirname($dir), $permission))
	{
		return FALSE;
	}

	return @mkdir($dir, $permission);
}

/**
 * jQuery jsonp 调用函数
 *
 * 用法同 json_encode
 *
 * @param  array
 * @param  string
 * @return string
 */
function jsonp_encode($json = array(), $callback = 'jsoncallback')
{
	if ($_GET[$callback])
	{
		return $_GET[$callback] . '(' . json_encode($json) . ')';
	}

	return json_encode($json);
}

/**
 * 时间友好型提示风格化（即微博中的XXX小时前、昨天等等）
 *
 * 即微博中的 XXX 小时前、昨天等等, 时间超过 $time_limit 后返回按 out_format 的设定风格化时间戳
 *
 * @param  int
 * @param  int
 * @param  string
 * @param  array
 * @param  int
 * @return string
 */
function date_friendly($timestamp, $time_limit = 604800, $out_format = 'Y-m-d H:i', $formats = null, $time_now = null)
{
	if (get_setting('time_style') == 'N')
	{
		return date($out_format, $timestamp);
	}

	if (!$timestamp)
	{
		return false;
	}

	if ($formats == null)
	{
		$formats = array('YEAR' => AWS_APP::lang()->_t('%s 年前'), 'MONTH' => AWS_APP::lang()->_t('%s 月前'), 'DAY' => AWS_APP::lang()->_t('%s 天前'), 'HOUR' => AWS_APP::lang()->_t('%s 小时前'), 'MINUTE' => AWS_APP::lang()->_t('%s 分钟前'), 'SECOND' => AWS_APP::lang()->_t('%s 秒前'));
	}

	$time_now = $time_now == null ? time() : $time_now;
	$seconds = $time_now - $timestamp;

	if ($seconds == 0)
	{
		$seconds = 1;
	}

	if (!$time_limit OR $seconds > $time_limit)
	{
		return date($out_format, $timestamp);
	}

	$minutes = floor($seconds / 60);
	$hours = floor($minutes / 60);
	$days = floor($hours / 24);
	$months = floor($days / 30);
	$years = floor($months / 12);

	if ($years > 0)
	{
		$diffFormat = 'YEAR';
	}
	else
	{
		if ($months > 0)
		{
			$diffFormat = 'MONTH';
		}
		else
		{
			if ($days > 0)
			{
				$diffFormat = 'DAY';
			}
			else
			{
				if ($hours > 0)
				{
					$diffFormat = 'HOUR';
				}
				else
				{
					$diffFormat = ($minutes > 0) ? 'MINUTE' : 'SECOND';
				}
			}
		}
	}

	$dateDiff = null;

	switch ($diffFormat)
	{
		case 'YEAR' :
			$dateDiff = sprintf($formats[$diffFormat], $years);
			break;
		case 'MONTH' :
			$dateDiff = sprintf($formats[$diffFormat], $months);
			break;
		case 'DAY' :
			$dateDiff = sprintf($formats[$diffFormat], $days);
			break;
		case 'HOUR' :
			$dateDiff = sprintf($formats[$diffFormat], $hours);
			break;
		case 'MINUTE' :
			$dateDiff = sprintf($formats[$diffFormat], $minutes);
			break;
		case 'SECOND' :
			$dateDiff = sprintf($formats[$diffFormat], $seconds);
			break;
	}

	return $dateDiff;
}

/**
 * 载入类库, 并实例化、加入队列
 *
 * 路径从 system 开始计算，并遵循 Zend Freamework 路径表示法，即下划线 _ 取代 / , 如 core_config 表示 system/core/config.php
 *
 * @param  string
 * @return object
 */
function &load_class($class)
{
	static $_classes = array();

	// Does the class exist?  If so, we're done...
	if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}

	if (class_exists($class) === FALSE)
	{
		$file = AWS_PATH . preg_replace('#_+#', '/', $class) . '.php';

		if (! file_exists($file))
		{
			throw new Zend_Exception('Unable to locate the specified class: ' . $class . ' ' . preg_replace('#_+#', '/', $class) . '.php');
		}

		require_once $file;
	}

	$_classes[$class] = new $class();

	return $_classes[$class];
}

function _show_error($exception_message)
{
	$name = strtoupper($_SERVER['HTTP_HOST']);

	if ($exception_message)
	{
		$exception_message = htmlspecialchars($exception_message);

		$errorBlock = "<div style='display:none' id='exception_message'><textarea rows='15' onfocus='this.select()'>{$exception_message}</textarea></div>";
	}

	if (defined('IN_AJAX'))
	{
		return $exception_message;
	}

	return <<<EOF
<!DOCTYPE html><html><head><title>Error</title><style type='text/css'>body{background:#f9f9f9;margin:0;padding:30px 20px;font-family:"Helvetica Neue",helvetica,arial,sans-serif}#error{max-width:800px;background:#fff;margin:0 auto}h1{background:#151515;color:#fff;font-size:22px;font-weight:500;padding:10px}h1 span{color:#7a7a7a;font-size:14px;font-weight:400}#content{padding:20px;line-height:1.6}#reload_button{background:#151515;color:#fff;border:0;line-height:34px;padding:0 15px;font-family:"Helvetica Neue",helvetica,arial,sans-serif;font-size:14px;border-radius:3px}textarea{width:95%;height:300px;font-size:11px;font-family:"Helvetica Neue Ultra Light", Monaco,Lucida Console,Consolas,Courier,Courier New;line-height:16px;color:#474747;border:1px #bbb solid;border-radius:3px;padding:5px;}</style></head><body onkeydown="if (event.keyCode == 68) { document.getElementById('exception_message').style.display = 'block' }"><div id='error'><h1>An error occurred <span>(500 Error)</span></h1><div id='content'>We're sorry, but a temporary technical error has occurred which means we cannot display this site right now.<br /><br />You can try again by clicking the button below, or try again later.<br /><br />{$errorBlock}<br /><button onclick="window.location.reload();" id='reload_button'>Try again</button></div></div></body></html>
EOF;
}

function show_error($exception_message, $error_message = '')
{
	@ob_end_clean();

	if (get_setting('report_diagnostics') == 'Y' AND class_exists('AWS_APP', false))
	{
		AWS_APP::mail()->send('wecenter_report@outlook.com', '[' . G_VERSION . '][' . G_VERSION_BUILD . '][' . base_url() . ']' . $error_message, nl2br($exception_message), get_setting('site_name'), 'WeCenter');
	}

	if (isset($_SERVER['SERVER_PROTOCOL']) AND strstr($_SERVER['SERVER_PROTOCOL'], '/1.0') !== false)
	{
		header("HTTP/1.0 500 Internal Server Error");
	}
	else
	{
		header("HTTP/1.1 500 Internal Server Error");
	}

	echo _show_error($exception_message);
	exit;
}

/**
 * 获取带表前缀的数据库表名
 *
 * @param  string
 * @return string
 */
function get_table($name)
{
	return AWS_APP::config()->get('database')->prefix . $name;
}

/**
 * 获取全局配置项
 *
 * 如果指定 varname 则返回指定的配置项, 如果不指定 varname 则返回全部配置项
 *
 * @param  string
 * @return mixed
 */
function get_setting($varname = null, $permission_check = true)
{
	if (! class_exists('AWS_APP', false))
	{
		return false;
	}

	if ($settings = AWS_APP::$settings)
	{
		// AWS_APP::session()->permission 是指当前用户所在用户组的权限许可项，在 users_group 表中，你可以看到 permission 字段
		if ($permission_check AND $settings['upload_enable'] == 'Y')
		{
			if (AWS_APP::session())
			{
				if (!AWS_APP::session()->permission['upload_attach'])
				{
					$settings['upload_enable'] = 'N';
				}
			}
		}
	}

	if ($varname)
	{
		return $settings[$varname];
	}
	else
	{
		return $settings;
	}
}

// ------------------------------------------------------------------------


/**
 * 判断文件或目录是否可写
 *
 * @param  string
 * @return boolean
 */
function is_really_writable($file)
{
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' and @ini_get('safe_mode') == FALSE)
	{
		return is_writable($file);
	}

	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.  Bah...
	if (is_dir($file))
	{
		$file = rtrim($file, '/') . '/is_really_writable_' . md5(rand(1, 100));

		if (! @file_put_contents($file, 'is_really_writable() test file'))
		{
			return FALSE;
		}
		else
		{
			@unlink($file);
		}

		return TRUE;
	}
	else if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
	{
		return FALSE;
	}

	return TRUE;
}

/**
 * 生成密码种子
 *
 * @param  integer
 * @return string
 */
function fetch_salt($length = 4)
{
	for ($i = 0; $i < $length; $i++)
	{
		$salt .= chr(rand(97, 122));
	}

	return $salt;
}

/**
 * 根据 salt 混淆密码
 *
 * @param  string
 * @param  string
 * @return string
 */
function compile_password($password, $salt)
{
	$password = md5(md5($password) . $salt);

	return $password;
}

/**
 * 伪静态地址转换器
 *
 * @param  string
 * @return string
 */
function get_js_url($url)
{
	if (substr($url, 0, 1) == '/')
	{
		$url = substr($url, 1);

		if (get_setting('url_rewrite_enable') == 'Y' AND $request_routes = get_request_route())
		{
			if (strstr($url, '?'))
			{
				$request_uri = explode('?', $url);

				$query_string = $request_uri[1];

				$url = $request_uri[0];
			}
			else
			{
				unset($query_string);
			}

			foreach ($request_routes as $key => $val)
			{
				if (preg_match('/^' . $val[0] . '$/', $url))
				{
					$url = preg_replace('/^' . $val[0] . '$/', $val[1], $url);

					break;
				}
			}

			if ($query_string)
			{
				$url .= '?' . $query_string;
			}
		}

		$url = base_url() . '/' . ((get_setting('url_rewrite_enable') != 'Y') ? G_INDEX_SCRIPT : '') . $url;
	}

	return $url;
}

/**
 * 用于分页查询 SQL 的 limit 参数生成器
 *
 * @param  int
 * @param  int
 * @return string
 */
function calc_page_limit($page, $per_page)
{
	if (intval($per_page) == 0)
	{
		throw new Zend_Exception('Error param: per_page');
	}

	if ($page < 1)
	{
		$page = 1;
	}

	return ((intval($page) - 1) * intval($per_page)) . ', ' . intval($per_page);
}

/**
 * 将用户登录信息编译成 hash 字符串，用于发送 Cookie
 *
 * @param  string
 * @param  string
 * @param  string
 * @param  integer
 * @param  boolean
 * @return string
 */
function get_login_cookie_hash($user_name, $password, $salt, $uid, $hash_password = true)
{
	if ($hash_password)
	{
		$password = compile_password($password, $salt);
	}

	$auth_hash_key = md5(G_COOKIE_HASH_KEY . $_SERVER['HTTP_USER_AGENT']);

	return AWS_APP::crypt()->encode(json_encode(array(
		'uid' => $uid,
		'user_name' => $user_name,
		'password' => $password
	)), $auth_hash_key);
}

/**
 * 检查队列中是否存在指定的 hash 值, 并移除之, 用于表单提交验证
 *
 * @param  string
 * @return boolean
 */
function valid_post_hash($hash)
{
	return AWS_APP::form()->valid_post_hash($hash);
}

/**
 * 创建一个新的 hash 字符串，并写入 hash 队列, 用于表单提交验证
 *
 * @return string
 */
function new_post_hash()
{
	if (! AWS_APP::session()->client_info)
	{
		return false;
	}

	return AWS_APP::form()->new_post_hash();
}

/**
 * 构造或解析路由规则后得到的请求地址数组
 *
 * 返回二维数组, 二位数组, 每个规则占据一条, 被处理的地址通过下标 0 返回, 处理后的地址通过下标 1 返回
 *
 * @param  boolean
 * @return array
 */
function get_request_route($positive = true)
{
	if (!$route_data = get_setting('request_route_custom'))
	{
		return false;
	}

	if ($request_routes = explode("\n", $route_data))
	{
		$routes = array();

		$replace_array = array("(:any)" => "([^\"'&#\?\/]+[&#\?\/]*[^\"'&#\?\/]*)", "(:num)" => "([0-9]+)");

		foreach ($request_routes as $key => $val)
		{
			$val = trim($val);

			if (!$val)
			{
				continue;
			}

			if ($positive)
			{
				list($pattern, $replace) = explode('===', $val);
			}
			else
			{
				list($replace, $pattern) = explode('===', $val);
			}

			if (substr($pattern, 0, 1) == '/' and $pattern != '/')
			{
				$pattern = substr($pattern, 1);
			}

			if (substr($replace, 0, 1) == '/' and $replace != '/')
			{
				$replace = substr($replace, 1);
			}

			$pattern = addcslashes($pattern, "/\.?");

			$pattern = str_replace(array_keys($replace_array), array_values($replace_array), $pattern);

			$replace = str_replace(array_keys($replace_array), "\$1", $replace);

			$routes[] = array($pattern, $replace);
		}

		return $routes;
	}
}

/**
 * 删除 UBB 标识码
 *
 * @param  string
 * @return string
 */
function strip_ubb($str)
{
	//$str = preg_replace('/\[attach\]([0-9]+)\[\/attach]/', '<i>** ' . AWS_APP::lang()->_t('插入的附件') . ' **</i>', $str);
	$str = preg_replace('/\[[^\]]+\](http[s]?:\/\/[^\[]*)\[\/[^\]]+\]/', ' $1 ', $str);

	$pattern = '/\[[^\]]+\]([^\[]*)\[\/[^\]]+\]/';
	$replacement = ' $1 ';
	return preg_replace($pattern, $replacement, preg_replace($pattern, $replacement, $str));
}

/**
 * 获取数组中随机一条数据
 *
 * @param  array
 * @return mixed
 */
function array_random($arr)
{
	shuffle($arr);

	return end($arr);
}

/**
 * 获得二维数据中第二维指定键对应的值，并组成新数组 (不支持二维数组)
 *
 * @param  array
 * @param  string
 * @return array
 */
function fetch_array_value($array, $key)
{
	if (!$array || ! is_array($array))
	{
		return array();
	}

	$data = array();

	foreach ($array as $_key => $val)
	{
		$data[] = $val[$key];
	}

	return $data;
}

/**
 * 强制转换字符串为整型, 对数字或数字字符串无效
 *
 * @param  mixed
 */
function intval_string(&$value)
{
	if (! is_numeric($value))
	{
		$value = intval($value);
	}
}

/**
 * 获取时差
 *
 * @return string
 */
function get_time_zone()
{
	$time_zone = 0 + (date('O') / 100);

	if ($time_zone == 0)
	{
		return '';
	}

	if ($time_zone > 0)
	{
		return '+' . $time_zone;
	}

	return $time_zone;
}

/**
 * 格式化输出相应的语言
 *
 * 根据语言包中数组键名的下标获取对应的翻译字符串
 *
 * @param  string
 * @param  string
 */
function _e($string, $replace = null)
{
	if (!class_exists('AWS_APP', false))
	{
		echo load_class('core_lang')->translate($string, $replace, TRUE);
	}
	else
	{
		echo AWS_APP::lang()->translate($string, $replace, TRUE);
	}
}

/**
 * 递归读取文件夹的文件列表
 *
 * 读取的目录路径可以是相对路径, 也可以是绝对路径, $file_type 为指定读取的文件后缀, 不设置则读取文件夹内所有的文件
 *
 * @param  string
 * @param  string
 * @return array
 */
function fetch_file_lists($dir, $file_type = null)
{
	if ($file_type)
	{
		if (substr($file_type, 0, 1) == '.')
		{
			$file_type = substr($file_type, 1);
		}
	}

	$base_dir = realpath($dir);

	if (!file_exists($base_dir))
	{
		return false;
	}

	$dir_handle = opendir($base_dir);

	$files_list = array();

	while (($file = readdir($dir_handle)) !== false)
	{
		if (substr($file, 0, 1) != '.' AND !is_dir($base_dir . '/' . $file))
		{
			if (($file_type AND H::get_file_ext($file, false) == $file_type) OR !$file_type)
			{
				$files_list[] = $base_dir . '/' . $file;
			}
		}
		else if (substr($file, 0, 1) != '.' AND is_dir($base_dir . '/' . $file))
		{
			if ($sub_dir_lists = fetch_file_lists($base_dir . '/' . $file, $file_type))
			{
				$files_list = array_merge($files_list, $sub_dir_lists);
			}
		}
	}

	return $files_list;
}

/**
 * 判断是否是合格的手机客户端
 *
 * @return boolean
 */
function is_mobile($ignore_cookie = false)
{
	if (HTTP::get_cookie('_ignore_ua_check') == 'TRUE' AND !$ignore_cookie)
	{
		return false;
	}

	$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if (preg_match('/playstation/i', $user_agent) OR preg_match('/ipad/i', $user_agent) OR preg_match('/ucweb/i', $user_agent))
	{
		return false;
	}

	if (preg_match('/iemobile/i', $user_agent) OR preg_match('/mobile\ssafari/i', $user_agent) OR preg_match('/iphone\sos/i', $user_agent) OR preg_match('/android/i', $user_agent) OR preg_match('/symbian/i', $user_agent) OR preg_match('/series40/i', $user_agent))
	{
		return true;
	}

	return false;
}

/**
 * 判断是否处于微信内置浏览器中
 *
 * @return boolean
 */
function in_weixin()
{
	$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if (preg_match('/micromessenger/i', $user_agent))
	{
		return true;
	}

	return false;
}

/**
 * CURL 获取文件内容
 *
 * 用法同 file_get_contents
 *
 * @param string
 * @param integerr
 * @return string
 */
function curl_get_contents($url, $timeout = 30)
{
	return HTTP::request($url, 'GET', null, $timeout);
}

/**
 * 删除网页上看不见的隐藏字符串, 如 Java\0script
 *
 * @param	string
 */
function remove_invisible_characters(&$str, $url_encoded = TRUE)
{
	$non_displayables = array();

	// every control character except newline (dec 10)
	// carriage return (dec 13), and horizontal tab (dec 09)

	if ($url_encoded)
	{
		$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
		$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
	}

	$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

	do
	{
		$str = preg_replace($non_displayables, '', $str, -1, $count);
	}
	while ($count);
}

/**
 * 生成一段时间的月份列表
 *
 * @param string
 * @param string
 * @param string
 * @param string
 * @return array
 */
function get_month_list($timestamp1, $timestamp2, $year_format = 'Y', $month_format = 'm')
{
	$yearsyn = date($year_format, $timestamp1);
	$monthsyn = date($month_format, $timestamp1);
	$daysyn = date('d', $timestamp1);

	$yearnow = date($year_format, $timestamp2);
	$monthnow = date($month_format, $timestamp2);
	$daynow = date('d', $timestamp2);

	if ($yearsyn == $yearnow)
	{
		$monthinterval = $monthnow - $monthsyn;
	}
	else if ($yearsyn < $yearnow)
	{
		$yearinterval = $yearnow - $yearsyn -1;
		$monthinterval = (12 - $monthsyn + $monthnow) + 12 * $yearinterval;
	}

	$timedata = array();
	for ($i = 0; $i <= $monthinterval; $i++)
	{
		$tmptime = mktime(0, 0, 0, $monthsyn + $i, 1, $yearsyn);
		$timedata[$i]['year'] = date($year_format, $tmptime);
		$timedata[$i]['month'] = date($month_format, $tmptime);
		$timedata[$i]['beginday'] = '01';
		$timedata[$i]['endday'] = date('t', $tmptime);
	}

	$timedata[0]['beginday'] = $daysyn;
	$timedata[$monthinterval]['endday'] = $daynow;

	unset($tmptime);

	return $timedata;
}

/**
 * EML 文件解码
 *
 * @param string
 * @return string
 */
function decode_eml($string)
{
	$pos = strpos($string, '=?');

	if (!is_int($pos))
	{
		return $string;
	}

	$preceding = substr($string, 0, $pos);	// save any preceding text
	$search = substr($string, $pos + 2);	// the mime header spec says this is the longest a single encoded word can be
	$part_1 = strpos($search, '?');

	if (!is_int($part_1))
	{
		return $string;
	}

	$charset = substr($string, $pos + 2, $part_1);	// 取出字符集的定义部分
	$search = substr($search, $part_1 + 1);	// 字符集定义以后的部分 => $search

	$part_2 = strpos($search, '?');

	if (!is_int($part_2))
	{
		return $string;
	}

	$encoding = substr($search, 0, $part_2);	// 两个?　之间的部分编码方式: q 或 b　
	$search = substr($search, $part_2 + 1);
	$end = strpos($search, '?=');	// $part_2 + 1 与 $end 之间是编码了的内容: => $endcoded_text;

	if (!is_int($end))
	{
		return $string;
	}

	$encoded_text = substr($search, 0, $end);
	$rest = substr($string, (strlen($preceding . $charset . $encoding . $encoded_text) + 6));	// + 6 是前面去掉的 =????= 六个字符

	switch (strtolower($encoding))
	{
		case 'q':
			$decoded = quoted_printable_decode($encoded_text);

			if (strtolower($charset) == 'windows-1251')
			{
				$decoded = convert_cyr_string($decoded, 'w', 'k');
			}
		break;

		case 'b':
			$decoded = base64_decode($encoded_text);

			if (strtolower($charset) == 'windows-1251')
			{
				$decoded = convert_cyr_string($decoded, 'w', 'k');
			}
		break;

		default:
			$decoded = '=?' . $charset . '?' . $encoding . '?' . $encoded_text . '?=';
		break;
	}

	return $preceding . $decoded . decode_eml($rest);
}

function array_key_sort_asc_callback($a, $b)
{
	if ($a['sort'] == $b['sort'])
	{
		return 0;
	}

	return ($a['sort'] < $b['sort']) ? -1 : 1;
}

function get_random_filename($dir, $file_ext)
{
	if (!$dir OR !file_exists($dir))
	{
		return false;
	}

	$dir = rtrim($dir, '/') . '/';

	$filename = md5(mt_rand(1, 99999999) . microtime());

	if (file_exists($dir . $filename . '.' . $file_ext))
	{
		return get_random_filename($dir, $file_ext);
	}

	return $filename . '.' . $file_ext;
}

function check_extension_package($package)
{
	if (!file_exists(ROOT_PATH . 'models/' . $package . '.php'))
	{
		return false;
	}

	return true;
}

function get_left_days($timestamp)
{
	$left_days = intval(($timestamp - time()) / (3600 * 24));

	if ($left_days < 0)
	{
		$left_days = 0;
	}

	return $left_days;
}

function get_paid_progress_bar($amount, $paid)
{
	if ($amount == 0)
	{
		return 0;
	}

	return intval(($paid / $amount) * 100);
}


function uniqid_generate($length = 16)
{
	return substr(strtolower(md5(uniqid(rand()))), 0, $length);
}
