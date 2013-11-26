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


if (!defined('IN_ANWSION'))
{
	die;
}

class openid_weixin_class extends AWS_MODEL
{	
	function get_user_info_by_openid($open_id)
	{
		return $this->fetch_row('users_weixin', "openid = '" . $this->quote($open_id) . "'");
	}
	
	function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_weixin', 'uid = ' . intval($uid));
	}
	
	function bind_account($access_user, $access_token, $uid, $is_ajax = false)
	{
		if (! $access_user['nickname'])
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与微信通信出错, 请重新登录')));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('与微信通信出错, 请重新登录'), '/account/logout/');
			}
		}
		
		if ($openid_info = $this->get_user_info_by_uid($uid))
		{
			if ($openid_info['opendid'] != $access_user['openid'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信账号已经被其他账号绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('微信账号已经被其他账号绑定'), '/account/logout/');
				}
			}
		}
		
		return $this->add_user($uid, $access_user, $access_token);
	}
	
	function add_user($uid, $access_user, $access_token)
	{
		return $this->insert('users_weixin', array(
			'openid' => $access_token['openid'],
			'expires_in' => (time() + $access_token['expires_in']),
			'access_token' => $access_token['access_token'],
			'refresh_token' => $access_token['refresh_token'],
			'scope' => $access_token['scope'],
			'headimgurl' => intval($uid),
			'nickname' => $access_user['nickname'],
			'sex' => $access_user['sex'],
			'province' => $access_user['province'],
			'city' => $access_user['city'],
			'country' => $access_user['country'],
			'add_time' => time()
		));
	}
	
	function update_token($openid, $access_token)
	{
		$this->update('users_weixin', array(
			'access_token' => $access_token
		), "openid = '" . $this->quote($openid) . "'");
	}
	
	function del_user_by_uid($uid)
	{
		return $this->delete('users_weixin', 'uid = ' . intval($uid));
	}
}
	