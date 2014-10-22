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
		HTTP::no_cache_header();

		if (get_setting('sina_weibo_enabled') != 'Y' OR !get_setting('sina_akey') OR !get_setting('sina_skey'))
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站未开通微博登录'), '/');
		}
	}

	function binding_action()
	{
		$url = ($_GET['uid'] AND $this->user_info['group_id'] == 1) ? 'uid-' . intval($_GET['uid']) : '';

		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));

		HTTP::redirect($oauth->getAuthorizeURL(get_js_url('/account/sina/binding_callback/' . $url)));
	}

	function binding_callback_action()
	{
		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));

		if ($_GET['uid'] AND $this->user_info['permission']['is_administortar'])
		{
			$user_id = intval($_GET['uid']);

			$user_info = $this->model('account')->get_user_info_by_uid($user_id);

			if (!$user_info)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本地用户不存在，无法绑定')));
			}

			$sina_token = $oauth->getAccessToken('code', array(
								'code' => $_GET['code'],
								'redirect_uri' => get_js_url('/account/sina/binding_callback/uid-' . $user_id)
							));
		}
		else
		{
			$user_id = $this->user_id;

			AWS_APP::session()->sina_token = $oauth->getAccessToken('code', array(
				'code' => $_GET['code'],
				'redirect_uri' => get_js_url('/account/sina/binding_callback/')
			));

			$sina_token = AWS_APP::session()->sina_token;

			$redirect = get_js_url('/account/setting/openid/');
		}

		$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $sina_token['access_token']);

		$uid_get = $client->get_uid();

		$sina_profile = $client->show_user_by_id($uid_get['uid']);

		if ($sina_profile['error'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 错误代码: %s', $sina_profile['error']), '/account/setting/openid/');
		}

		if (!$this->model('integral')->fetch_log($user_id, 'BIND_OPENID'))
		{
			$this->model('integral')->process($user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
		}

		//$this->model('openid_weibo')->bind_account($sina_profile, get_js_url('/account/setting/openid/'), $user_id, $last_key['oauth_token'], $last_key['oauth_token_secret'], $sina_token);
		$this->model('openid_weibo')->bind_account($sina_profile, $redirect, $user_id, $sina_token);
	}

	function del_bind_action()
	{
		$this->model('openid_weibo')->del_users_by_uid($this->user_id);

		HTTP::redirect('/account/setting/openid/');
	}
}
