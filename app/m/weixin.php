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
		
		TPL::import_clean();
		
		TPL::import_css(array(
			'js/mobile/mobile.css',
		));
		
		TPL::import_js(array(
			'js/jquery.2.js',
			'js/jquery.form.js',
			'js/mobile/framework.js',
			'js/mobile/mobile.js',
			'js/mobile/aw-mobile-template.js'
		));
	}
	
	public function redirect_action()
	{
		if ($_GET['code'])
		{
			if ($access_token = json_decode(curl_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . AWS_APP::config()->get('weixin')->app_id . '&secret=' . AWS_APP::config()->get('weixin')->app_secret . '&code=' . $_GET['code'] . '&grant_type=authorization_code'), true))
			{
				if ($access_token['errcode'])
				{
					H::redirect_msg('Error: ' . $access_token['errcode'] . ' ' . $access_token['errmsg']);
				}
				
				if ($weixin_user = $this->model('openid_weixin')->get_user_info_by_openid($access_token['openid']))
				{
					$user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);
					
					HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
					
					HTTP::redirect(base64_decode($_GET['redirect']));
				}
				else
				{
					$redirect_uri = str_replace(get_setting('base_url'), '/', base64_decode($_GET['redirect']));
										
					if ($this->user_info['permission']['visit_site'] AND substr($redirect_uri, 0, 1) == '/')
					{
						$uri_analyze = explode('/', substr($redirect_uri, 1));
						
						if (($uri_analyze[0] == 'question' AND $this->user_info['permission']['visit_question']) OR ($uri_analyze[0] == 'topic' AND $this->user_info['permission']['visit_topic']) OR ($uri_analyze[0] == 'feature' AND $this->user_info['permission']['visit_feature']) OR ($uri_analyze[0] == 'people' AND $this->user_info['permission']['visit_people']) OR ($uri_analyze[0] == 'home' AND $this->user_info['permission']['visit_explore']))
						{
							HTTP::redirect(base64_decode($_GET['redirect']));
						}
					}
					
					HTTP::redirect($this->model('openid_weixin')->get_oauth_url('/m/weixin/authorization/?redirect=' . urlencode($_GET['redirect'])));
				}
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
		}
	}
	
	public function authorization_action()
	{
		$this->model('account')->setcookie_logout();	// 清除 COOKIE
		$this->model('account')->setsession_logout();	// 清除 Session
		
		if ($_GET['code'])
		{
			if ($access_token = json_decode(curl_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . AWS_APP::config()->get('weixin')->app_id . '&secret=' . AWS_APP::config()->get('weixin')->app_secret . '&code=' . $_GET['code'] . '&grant_type=authorization_code'), true))
			{
				if ($access_token['errcode'])
				{
					H::redirect_msg('Access error: ' . $access_token['errcode'] . ' ' . $access_token['errmsg']);
				}
				
				$access_user = json_decode(curl_get_contents('https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token['access_token'] . '&openid=' . $access_token['openid']), true);
				
				if ($access_user['errcode'])
				{
					H::redirect_msg('Get user info error: ' . $access_user['errcode'] . ' ' . $access_user['errmsg']);
				}
				
				AWS_APP::session()->WXConnect = array(
					'access_token' => $access_token,
					'access_user' => $access_user
				);
				
				TPL::assign('access_token', $access_token);
				TPL::assign('access_user', $access_user);
				
				TPL::assign('body_class', 'explore-body');
				
				TPL::output('m/weixin/authorization');
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
		}
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
			
			if ($_GET['redirect'])
			{
				HTTP::redirect(base64_decode($_GET['redirect']));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('授权失败, 请返回重新操作'));
		}
	}
	
	public function oauth_redirect_action()
	{
		if (!$_GET['uri'])
		{
			$redirect_uri = $_SERVER['HTTP_REFERER'];
		}
		else
		{
			$redirect_uri = urlencode(get_js_url($_GET['uri']));
		}
		
		header('Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . AWS_APP::config()->get('weixin')->app_id . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=' . urlencode($_GET['scope']) . '&state=' . urlencode($_GET['state']) . '#wechat_redirect');
		die;
	}
}