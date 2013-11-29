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
	public function get_user_info_by_openid($open_id)
	{
		return $this->fetch_row('users_weixin', "openid = '" . $this->quote($open_id) . "'");
	}
	
	public function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function bind_account($access_user, $access_token, $uid, $is_ajax = false)
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
	
	public function add_user($uid, $access_user, $access_token)
	{
		return $this->insert('users_weixin', array(
			'uid' => intval($uid),
			'openid' => $access_token['openid'],
			'expires_in' => (time() + $access_token['expires_in']),
			'access_token' => $access_token['access_token'],
			'refresh_token' => $access_token['refresh_token'],
			'scope' => $access_token['scope'],
			'headimgurl' => $access_user['headimgurl'],
			'nickname' => $access_user['nickname'],
			'sex' => $access_user['sex'],
			'province' => $access_user['province'],
			'city' => $access_user['city'],
			'country' => $access_user['country'],
			'add_time' => time()
		));
	}
	
	public function update_token($openid, $access_token)
	{
		$this->update('users_weixin', array(
			'access_token' => $access_token
		), "openid = '" . $this->quote($openid) . "'");
	}
	
	public function del_user_by_uid($uid)
	{
		return $this->delete('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function get_oauth_url($redirect_uri, $state = '', $scope = 'snsapi_base')
	{
		return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . AWS_APP::config()->get('weixin')->app_id . '&redirect_uri=' . urlencode($redirect_uri) . '&response_type=code&scope=' . $scope . '&state=' . $state;
	}
	
	public function redirect($redirect_uri)
	{
		HTTP::redirect($this->get_oauth_url(get_js_url('/m/weixin/redirect/redirect-' . urlencode($redirect_uri))));
	}
}
	