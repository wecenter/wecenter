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

class sina extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			die;
		}
	}

	function binding_action()
	{
		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
		
		HTTP::redirect($oauth->getAuthorizeURL(get_js_url('/account/sina/binding_callback/')));
	}

	function binding_callback_action()
	{	
		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
		
		AWS_APP::session()->sina_token = $oauth->getAccessToken('code', array(
			'code' => $_GET['code'],
			'redirect_uri' => get_js_url('/account/sina/binding_callback/')
		));
		
		$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), AWS_APP::session()->sina_token['access_token']);
		
		$uid_get = $client->get_uid();
		$sina_profile = $client->show_user_by_id($uid_get['uid']);
		
		if ($sina_profile['error'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 错误代码: %s', $sina_profile['error']), "/account/setting/openid/");
		}
		
		if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
		{
			$this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
		}
		
		//$this->model('openid_weibo')->bind_account($sina_profile, get_js_url('/account/setting/openid/'), $this->user_id, $last_key['oauth_token'], $last_key['oauth_token_secret']);
		$this->model('openid_weibo')->bind_account($sina_profile, get_js_url('/account/setting/openid/'), $this->user_id);
	
	}

	function del_bind_action()
	{
		$this->model('openid_weibo')->del_users_by_uid($this->user_id);
		
		HTTP::redirect("/account/setting/openid/");
	}
}
	