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
		if (AWS_APP::plugins()->installed('aws_weixin_enterprise'))
		{
			return $this->model('aws_weixin_enterprise')->bind_account($access_user, $access_token, $uid, $is_ajax);
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
					H::redirect_msg(AWS_APP::lang()->_t('微信账号已经被其他账号绑定'));
				}
			}
		}
		
		return $this->insert('users_weixin', array(
			'uid' => intval($uid),
			'openid' => $access_token['openid'],
			'add_time' => time()
		));
	}
	
	public function weixin_unbind($uid)
	{
		return $this->delete('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function get_oauth_url($redirect_uri, $scope = 'snsapi_base', $state = 'STATE')
	{		
		return get_js_url('/m/weixin/oauth_redirect/?uri=' . urlencode($redirect_uri) . '&scope=' . urlencode($scope) . '&state=' . urlencode($state));
	}
	
	public function redirect_url($redirect_uri)
	{
		if (!AWS_APP::plugins()->installed('aws_weixin_enterprise'))
		{
			return get_js_url($redirect_uri);
		}
		
		return $this->get_oauth_url(get_js_url('/m/weixin/redirect/?redirect=' . base64_encode(get_js_url($redirect_uri))));
	}
}
	