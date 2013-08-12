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

class openid extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	function index_action()
	{
		HTTP::redirect('/account/login/');
	}

	function sina_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		unset(AWS_APP::session()->sina_profile);
		unset(AWS_APP::session()->sina_token);
		
		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
		
		HTTP::redirect($oauth->getAuthorizeURL(get_js_url('/account/openid/sina_callback/')));
	}

	function sina_callback_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		if ($this->is_post() and AWS_APP::session()->sina_profile and ! AWS_APP::session()->sina_token['error'])
		{
			if (get_setting('invite_reg_only') == 'Y')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
			}
			
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, AWS_APP::lang()->_t('请输入真实姓名')));
			}
			else if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, AWS_APP::lang()->_t('真实姓名已经存在')));
			}
			else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, $check_rs));
			}
			else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, AWS_APP::lang()->_t('真实姓名中包含敏感词或系统保留字')));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'userPassword'
				), -1, AWS_APP::lang()->_t('密码长度不符合规则')));
			}
			
			if (! $_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
				
				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			}
			
			if ($uid)
			{
				$this->model('openid_weibo')->bind_account(AWS_APP::session()->sina_profile, null, $uid, true);
				
				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('与微博通信出错 (Register), 请重新登录')));
			}
		}
		else
		{
			if ($_GET['code'] and (! AWS_APP::session()->sina_token or AWS_APP::session()->sina_token['error']))
			{
				$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
				
				AWS_APP::session()->sina_token = $oauth->getAccessToken('code', array(
					'code' => $_GET['code'], 
					'redirect_uri' => get_js_url('/account/openid/sina_callback/')
				));
				
				$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), AWS_APP::session()->sina_token['access_token']);
				
				$uid_get = $client->get_uid();
				$sina_profile = $client->show_user_by_id($uid_get['uid']);
				
				if ($sina_profile['error'])
				{
					H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 错误代码: %s', $sina_profile['error']), '/account/login/');
				}
				
				AWS_APP::session()->sina_profile = $sina_profile;
			}
			
			if (! AWS_APP::session()->sina_profile)
			{
				H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 请重新登录'), '/account/login/');
			}
			
			if ($sina_user = $this->model('openid_weibo')->get_users_sina_by_id(AWS_APP::session()->sina_profile['id']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($sina_user['uid']);
				
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
				$this->model('openid_weibo')->update_token($sina_user['id'], AWS_APP::session()->sina_token);
				
				unset(AWS_APP::session()->sina_profile);
				unset(AWS_APP::session()->sina_token);
				
				if (get_setting('ucenter_enabled') == 'Y')
				{
					HTTP::redirect('/account/sync_login/');
				}
				else
				{
					HTTP::redirect('/');
				}
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_weibo')->bind_account(AWS_APP::session()->sina_profile, '/', $this->user_id);
				}
				else
				{
					if (get_setting('invite_reg_only') == 'Y')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'), get_setting('base_url'));
					}
					else
					{
						$this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');
						
						TPL::assign('user_name', AWS_APP::session()->sina_profile['screen_name']);
						
						TPL::import_css('css/register.css');
						
						TPL::output("account/openid/callback");
					}
				}
			}
		}
	}

	function qq_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		$this->model('openid_qq_weibo')->init(get_js_url('/account/openid/qq_callback/'), get_setting('qq_app_key'), get_setting('qq_app_secret'));
	}

	function qq_callback_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
		
		if (! Services_Tencent_OpenSDK_Tencent_Weibo::getAccessToken($_GET['oauth_verifier']) or ! $uinfo = Services_Tencent_OpenSDK_Tencent_Weibo::call('user/info'))
		{
			H::redirect_msg(AWS_APP::lang()->_t('与微博通信出错, 请重新登录'), "/account/login/");
		}
		
		if ($this->is_post())
		{
			if (get_setting('invite_reg_only') == 'Y')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
			}
			
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, AWS_APP::lang()->_t('请输入真实姓名')));
			}
			else if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, AWS_APP::lang()->_t('真实姓名已经存在')));
			}
			else if ($user_name_check = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, $user_name_check));
			}
			else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), -1, AWS_APP::lang()->_t('真实姓名中包含敏感词或系统保留字')));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'userPassword'
				), -1, AWS_APP::lang()->_t('密码长度不符合规则')));
			}
			
			if (! $_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
				
				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			}
			
			if ($uid)
			{			
				$this->model('openid_qq_weibo')->bind_account($uinfo, null, $uid, true);
				
				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与微博通信出错 (Register), 请重新登录')));
			}
		}
		else
		{
			if ($qq_user = $this->model('openid_qq_weibo')->get_users_qq_by_name($uinfo['data']['name']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($qq_user['uid']);
				
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
				$oauth_access_token_name = Services_Tencent_OpenSDK_Tencent_Weibo::ACCESS_TOKEN;
				$oauth_access_token_secret_name = Services_Tencent_OpenSDK_Tencent_Weibo::OAUTH_TOKEN_SECRET;
				
				$this->model('openid_qq_weibo')->update_token($qq_user['name'], AWS_APP::session()->$oauth_access_token_name, AWS_APP::session()->$oauth_access_token_secret_name);
				
				if (get_setting('ucenter_enabled') == 'Y')
				{
					HTTP::redirect('/account/sync_login/');
				}
				else
				{
					HTTP::redirect('/');
				}
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_qq_weibo')->bind_account($uinfo, '/', $this->user_id);
				}
				else
				{
					if (get_setting('invite_reg_only') == 'Y')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'), get_setting('base_url'));
					}
					else
					{
						$this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');
						
						TPL::assign('user_name', $uinfo['data']['name']);
						
						TPL::import_css('css/register.css');
						
						TPL::output("account/openid/callback");
					}
				}
			}
		}
	}

	public function qq_login_action()
	{
		unset(AWS_APP::session()->QQConnect);
		
		HTTP::redirect($this->model('openid_qq')->qq_login(get_js_url('/account/openid/qq_login_callback/')));
	}

	public function qq_login_callback_action()
	{
		if (! $_GET['code'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
		}
		
		if (! AWS_APP::session()->QQConnect['access_token'])
		{
			if (! $this->model('openid_qq')->request_access_token(get_js_url('/account/openid/qq_login_callback/')))
			{
				H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
			}
		}
		
		if (! AWS_APP::session()->QQConnect['access_token'] OR ! $uinfo = $this->model('openid_qq')->request_user_info())
		{
			H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
		}
		
		if ($qq_user = $this->model('openid_qq')->get_user_info_by_open_id(load_class('Services_Tencent_QQConnect_V2')->get_openid()))
		{
			$user_info = $this->model('account')->get_user_info_by_uid($qq_user['uid']);
				
			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
			$this->model('openid_qq')->update_token($qq_user['name'], AWS_APP::session()->QQConnect['access_token']);
				
			if (get_setting('ucenter_enabled') == 'Y')
			{
				HTTP::redirect('/account/sync_login/');
			}
			else
			{
				HTTP::redirect('/');
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('该 QQ 号未与本站账户绑定'), get_setting('base_url'));
		}
	}
}