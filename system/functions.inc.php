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

/**
 * 根据特定规则对数组进行排序
 * @param  array $source_array 需要排序的数组
 * @param  string $order_field 提取多维数组的某个键名，以便把数组转换成一位数组进行排序（注意：不支持下标，否则排序会出错）
 * @param  string $sort_type 正序或倒序 'ASC'|'DESC'
 * @return array 返回排序后的数组
 */
function aasort($source_array, $order_field, $sort_type)
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
 * @return string
 */
function fetch_ip()
{
	if ($_SERVER['HTTP_X_FORWARDED_FOR'] and valid_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else if ($_SERVER['REMOTE_ADDR'] and $_SERVER['HTTP_CLIENT_IP'])
	{
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	else if ($_SERVER['REMOTE_ADDR'])
	{
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}
	else if ($_SERVER['HTTP_CLIENT_IP'])
	{
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	
	if ($ip_address === FALSE)
	{
		$ip_address = '0.0.0.0';
		
		return $ip_address;
	}
	
	if (strstr($ip_address, ','))
	{
		$x = explode(',', $ip_address);
		$ip_address = end($x);
	}
	
	return $ip_address;
}

/**
 * 校验ip有效性
 * @param  string $ip
 * @return bool
 */
function valid_ip($ip)
{
	return Zend_Validate::is($ip, 'Ip');
}

if (! function_exists('iconv'))
{
	/**
	 * 系统不开启iconv模块时，自建iconv()
	 * @param  string $from_encoding 源字符串的编码，默认GBK
	 * @param  string $target_encoding 目标字符串的编码，默认UTF-8
	 * @param  string $string 需要转换的字符串
	 * @return string 转码后的字符串
	 */
	function iconv($from_encoding = 'GBK', $target_encoding = 'UTF-8', $string)
	{
		return convert_encoding($string, $from_encoding, $target_encoding);
	}
}

if (! function_exists('iconv_substr'))
{

	function iconv_substr($string, $start, $length, $charset = 'UTF-8')
	{
		return mb_substr($string, $start, $length, $charset);
	}
}

if (! function_exists('iconv_strpos'))
{

	function iconv_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
	{
		return mb_strpos($haystack, $needle, $offset, $charset);
	}
}

/**
 * 兼容性转码
 * @param  strring $string 需要转换的字符串
 * @param  string $from_encoding 源字符串编码
 * @param  string $target_encoding 目标字符串编码
 * @return string 转码后的字符串
 */
function convert_encoding($string, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	if (function_exists('mb_convert_encoding'))
	{
		return mb_convert_encoding($string, str_replace('//IGNORE', '', strtoupper($target_encoding)), $from_encoding);
	}
	else
	{
		if (strtoupper($target_encoding) == 'GB2312' or strtoupper($target_encoding) == 'GBK')
		{
			$target_encoding .= '//IGNORE';
		}
		
		return iconv($from_encoding, $target_encoding, $string);
	}
}

function cjk_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
{
	if (function_exists('iconv_strpos'))
	{
		return iconv_strpos($haystack, $needle, $offset, $charset);
	}
	
	return mb_strpos($haystack, $needle, $offset, $charset);
}

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
 * @param  string  $dir 目录的绝对路径的字符串表示
 * @param  int $mode 目录权限，仅针对类unix
 * @return bool 
 */
function make_dir($dir, $mode = 0777)
{
	$dir = rtrim($dir, '/') . '/';
	
	if (is_dir($dir))
	{
		return TRUE;
	}
	
	if (! make_dir(dirname($dir), $mode))
	{
		return FALSE;
	}
	
	return @mkdir($dir, $mode);
}

/**
 * 获取头像地址
 * @param  int $uid 用户id
 * @param  string $size 三种头像尺寸 max(100px)|mid(50px)|min(32px)
 * @return string 返回完整url地址
 * 举个例子：$uid=12345，那么头像路径很可能(根据您部署的上传文件夹而定)会被存储为/uploads/000/01/23/45_avatar_min.jpg
 */
function get_avatar_url($uid, $size = 'min')
{
	$uid = intval($uid);
	
	if (!$uid)
	{
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.jpg';
	}
	
	foreach (AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
	{
		$all_size[] = $key;
	}
	
	$size = in_array($size, $all_size) ? $size : $all_size[0];
	
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	
	if (file_exists(get_setting('upload_dir') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg'))
	{
		return get_setting('upload_url') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg';
	}
	else
	{
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.jpg';
	}
}

function jsonp_encode($json = array(), $callback = 'jsoncallback')
{
	if ($_GET[$callback])
	{
		return $_GET[$callback] . '(' . json_encode($json) . ')';
	}
	
	return json_encode($json);
}

/**
 * 附件url地址，实际上是通过一定格式编码指配到/app/file/main.php中，让download控制器处理并发送下载请求
 * @param  string $file_name 附件的真实文件名，即上传之前的文件名称，包含后缀
 * @param  string $url 附件完整的真实url地址
 * @return string 附件下载的完整url地址
 */
function download_url($file_name, $url)
{	
	return get_js_url('/file/download/file_name-' . base64_encode($file_name) . '__url-' . base64_encode($url));
}

/**
 * 时间友好型提示风格化（即微博中的XXX小时前、昨天等等）
 * @param  int  $timestamp 需要处理的时间戳
 * @param  int $time_limit 过期的秒数，需要处理的时间过期后，按out_format的设定风格化时间戳
 * @param  string  $out_format 时间戳date()格式化风格，仅在time_style关闭状态或需要处理的时间戳超过time_limit预定时触发
 * @param  array  $formats 友好型sprintf()格式化风格，并遵循指定数组格式：array('YEAR' => '', 'MONTH' => '', 'DAY' => '', 'HOUR' => '', 'MINUTE' => '', 'SECOND' => '')，默认为XX小时前、XX秒前……
 * @param  int  $time_now 当前时间值
 * @return string 风格化后的时间表示
 */
function date_friendly($timestamp, $time_limit = 604800, $out_format = 'Y-m-d H:i', $formats = null, $time_now = null)
{
	if (get_setting('time_style') == 'N')
	{
		return date($out_format, $timestamp);
	}
	
	if ($formats == null)
	{
		$formats = array('YEAR' => '%s 年前', 'MONTH' => '%s 月前', 'DAY' => '%s 天前', 'HOUR' => '%s 小时前', 'MINUTE' => '%s 分钟前', 'SECOND' => '%s 秒前');
	}
	
	$time_now = $time_now == null ? time() : $time_now;
	$seconds = $time_now - $timestamp;
	
	if ($seconds == 0)
	{
		$seconds = 1;
	}
	
	if ($time_limit != null && $seconds > $time_limit)
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
 * 载入类库，并实例化、加入队列
 * @param  string $class 需要加载的类包，注意：路径从/system开始计算，并遵循zendframe路径表示法，即下划线"_"取代"/"，比如core_config表示/system/core/config.php
 * @return object 实例化后的对象
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

function _show_error($errorMessage = '')
{
	$name = strtoupper($_SERVER['HTTP_HOST']);
		
	if ($errorMessage)
	{
		$errorMessage = htmlspecialchars($errorMessage);
		
		$errorBlock = "<div class='system-error'><textarea rows='15' cols='60' onfocus='this.select()'>{$errorMessage}</textarea></div>";
	}
	
	if (defined('IN_AJAX'))
	{
		return $errorMessage;
	}
	
	return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="content-type" content="text/html; charset=UTF-8" /><meta http-equiv="Pragma" content="no-cache" /><meta http-equiv="Cache-Control" content="no-cache" /><meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" /><title>{$name} System Error</title><style type='text/css'>body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td{margin:0;padding:0;}table{border-collapse:collapse;border-spacing:0;}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:400;}ol,ul{list-style:none;}caption,th{text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:400;}q:before,q:after{content:'';}hr{display:none;}address{display:inline;}body{font-family:arial, tahoma, sans-serif;font-size:.8em;width:100%;}h1{font-family:arial, tahoma, "times new roman", serif;font-size:1.9em;color:#fff;}h2{font-size:1.6em;font-weight:400;clear:both;margin:0 0 8px;}a{color:#3e70a8;}a:hover{color:#3d8ce4;}#branding{background:#484848;padding:8px;}#content{clear:both;overflow:hidden;padding:20px 15px 0;}* #content{height:1%;}.message{background-color:#f5f5f5;clear:both;border-color:#d7d7d7;border-style:solid;border-width:1px;margin:0 0 10px;padding:7px 7px 7px 30px;border-radius:5px;}.message.error{background-color:#f3dddd;color:#281b1b;font-size:1.3em;font-weight:700;border-color:#deb7b7;}.message.unspecific{background-color:#f3f3f3;color:#515151;border-color:#d4d4d4;}.footer{text-align:center;font-size:1.5em;}.system-error{margin:10px 0;padding:5px 10px;}textarea{width:95%;height:300px;font-size:12px;font-family:Monaco,Lucida Console,Consolas,Courier,Courier New;line-height:16px;color:#474747;border:1px #bbb solid;border-radius:3px;padding:5px;}fieldset,img,abbr,acronym{border:0;}</style></head><body><div id='header'><div id='branding'><h1>{$name} System Error</h1></div></div><div id='content'><div class='message error'>There appears to be an error:{$errorBlock}</div><p class='message unspecific'>If you are seeing this page, it means there was a problem communicating with our database.  Sometimes this error is temporary and will go away when you refresh the page.  Sometimes the error will need to be fixed by an administrator before the site will become accessible again.<br /><br />You can try to refresh the page by clicking <a href="#" onclick="window.location=window.location; return false;">here</a></p></div></body></html>
EOF;
}

function show_error($errorMessage = '')
{
	@ob_end_clean();
	
	echo _show_error($errorMessage);
	
	exit;
}

/**
 * 获取数据库表名
 * @param  string $name 不包含前缀的表名
 * @return string 返回包含前缀的完整表名
 */
function get_table($name)
{
	return AWS_APP::config()->get('database')->prefix . $name;
}

/**
 * 获取全局配置项
 * @param  string $varname 指定需要获取的某一配置项
 * @return string|array 如果指定varname，则返回指定的配置项；如果不指定varname，则返回全部配置项
 */
function get_setting($varname = null, $permission_check = true)
{
	if (! class_exists('AWS_APP', false))
	{
		return false;
	}

	if ($settings = AWS_APP::$settings)
	{
		// AWS_APP::session()->permission是指当前用户所在用户组的权限许可项，在users_group表中，你可以看到permission字段
		if ($permission_check AND $settings['upload_enable'] == 'Y' AND ! AWS_APP::session()->permission['upload_attach'])
		{
			$settings['upload_enable'] = 'N';
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
 * @param  [type]  $file 物理路径
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
 * @param  integer $length 种子长度
 * @return string 随机的种子
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
 * 编译密码
 *
 *  @param  $password 	密码
 *  @param  $salt		混淆码
 * 	@return string		加密后的密码
 */
function compile_password($password, $salt)
{
	// md5 password...
	if (strlen($password) == 32)
	{
		return md5($password . $salt);
	}
	
	$password = md5(md5($password) . $salt);
	
	return $password;
}

/**
 * 伪静态构造器
 * @param  string $url 需要转换的请求地址
 * @return string 构造后的完整url地址
 */
function get_js_url($url)
{
	if (substr($url, 0, 1) == '/')
	{
		$url = substr($url, 1);
		
		if (get_setting('url_rewrite_enable') == 'Y' AND !defined('IN_MOBILE') AND $request_routes = get_request_route())
		{
			foreach ($request_routes as $key => $val)
			{
				if (preg_match('/' . $val[0] . '/', $url))
				{
					$url = preg_replace('/' . $val[0] . '/', $val[1], $url);
					
					break;
				}
			}
		}
		
		$url = get_setting('base_url') . '/' . ((get_setting('url_rewrite_enable') != 'Y' OR defined('IN_MOBILE')) ? G_INDEX_SCRIPT : '') . $url;
	}
	
	return $url;
}

/**
 * 用于分页查询SQL的limit参数
 * @param  int $page lime第一个参数，起始行
 * @param  int $per_page lime第二个参数，查询数量
 * @return string limit参数的字符串表示
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
 * 将用户登录信息编译成hash字符串，用于发送cookie
 * @param  string  $user_name 用户名
 * @param  string  $password 密码
 * @param  string  $salt 混淆码
 * @param  int  $uid 用户id
 * @param  boolean $hash_password 是否采用混淆器加密
 * @return string 返回编译后的hash字符串
 */
function get_login_cookie_hash($user_name, $password, $salt, $uid, $hash_password = true)
{
	if ($hash_password)
	{
		$password = compile_password($password, $salt);
	}
	
	return H::encode_hash(array(
		'user_name' => $user_name,
		'password' => $password,
		'uid' => $uid,
		'UA' => $_SERVER['HTTP_USER_AGENT']
	));
}

/**
 * 检查队列中是否存在指定的hash值，并移除之
 * @param  string $hash 需要检查的hash字符串
 * @return bool 是否检测到
 */
function valid_post_hash($hash)
{
	return AWS_APP::form()->valid_post_hash($hash);
}

/**
 * 创建一个新的hash字符串，并写入hash队列
 * @return string 创建的hash字符串
 */
function new_post_hash()
{
	if (! AWS_APP::session()->client_info)
	{
		return false;
	}
	
	return AWS_APP::form()->new_post_hash();
}

// 检测当前操作是否需要验证码
function human_valid($permission_tag)
{
	if (! is_array(AWS_APP::session()->human_valid))
	{
		return FALSE;
	}
	
	if (! AWS_APP::session()->human_valid[$permission_tag] or ! AWS_APP::session()->permission[$permission_tag])
	{
		return FALSE;
	}
	
	foreach (AWS_APP::session()->human_valid[$permission_tag] as $time => $val)
	{
		if (date('H', $time) != date('H', time()))
		{
			unset(AWS_APP::session()->human_valid[$permission_tag][$time]);
		}
	}
	
	if (sizeof(AWS_APP::session()->human_valid[$permission_tag]) >= AWS_APP::session()->permission[$permission_tag])
	{
		return TRUE;
	}
	
	return FALSE;
}

function set_human_valid($permission_tag)
{
	if (! is_array(AWS_APP::session()->human_valid))
	{
		return FALSE;
	}
	
	AWS_APP::session()->human_valid[$permission_tag][time()] = TRUE;
	
	return count(AWS_APP::session()->human_valid[$permission_tag]);
}

/**
 * 构造或解析路由规则后得到的请求地址数组
 * @param  boolean $positive true：通过真实请求地址构造伪静态| false：解析伪静态的真实请求地址
 * @return array 二位数组，每个规则占据一条，被处理的地址通过下标为0返回，处理后的地址通过下标为1返回
 */
function get_request_route($positive = true)
{
	$route_data = (get_setting('request_route') == 99) ? get_setting('request_route_custom') : get_setting('request_route_sys_' . get_setting('request_route'));
	
	if ($request_routes = explode("\n", $route_data))
	{
		$routes = array();
		
		$replace_array = array("(:any)" => "([^\"'&#\?\/]+[&#\?\/]*[^\"'&#\?\/]*)", "(:num)" => "([0-9]+)");
		
		foreach ($request_routes as $key => $val)
		{
			$val = trim($val);
			
			if (empty($val))
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
	else
	{
		return false;
	}
}

/**
 * 删除 ubb 标识码
 * @param  string $str 需要ubb处理的字符串
 * @return string      替换后的字符串
 */
function strip_ubb($str)
{
	$str = preg_replace('/\[attach\]([0-9]+)\[\/attach]/', '<i>** ' . AWS_APP::lang()->_t('插入的附件') . ' **</i>', $str);
	$str = preg_replace('/\[[^\]]+\](http[s]?:\/\/[^\[]*)\[\/[^\]]+\]/', "\$1 ", $str);
	
	return preg_replace('/\[[^\]]+\]([^\[]*)\[\/[^\]]+\]/', "\$1", $str);
}

/**
 * 仅附件处理中的preg_replace_callback()的每次搜索时的回调
 * @param  array $matches preg_replace_callback()搜索时返回给第二参数的结果
 * @return string  取出附件的加载模板字符串
 */
function parse_attachs_callback($matches)
{
	if ($attach = AWS_APP::model('publish')->get_attach_by_id($matches[1]))
	{
		TPL::assign('attach', $attach);
		
		return TPL::output('question/ajax/load_attach', false);
	}
}

/**
 * 获取主题图片指定尺寸的完整url地址
 * @param  string $size     三种图片尺寸 max(100px)|mid(50px)|min(32px)
 * @param  string $pic_file 某一尺寸的图片文件名
 * @return string           取出主题图片或主题默认图片的完整url地址
 */
function get_topic_pic_url($size = null, $pic_file = null)
{
	if ($sized_file = AWS_APP::model('topic')->get_sized_file($size, $pic_file))
	{
		return get_setting('upload_url') . '/topic/' . $sized_file;
	}
	else
	{
		if (! $size)
		{
			return G_STATIC_URL . '/common/topic-max-img.jpg';
		}
		
		return G_STATIC_URL . '/common/topic-' . $size . '-img.jpg';
	}
}

/**
 * 获取专题图片指定尺寸的完整url地址
 * @param  string $size     三种图片尺寸 max(100px)|mid(50px)|min(32px)
 * @param  string $pic_file 某一尺寸的图片文件名
 * @return string           取出专题图片的完整url地址
 */
function get_feature_pic_url($size = null, $pic_file = null)
{
	if (! $pic_file)
	{
		return false;
	}
	else
	{
		if ($size)
		{
			$pic_file = str_replace(AWS_APP::config()->get('image')->feature_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail['min']['h'], AWS_APP::config()->get('image')->feature_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail[$size]['h'], $pic_file);
		}
	}
	
	return get_setting('upload_url') . '/feature/' . $pic_file;
}

/**
 * 随机码
 * @param  array $arr 随机码阵列
 * @return string     最后一个单元的值
 */
function array_random($arr)
{
	shuffle($arr);
	
	return end($arr);
}

/**
 * 获得二维数据中第二维指定键对应的值，并组成新数组 (不支持二维数组)
 * @param  array $array 需要处理的数组
 * @param  string $key  第二维的指定键
 * @return array        组成新的数组
 */
function fetch_array_value($array, $key)
{
	if (! is_array($array) || empty($array))
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

function get_host_top_domain()
{
	$host = strtolower($_SERVER['HTTP_HOST']);
	
	if (strpos($host, '/') !== false)
	{
		$parse = @parse_url($host);
		$host = $parse['host'];
	}
	
	$top_level_domain_db = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me', 'jp', 'uk', 'ws', 'eu', 'pw', 'kr', 'io', 'us', 'cn');
	
	foreach ($top_level_domain_db as $v)
	{
		$str .= ($str ? '|' : '') . $v;
	}
	
	$matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
	
	if (preg_match('/' . $matchstr . '/ies', $host, $matchs))
	{
		$domain = $matchs['0'];
	}
	else
	{
		$domain = $host;
	}
	
	return $domain;
}

function parse_link_callback($matches)
{
	if (preg_match('/^(?!http).*/i', $matches[1]))
	{
		$url = 'http://' . $matches[1];
	}
	else
	{
		$url = $matches[1];
	}
	
	if (is_inside_url($url))
	{
		return '<a href="' . $url . '">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
	else
	{
		return '<a href="' . $url . '" rel="nofollow" target="_blank">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
}

function is_inside_url($url)
{
	if (!$url)
	{
		return false;
	}
	
	if (preg_match('/^(?!http).*/i', $url))
	{
		$url = 'http://' . $url;
	}
	
	$domain = get_host_top_domain();
	
	if (preg_match('/^http[s]?:\/\/([-_a-zA-Z0-9]+[\.])*?' . $domain . '(?!\.)[-a-zA-Z0-9@:;%_\+.~#?&\/\/=]*$/i', $url))
	{
		return true;
	}
	
	return false;
}

/**
 * 强制转换字符串为整型，对数字或数字字符串无效
 * @param  mixed $value 可以是任意的类型
 * @return void        引用传参
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
 * @return string 时差的字符串表示
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
 * @param  string $string  在语言包中的键名
 * @param  string $replace 替换值
 * @return void          输出格式化后的语言到客户端
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
 * @param  string $dir       读取的目录路径，可以是相对路径，也可以是绝对路径
 * @param  string $file_type 指定读取的文件后缀，如果不知道，则读取文件夹内所有的文件
 * @return array             指定的文件夹内指定后缀的文件路径的集合
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
 * @return boolean
 */
function is_mobile()
{
	$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
	if (preg_match('/playstation/i', $user_agent) OR preg_match('/ipad/i', $user_agent) OR preg_match('/ucweb/i', $user_agent))
	{
		return false;
	}
	
	if (preg_match('/iemobile/i', $user_agent) OR preg_match('/mobile\ssafari/i', $user_agent) OR preg_match('/iphone\sos/i', $user_agent) OR preg_match('/android/i', $user_agent))
	{
		return true;
	}
	
	return false;
}

function get_weixin_rule_image($image_file, $size = '')
{
	return AWS_APP::model('weixin')->get_weixin_rule_image($image_file, $size);
}

function curl_get_contents($url, $timeout = 10)
{
	if (!function_exists('curl_init'))
	{
		throw new Zend_Exception('CURL not support');
	}

	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	
	$result = curl_exec($curl);
	
	curl_close($curl);
	
	return $result;
}