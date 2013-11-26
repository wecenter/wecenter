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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

define('IN_MOBILE', true);

class weixin extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array(
			'binding'	
		);
		
		return $rule_action;
	}
	
	public function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function authorization_action()
	{
		if ($_GET['code'])
		{
			if ($access_token = json_decode(curl_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . AWS_APP::config()->get('weixin')->app_id . '&secret=' . AWS_APP::config()->get('weixin')->app_secret . '&code=' . $_GET['code'] . '&grant_type=authorization_code'), true))
			{
				if ($access_token['errcode'])
				{
					H::redirect_msg('Error: ' . $access_token['errcode'] . ' ' . $access_token['errmsg']);
				}
				
				$access_user = json_decode(curl_get_contents('https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token['access_token'] . '&openid=' . $access_token['openid']));
				
				AWS_APP::session()->WXConnect = array(
					'access_token' => $access_token,
					'access_user' => $access_user
				);
				
				TPL::assign('access_token', $access_token);
				TPL::assign('access_user', $access_user);
				
				TPL::output('m/weixin/authorization');
				die;
			}
		}
		
		H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
	}
	
	public function binding_action()
	{
		if ($weixin_user = $this->model('openid_weixin')->get_user_info_by_openid(AWS_APP::session()->WXConnect['access_token']['openid']))
		{
			$user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);
			
			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
			
			HTTP::redirect('/');
		}
		else if (AWS_APP::session()->WXConnect['access_token']['openid'])
		{
			$this->model('openid_weixin')->bind_account(AWS_APP::session()->WXConnect['access_user'], AWS_APP::session()->WXConnect['access_token'], $this->user_id);
			
			H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'));
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
		}
	}
}