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

class qq extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	function binding_weibo_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('腾讯微博绑定功能已关闭'), '/');
		}
		
		$this->model('openid_qq_weibo')->init(get_js_url('/account/qq/callback_weibo/'));
	}

	function callback_weibo_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('腾讯微博绑定功能已关闭'), '/');
		}
		
		Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
		
		if (Services_Tencent_OpenSDK_Tencent_Weibo::getAccessToken($_GET['oauth_verifier']) and $uinfo = Services_Tencent_OpenSDK_Tencent_Weibo::call('user/info'))
		{
			if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
			{
				$this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
			}

			$this->model('openid_qq_weibo')->bind_account($uinfo, get_js_url('/account/setting/openid/'), $this->user_id);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 请重新登录'), '/account/setting/openid/');
		}
	}

	function del_bind_weibo_action()
	{
		$this->model('openid_qq_weibo')->del_users_by_uid($this->user_id);
		
		HTTP::redirect('/account/setting/openid/');
	}

	function binding_qq_action()
	{
		if (get_setting('qq_login_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('QQ 帐号绑定功能已关闭'), '/');
		}
		
		unset(AWS_APP::session()->QQConnect);
		
		HTTP::redirect($this->model('openid_qq')->qq_login(get_js_url('/account/qq/callback_qq/')));
	}

	function callback_qq_action()
	{
		if (get_setting('qq_login_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('QQ 帐号绑定功能已关闭'), '/');
		}
		
		if (! $_GET['code'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), '/account/login/');
		}
		
		if (! AWS_APP::session()->QQConnect['access_token'])
		{
			if (! $this->model('openid_qq')->request_access_token(get_js_url('/account/qq/callback_qq/')))
			{
				H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), '/account/login/');
			}
		}
				
		if (! AWS_APP::session()->QQConnect['access_token'] OR ! $uinfo = $this->model('openid_qq')->request_user_info())
		{
			H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), '/account/login/');
		}
		else
		{
			if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
			{
				$this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), AWS_APP::lang()->_t('绑定 OPEN ID'));
			}
			
			$this->model('openid_qq')->bind_account($uinfo, get_js_url('/account/setting/openid/'), $this->user_id);
		}
	}
	
	function del_bind_qq_action()
	{
		$this->model('openid_qq')->del_user_by_uid($this->user_id);
		
		HTTP::redirect('/account/setting/openid/');
	}
}
	