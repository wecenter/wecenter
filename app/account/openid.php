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

	public function index_action()
	{
		HTTP::redirect('/account/login/');
	}

	public function sina_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}

		unset(AWS_APP::session()->sina_profile);
		unset(AWS_APP::session()->sina_token);

		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));

		$url = '/account/openid/sina_callback/';

		if ($_GET['return_url'])
		{
			$url .= 'return_url-' . $_GET['return_url'];
		}

		HTTP::redirect($oauth->getAuthorizeURL(get_js_url($url)));
	}

	public function sina_callback_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}

		if ($this->is_post() and AWS_APP::session()->sina_profile and ! AWS_APP::session()->sina_token['error'])
		{
			define('IN_AJAX', TRUE);

			if (get_setting('register_type') == 'close')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
			}
			else if (get_setting('register_type') == 'invite')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
			}
			else if (get_setting('register_type') == 'weixin')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过微信注册')));
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

			if (!$_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
			}

			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email']);

				if (!is_array($result))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('UCenter 同步失败，错误为：%s', $result)));
				}

				$uid = $result['user_info']['uid'];

				$redirect_url = '/account/sync_login/';
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

				if (get_setting('register_valid_type') == 'email')
				{
					$this->model('active')->new_valid_email($uid);
				}

				if (get_setting('register_valid_type') != 'approval')
				{
					$this->model('active')->active_user_by_uid($uid);
				}

				$redirect_url = '/';
			}

			if (!$uid)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与微博通信出错 (Register), 请重新登录')));
			}

			$this->model('openid_weibo')->bind_account(AWS_APP::session()->sina_profile, null, $uid, AWS_APP::session()->sina_token, true);

			if (AWS_APP::session()->sina_profile['profile_image_url'])
			{
				$this->model('account')->associate_remote_avatar($uid, str_replace('/50/', '/180/', AWS_APP::session()->sina_profile['profile_image_url']));
			}

			$user_info = $this->model('account')->get_user_info_by_uid($uid);

			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

			unset(AWS_APP::session()->sina_profile);
			unset(AWS_APP::session()->sina_token);

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url($redirect_url)
			), 1, null));
		}
		else
		{
			if ($_GET['code'] and (! AWS_APP::session()->sina_token or AWS_APP::session()->sina_token['error']))
			{
				$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));

				$callback_url = '/account/openid/sina_callback/';

				if ($_GET['return_url'])
				{
					$callback_url .= 'return_url-' . $_GET['return_url'];
				}

				AWS_APP::session()->sina_token = $oauth->getAccessToken('code', array(
					'code' => $_GET['code'],
					'redirect_uri' => get_js_url($callback_url)
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

				$this->model('openid_weibo')->refresh_access_token($sina_user['id'], AWS_APP::session()->sina_token);

				unset(AWS_APP::session()->sina_profile);
				unset(AWS_APP::session()->sina_token);

				if (get_setting('ucenter_enabled') == 'Y')
				{
					$redirect_url = '/account/sync_login/';

					if ($_GET['return_url'])
					{
						$redirect_url .= 'url-' . $_GET['return_url'];
					}
				}
				else if ($_GET['return_url'])
				{
					$redirect_url = base64_decode($_GET['return_url']);
				}
				else
				{
					$redirect_url = '/';
				}

				HTTP::redirect($redirect_url);
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_weibo')->bind_account(AWS_APP::session()->sina_profile, '/', $this->user_id, AWS_APP::session()->sina_token);
				}
				else
				{
					if (get_setting('register_type') == 'close')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'));
					}
					else if (get_setting('register_type') == 'invite')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'));
					}
					else if (get_setting('register_type') == 'weixin')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站只能通过微信注册'));
					}
					else
					{
						$this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

						TPL::assign('user_name', AWS_APP::session()->sina_profile['screen_name']);

						TPL::import_css('css/register.css');

						TPL::output('account/openid/callback');
					}
				}
			}
		}
	}

	public function qq_login_action()
	{
		unset(AWS_APP::session()->QQConnect);

		$url = '/account/openid/qq_login_callback/';

		if ($_GET['return_url'])
		{
			$url .= 'return_url-' . $_GET['return_url'];
		}

		HTTP::redirect($this->model('openid_qq')->qq_login(get_js_url($url)));
	}

	public function qq_login_callback_action()
	{
		if ($this->is_post() and AWS_APP::session()->qq_profile and AWS_APP::session()->QQConnect)
		{
			if (get_setting('register_type') == 'close')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
			}
			else if (get_setting('register_type') == 'invite')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
			}
			else if (get_setting('register_type') == 'weixin')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过微信注册')));
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
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email']);

				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

				if (get_setting('register_valid_type') == 'email')
				{
					$this->model('active')->new_valid_email($uid);
				}

				if (get_setting('register_valid_type') != 'approval')
				{
					$this->model('active')->active_user_by_uid($uid);
				}
			}

			if ($uid)
			{
				$this->model('openid_qq')->bind_account(AWS_APP::session()->qq_profile, null, $uid, true);

				if (AWS_APP::session()->qq_profile['figureurl_2'])
				{
					$this->model('account')->associate_remote_avatar($uid, AWS_APP::session()->qq_profile['figureurl_2']);
				}

				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与 QQ 通信出错 (Register), 请重新登录')));
			}
		}
		else
		{
			if (! $_GET['code'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
			}

			if (! AWS_APP::session()->QQConnect['access_token'])
			{
				$callback_url = '/account/openid/qq_login_callback/';

				if ($_GET['return_url'])
				{
					$callback_url .= 'return_url-' . $_GET['return_url'];
				}

				if (! $this->model('openid_qq')->request_access_token(get_js_url($callback_url)))
				{
					H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
				}
			}

			if (! AWS_APP::session()->QQConnect['access_token'] OR ! $uinfo = $this->model('openid_qq')->request_user_info())
			{
				H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), "/account/login/");
			}

			AWS_APP::session()->qq_profile = $uinfo;

			if ($qq_user = $this->model('openid_qq')->get_user_info_by_open_id(load_class('Services_Tencent_QQConnect_V2')->get_openid()))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($qq_user['uid']);

				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

				$this->model('openid_qq')->update_token($qq_user['name'], AWS_APP::session()->QQConnect['access_token']);

				if (get_setting('ucenter_enabled') == 'Y')
				{
					$redirect_url = '/account/sync_login/';

					if ($_GET['return_url'])
					{
						$redirect_url .= 'url-' . $_GET['return_url'];
					}
				}
				else if ($_GET['return_url'])
				{
					$redirect_url = base64_decode($_GET['return_url']);
				}
				else
				{
					$redirect_url = '/';
				}

				HTTP::redirect($redirect_url);
			}
			else
			{
			    if ($this->user_id)
				{
					$this->model('openid_qq')->bind_account($this->model('openid_qq')->request_user_info(), '/', $this->user_id);
				}
				else
				{
					if (get_setting('register_type') == 'close')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'));
					}
					else if (get_setting('register_type') == 'invite')
					{
						H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'));
					}
					else
					{
						$this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

						TPL::assign('user_name', str_replace(' ', '_', AWS_APP::session()->qq_profile['nickname']));

						TPL::import_css('css/register.css');

						TPL::output('account/openid/callback');
					}
				}
			}
		}
	}
}