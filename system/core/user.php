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


class core_user
{
	public function __construct()
	{
		/*if (AWS_APP::session()->client_info AND ! $_COOKIE[G_COOKIE_PREFIX . '_user_login'])
		{
			// Cookie 清除则 Session 也清除
			unset(AWS_APP::session()->client_info);
		}*/

		if (! AWS_APP::session()->client_info AND $_COOKIE[G_COOKIE_PREFIX . '_user_login'])
		{
			$auth_hash_key = md5(G_COOKIE_HASH_KEY . $_SERVER['HTTP_USER_AGENT']);

			// 解码 Cookie
			$sso_user_login = json_decode(AWS_APP::crypt()->decode($_COOKIE[G_COOKIE_PREFIX . '_user_login'], $auth_hash_key), true);

			if ($sso_user_login['user_name'] AND $sso_user_login['password'] AND $sso_user_login['uid'])
			{
				if ($user_info = AWS_APP::model('account')->check_hash_login($sso_user_login['user_name'], $sso_user_login['password']))
				{
					AWS_APP::session()->client_info['__CLIENT_UID'] = $user_info['uid'];
					AWS_APP::session()->client_info['__CLIENT_USER_NAME'] = $user_info['user_name'];
					AWS_APP::session()->client_info['__CLIENT_PASSWORD'] = $sso_user_login['password'];

					return true;
				}
			}

			HTTP::set_cookie('_user_login', '', null, '/', null, false, true);

			return false;
		}
	}

	public function get_info($key)
	{
		return AWS_APP::session()->client_info['__CLIENT_' . strtoupper($key)];
	}
}
