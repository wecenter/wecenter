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

class H
{	
	public static function get_file_ext($file_name, $merge_type = true)
	{
		$file_ext = end(explode('.', $file_name));
		
		if ($merge_type)
		{
			if ($file_ext == 'jpeg' or $file_ext == 'jpe')
			{
				$file_ext = 'jpg';
			}
			
			if ($file_ext == 'htm')
			{
				$file_ext = 'html';
			}
		}
		
		return $file_ext;
	}

	/**
	 * 数组JSON返回
	 * 
	 * @param  $array 
	 */
	public static function ajax_json_output($array)
	{
		//HTTP::no_cache_header('text/javascript');
		
		echo str_replace(array("\r", "\n", "\t"), '', json_encode(H::sensitive_words($array)));
		exit;
	}

	/**
	 * 检查手机号码是否合法
	 * @param $moblie
	 * @return unknown_type
	 */
	public static function check_mobile_char($mobile)
	{	
		$mobile = trim($mobile);
		
		if (strlen($mobile) != 11)
		{
			return false;
		}
		
		$exp = '/^(((13[0-9]{1})|(15[0-9]{1}))+\d{8})/isU';
		
		return preg_match($exp, $mobile);
	
	}

	/**
	 * 是否电子邮件格式
	 * @param $email
	 * @return bool
	 */
	public static function valid_email($email)
	{
		if (!$email)
		{
			return false;
		}
		
		//return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
		
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) ? FALSE : TRUE;
	}
	
	public static function redirect_msg($message, $url = NULL, $interval = 5)
	{		
		TPL::assign('message', $message);
		TPL::assign('url_bit', HTTP::parse_redirect_url($url));
		TPL::assign('interval', $interval);

		TPL::output('global/show_message');
		die;
	}

	/**
	 * 加密hash，生成发送给用户的hash字符串
	 *
	 * @param array $hash_arr
	 * @return string
	 */
	public static function encode_hash($hash_arr, $hash_key = false)
	{
		if (empty($hash_arr))
		{
			return false;
		}
		
		$hash_str = "";
		
		foreach ($hash_arr as $key => $value)
		{
			$hash_str .= $key . "^]+" . $value . "!;-";
		}
		
		$hash_str = substr($hash_str, 0, - 3);
		
		// 加密干扰码，加密解密时需要用到的KEY
		if (! $hash_key)
		{
			$hash_key = G_COOKIE_HASH_KEY;
		}
		
		// 加密过程
		$tmp_str = '';
		
		for ($i = 1; $i <= strlen($hash_str); $i ++)
		{
			$char = substr($hash_str, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) + ord($keychar));
			$tmp_str .= $char;
		}
		
		$hash_str = base64_encode($tmp_str);
		$hash_str = str_replace(array(
			'+', 
			'/', 
			'='
		), array(
			'-', 
			'_', 
			'.'
		), $hash_str);
		//$hash_str	=	urlencode($hash_str);
		

		return $hash_str;
	}

	/**
	 * 解密hash，从用户回链的hash字符串解密出里面的内容
	 *
	 * @param string $hash_str
	 * @param boolean $b_urldecode	当$hash_str不是通过浏览器传递的时候就需要urldecode,否则会解密失败，反之也一样
	 * @return array
	 */
	public static function decode_hash($hash_str, $b_urldecode = false, $hash_key = false)
	{
		if (empty($hash_str))
		{
			return array();
		}
			
			// 加密干扰码，加密解密时需要用到的KEY
		if (! $hash_key)
		{
			$hash_key = G_COOKIE_HASH_KEY;
		}
		
		//解密过程
		$tmp_str = '';
		
		if (strpos($hash_str, "-") || strpos($hash_str, "_") || strpos($hash_str, "."))
		{
			$hash_str = str_replace(array(
				'-', 
				'_', 
				'.'
			), array(
				'+', 
				'/', 
				'='
			), $hash_str);
		}
		
		$hash_str = base64_decode($hash_str);
		
		for ($i = 1; $i <= strlen($hash_str); $i ++)
		{
			$char = substr($hash_str, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) - ord($keychar));
			$tmp_str .= $char;
		}
		
		$hash_arr = array();
		$arr = explode("!;-", $tmp_str);
		
		foreach ($arr as $value)
		{
			list($k, $v) = explode("^]+", $value);
			if ($k)
			{
				$hash_arr[$k] = $v;
			}
		}
		
		return $hash_arr;
	}

	/** 生成 Options **/
	public static function display_options($param, $default = '_DEFAULT_', $default_key='key')
	{
		$output = '';
		
		if (is_array($param))
		{
			$keyindex = 0;
			
			foreach ($param as $key => $value)
			{	
				if ($default_key=='value')
				{
					$output .= '<option value="' . $key . '"' . (($value == $default) ? '  selected' : '') . '>' . $value . '</option>';
				}
				else
				{
					$output .= '<option value="' . $key . '"' . (($key == $default) ? '  selected' : '') . '>' . $value . '</option>';
				}
			}
			
		}
		
		return $output;
	}
	
	public static function get_common_email($email)
	{
		if (!self::valid_email($email))
		{
			return false;
		}
		
		$email_domain = substr(stristr($email, '@'), 1);
		
		$common_email = (array)AWS_APP::config()->get('common_email');
		
		if ($common_email[$email_domain])
		{
			return $common_email[$email_domain];
		}
		else
		{
			return array(
					'name' => $email_domain,
					'url' => 'http://www.' . $email_domain,
			);
		}
	}

	/**
	 * 敏感词替换
	 * @param unknown_type $content
	 * @param unknown_type $replace
	 * @return mixed
	 */
	public static function sensitive_words($content, $replace = '*')
	{
		if (!$content or !get_setting('sensitive_words'))
		{
			return $content;
		}
		
		if (is_array($content) && !empty($content))
		{
			foreach($content as $key => $val)
			{
				$content[$key] = self::sensitive_words($val, $replace);
			}
			
			return $content;
		}
		
		$sensitive_words = explode("\n", get_setting('sensitive_words'));
	
		foreach($sensitive_words as $word)
		{
			$word = trim($word);
			
			if (empty($word))
			{
				continue;
			}
			
			$replace_str = '';
			
			$word_length = cjk_strlen($word);
	
			for($i = 0; $i < $word_length; $i++)
			{
				$replace_str .=  $replace;
			}
	
			$content = str_replace($word, $replace_str, $content);
		}

		return $content;
	}

	/**
	 * 是否包含敏感词
	 * @param unknown_type $content
	 * @param unknown_type $replace
	 * @return mixed
	 */
	public static function sensitive_word_exists($content)
	{
		if (!$content or !get_setting('sensitive_words'))
		{
			return false;
		}
		
		if (is_array($content))
		{
			foreach($content as $key => $val)
			{
				$content[$key] = self::sensitive_word_exists($val);
			}
			
			return $content;
		}
		
		$sensitive_words = explode("\n", get_setting('sensitive_words'));
	
		foreach($sensitive_words as $word)
		{
			$word = trim($word);
			
			if (empty($word))
			{
				continue;
			}
			
			if (strstr($content, $word))
			{
				return true;
			}
		}

		return false;
	}
}