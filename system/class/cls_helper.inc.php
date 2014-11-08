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

	public static function valid_email($email)
	{
		return Zend_Validate::is($email, 'EmailAddress');

		//return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) ? FALSE : TRUE;
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
	 * 加密 hash，生成发送给用户的 hash 字符串
	 *
	 * @param array $hash_data
	 * @param string $hash_key
	 * @return string
	 */
	public static function encode_hash($hash_data, $hash_key = null)
	{
		if (!$hash_data)
		{
			return false;
		}

		foreach ($hash_data as $key => $value)
		{
			$hash_string .= $key . "^]+" . $value . "!;-";
		}

		$hash_string = substr($hash_string, 0, - 3);

		// 加密干扰码，加密解密时需要用到的 key
		if (! $hash_key)
		{
			$hash_key = G_COOKIE_HASH_KEY;
		}

		// 加密过程
		for ($i = 1; $i <= strlen($hash_string); $i ++)
		{
			$char = substr($hash_string, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) + ord($keychar));
			$tmp_str .= $char;
		}

		$hash_string = base64_encode($tmp_str);

		$hash_string = str_replace(array(
			'+',
			'/',
			'='
		), array(
			'-',
			'_',
			'.'
		), $hash_string);

		return $hash_string;
	}

	/**
	 * 解密 hash，从用户回链的 hash 字符串解密出里面的内容
	 *
	 * @param string $hash_string
	 * @return array
	 */
	public static function decode_hash($hash_string, $hash_key = null)
	{
		if (!$hash_string)
		{
			return false;
		}

		// 加密干扰码，加密解密时需要用到的 Key
		if (! $hash_key)
		{
			$hash_key = G_COOKIE_HASH_KEY;
		}

		// 解密过程
		if (strpos($hash_string, '-') OR strpos($hash_string, '_') OR strpos($hash_string, '.'))
		{
			$hash_string = str_replace(array(
				'-',
				'_',
				'.'
			), array(
				'+',
				'/',
				'='
			), $hash_string);
		}

		$hash_string = base64_decode($hash_string);

		for ($i = 1; $i <= strlen($hash_string); $i ++)
		{
			$char = substr($hash_string, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) - ord($keychar));
			$tmp_str .= $char;
		}

		$hash_data = array();

		$arr = explode('!;-', $tmp_str);

		foreach ($arr as $value)
		{
			list($k, $v) = explode('^]+', $value);

			if ($k)
			{
				$hash_data[$k] = $v;
			}
		}

		return $hash_data;
	}

	/** 生成 Options **/
	public static function display_options($param, $default = '_DEFAULT_', $default_key = 'key')
	{
		if (is_array($param))
		{
			$keyindex = 0;

			foreach ($param as $key => $value)
			{
				if ($default_key == 'value')
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

		if (is_array($content) && $content)
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

			if (!$word)
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

			if (!$word)
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