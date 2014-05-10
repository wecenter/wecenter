<?php

/**
 * OAuth1.0 SDK Interface
 *
 * 提供给具体接口子类使用的一些公共方法
 *
 * @author icehu@vip.qq.com
 */

class Services_Tencent_OpenSDK_OAuth_Interface
{

	/**
	 * app key
	 * @var string
	 */
	protected static $_appkey = '';
	/**
	 * app secret
	 * @var string
	 */
	protected static $_appsecret = '';

	/**
	 * OAuth 版本
	 * @var string
	 */
	protected static $version = '1.0';
	
	const RETURN_JSON = 'json';
	const RETURN_XML = 'xml';
	/**
	 * 初始化
	 * @param string $appkey
	 * @param string $appsecret
	 */
	public static function init($appkey,$appsecret)
	{
		self::setAppkey($appkey, $appsecret);
	}
	/**
	 * 设置APP Key 和 APP Secret
	 * @param string $appkey
	 * @param string $appsecret
	 */
	protected static function setAppkey($appkey,$appsecret)
	{
		self::$_appkey = $appkey;
		self::$_appsecret = $appsecret;
	}

	protected static $timestampFunc = null;

	/**
	 * 获得本机时间戳的方法
	 * 如果服务器时钟存在误差，在这里调整
	 * 
	 * @return number
	 */
	public static function getTimestamp()
	{
		if(null !== self::$timestampFunc && is_callable(self::$timestampFunc))
		{
			return call_user_func(self::$timestampFunc);
		}
		return time();
	}

	/**
	 * 设置获取时间戳的方法
	 *
	 * @param function $func
	 */
	public static function timestamp_set_save_handler( $func )
	{
		self::$timestampFunc = $func;
	}

	protected static $getParamFunc = null;

	public static function getParam( $key )
	{
		if(null !== self::$getParamFunc && is_callable(self::$getParamFunc))
		{
			return call_user_func(self::$getParamFunc, $key);
		}
		return $_SESSION[ $key ];
	}

	/**
	 *
	 * 设置Session数据的存取方法
	 * 类似于session_set_save_handler来重写Session的存取方法
	 * 当你的token存储到跟用户相关的数据库中时非常有用
	 * $get方法 接受1个参数 $key
	 * $set方法 接受2个参数 $key $val
	 *
	 * @param function $get
	 * @param function $set
	 */
	public static function param_set_save_handler( $get, $set)
	{
		self::$getParamFunc = $get;
		self::$setParamFunc = $set;
	}

	protected static $setParamFunc = null;

	public static function setParam( $key , $val=null)
	{
		if(null !== self::$setParamFunc && is_callable(self::$setParamFunc))
		{
			return call_user_func(self::$setParamFunc, $key, $val);
		}
		if( null === $val)
		{
			unset($_SESSION[$key]);
			return ;
		}
		$_SESSION[ $key ] = $val;
	}

}
