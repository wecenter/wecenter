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

class USER
{
	public static function &instance()
	{
		static $u;
		
		if (empty($u))
		{
			$u = new session_user(); // 使用 SESSION 来进行验证,客户端记录用户信息
		}
		
		return $u;
	}
	
	// 获取一个会话变量
	public static function get($key = false)
	{
		$u = &USER::instance();
		
		return $u->get_info($key);
	}
	
	// 当前访问者的uid
	public static function get_client_uid()
	{
		return (int)USER::get('__CLIENT_UID');
	}
}


class session_user
{
	// 保存客户端用户的信息
	public static $server_user_info = array();

	public function __construct()
	{
		// Cookie 清除则 Session 也清除
		if (AWS_APP::session()->client_info && ! $_COOKIE[G_COOKIE_PREFIX . '_user_login'])
		{
			unset(AWS_APP::session()->client_info);
		}
		
		// 解掉 COOKIE, 然后进行验证
		if (! AWS_APP::session()->client_info && $_COOKIE[G_COOKIE_PREFIX . '_user_login'])
		{
			// 解码 Cookie
			$sso_user_login = H::decode_hash($_COOKIE[G_COOKIE_PREFIX . '_user_login']);
			
			if ($sso_user_login['user_name'] && $sso_user_login['password'] && $sso_user_login['uid'] && strstr($sso_user_login['UA'], $_SERVER['HTTP_USER_AGENT']))
			{			
				if (AWS_APP::model('account')->check_hash_login($sso_user_login['user_name'], $sso_user_login['password']))
				{
					AWS_APP::session()->client_info['__CLIENT_UID'] = $sso_user_login['uid'];
					AWS_APP::session()->client_info['__CLIENT_USER_NAME'] = $sso_user_login['user_name'];
					AWS_APP::session()->client_info['__CLIENT_PASSWORD'] = $sso_user_login['password'];
										
					return true;
				}
			}
			
			return false;
		}
	}
	
	public function get_info($key)
	{
		return AWS_APP::session()->client_info[$key];
	}
}
