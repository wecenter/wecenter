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
			'mobile/css/icon.css',
			'mobile/css/mobile.css'
		));

		TPL::import_js(array(
			'js/jquery.2.js',
			'js/jquery.form.js',
			'mobile/js/framework.js',
			'mobile/js/aws-mobile.js',
			'mobile/js/app.js',
			'mobile/js/aw-mobile-template.js'
		));
	}

	public function redirect_action()
	{
		if (!in_weixin() OR get_setting('weixin_account_role') != 'service')
		{
			HTTP::redirect(base64_decode($_GET['redirect']));
		}

		if ($_GET['code'] AND get_setting('weixin_app_id') AND get_setting('weixin_app_secret'))
		{
			if ($access_token = $this->model('openid_weixin_weixin')->get_sns_access_token_by_authorization_code($_GET['code']))
			{
				if ($access_token['errcode'])
				{
					H::redirect_msg('授权失败: Redirect ' . $access_token['errcode'] . ' ' . $access_token['errmsg'] . ', Code: ' . htmlspecialchars($_GET['code']));
				}

				if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_openid($access_token['openid']))
				{
					$user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

					HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

					HTTP::redirect(base64_decode($_GET['redirect']));
				}
				else
				{
					if ($_GET['state'] == 'OAUTH')
					{
						HTTP::redirect('/m/weixin/authorization/?state=OAUTH&access_token=' . urlencode(base64_encode(serialize($access_token))) . '&redirect=' . urlencode($_GET['redirect']));
					}
					else
					{
						HTTP::redirect($this->model('openid_weixin_weixin')->get_oauth_url('/m/weixin/authorization/?redirect=' . urlencode($_GET['redirect'])));
					}
				}
			}
			else
			{
				H::redirect_msg('远程服务器忙,请稍后再试, State: ' . htmlspecialchars($_GET['state']) . ', Code: ' . htmlspecialchars($_GET['code']));
			}
		}
		else
		{
			H::redirect_msg('授权失败, 请返回重新操作, URI: ' . $_SERVER['REQUEST_URI']);
		}
	}

	public function authorization_action()
	{
		$this->model('account')->setcookie_logout();	// 清除 COOKIE
		$this->model('account')->setsession_logout();	// 清除 Session

		unset(AWS_APP::session()->WXConnect);

		if (get_setting('weixin_account_role') != 'service')
		{
			H::redirect_msg(AWS_APP::lang()->_t('此功能只适用于通过微信认证的服务号'));
		}
		else if ($_GET['code'] OR $_GET['state'] == 'OAUTH')
		{
			if ($_GET['state'] == 'OAUTH')
			{
				$access_token = unserialize(base64_decode($_GET['access_token']));
			}
			else
			{
				$access_token = $this->model('openid_weixin_weixin')->get_sns_access_token_by_authorization_code($_GET['code']);
			}

			if ($access_token)
			{
				if ($access_token['errcode'])
				{
					H::redirect_msg('授权失败: Authorization ' . $access_token['errcode'] . ' ' . $access_token['errmsg'] . ', Code: ' . htmlspecialchars($_GET['code']));
				}

				if ($_GET['state'] == 'OAUTH' OR $_GET['state'] == 'OAUTH_REDIRECT')
				{
					$access_user = $this->model('openid_weixin_weixin')->get_user_info_by_oauth_openid($access_token['access_token'], $access_token['openid']);
				}
				else
				{
					$access_user = $this->model('openid_weixin_weixin')->get_user_info_by_openid_from_weixin($access_token['openid']);
				}

				if (!$access_user)
				{
					H::redirect_msg('远程服务器忙,请稍后再试, Code: get_user_info, OpenId: ' . $access_token['openid']);
				}

				if ($access_user['errcode'])
				{
					if ($access_user['errcode'] == 48001)
					{
						$this->model('weixin')->send_text_message($access_token['openid'], '当前微信没有绑定社区帐号, 请<a href="' . $this->model('openid_weixin_weixin')->get_oauth_url(get_js_url('/m/weixin/authorization/'), 'snsapi_userinfo') . '">点此绑定</a>或<a href="' . get_js_url('/m/register/') . '">注册新账户</a>, 使用全部功能');

						H::redirect_msg(AWS_APP::lang()->_t('当前微信没有绑定社区帐号, 请返回进行绑定后访问本内容'));
					}
					else
					{
						H::redirect_msg('获取用户信息失败: ' . $access_user['errcode'] . ' ' . $access_user['errmsg']);
					}
				}

				if (!$access_user['nickname'])
				{
					if ($access_user['subscribe'] == 0)
					{
						H::redirect_msg(AWS_APP::lang()->_t('您当前没有关注本公众号主账号, 无法使用身份认证功能'));
					}

					H::redirect_msg(AWS_APP::lang()->_t('您当前没有关注本公众号, 无法使用全部功能'));
				}

				if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_openid($access_token['openid']))
				{
					$user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

					HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

					if ($_GET['redirect'])
					{
						HTTP::redirect(base64_decode($_GET['redirect']));
					}
					else
					{
						H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'), '/m/');
					}
				}

				if (get_setting('register_type') == 'weixin')
				{
					if ($user_info = $this->model('openid_weixin_weixin')->weixin_auto_register($access_token, $access_user))
					{
						if ($_GET['redirect'])
						{
							HTTP::redirect(base64_decode($_GET['redirect']));
						}
						else
						{
							H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'), '/m/');
						}
					}
					else
					{
						H::redirect_msg(AWS_APP::lang()->_t('注册失败,请返回重新操作'));
					}
				}
				else
				{
					AWS_APP::session()->WXConnect = array(
						'access_token' => $access_token,
						'access_user' => $access_user
					);

					TPL::assign('access_token', $access_token);
					TPL::assign('access_user', $access_user);
															if (get_setting('register_type') != 'close' AND get_setting('register_type') != 'invite')					{						TPL::assign('register_url', $this->model('openid_weixin_weixin')->get_oauth_url(get_js_url('/m/weixin/register/redirect-' . urlencode($_GET['redirect'])), 'snsapi_userinfo'));					}					
					TPL::assign('body_class', 'explore-body');

					TPL::output('m/weixin/authorization');
				}
			}
			else
			{
				H::redirect_msg('远程服务器忙,请稍后再试, State: ' . htmlspecialchars($_GET['state']) . ', Code: ' . htmlspecialchars($_GET['code']));
			}
		}
		else
		{
			H::redirect_msg('授权失败, 请返回重新操作, URI: ' . $_SERVER['REQUEST_URI']);
		}
	}

	public function binding_action()
	{
		if (AWS_APP::session()->WXConnect['access_token']['openid'])
		{
			$this->model('openid_weixin_weixin')->bind_account(AWS_APP::session()->WXConnect['access_user'], AWS_APP::session()->WXConnect['access_token'], $this->user_id);

			if ($_GET['redirect'])
			{
				HTTP::redirect(base64_decode($_GET['redirect']));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'), '/m/');
			}
		}
		else
		{
			H::redirect_msg('授权失败, 请返回重新操作, URI: ' . $_SERVER['REQUEST_URI']);
		}
	}

	public function oauth_redirect_action()
	{
		if (strstr($_GET['uri'], '%'))
		{
			$_GET['uri'] = urldecode($_GET['uri']);
		}

		if (!$_GET['uri'])
		{
			$redirect_uri = $_SERVER['HTTP_REFERER'];
		}
		else
		{
			$redirect_uri = get_js_url($_GET['uri']);
		}

		if (!in_weixin() OR get_setting('weixin_account_role') != 'service')
		{
			HTTP::redirect($redirect_uri);
		}

		$redirect_info = parse_url($redirect_uri);

		$this->model('account')->setcookie_logout();	// 清除 COOKIE
		$this->model('account')->setsession_logout();	// 清除 Session

		HTTP::redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . get_setting('weixin_app_id') . '&redirect_uri=' . urlencode($redirect_uri) . '&response_type=code&scope=' . urlencode($_GET['scope']) . '&state=' . urlencode($_GET['state']) . '#wechat_redirect');
	}

	public function register_action()
	{		if (get_setting('register_type') == 'close')		{			H::redirect_msg('本站目前关闭注册');		}		else if (get_setting('register_type') == 'invite')		{			H::redirect_msg('本站只能通过邀请注册');		}		
		if ($_GET['code'] AND get_setting('weixin_app_id'))
		{
			if (!$access_token = $this->model('openid_weixin_weixin')->get_sns_access_token_by_authorization_code($_GET['code']))
			{
				H::redirect_msg('远程服务器忙,请稍后再试, Code: ' . htmlspecialchars($_GET['code']));
			}

			if ($access_token['errcode'])
			{
				H::redirect_msg('授权失败: Register ' . $access_token['errcode'] . ' ' . $access_token['errmsg'] . ', Code: ' . htmlspecialchars($_GET['code']));
			}

			if (!$access_user = $this->model('openid_weixin_weixin')->get_user_info_by_oauth_openid($access_token['access_token'], $access_token['openid']))
			{
				H::redirect_msg('远程服务器忙,请稍后再试, Code: get_user_info');
			}

			if ($access_user['errcode'])
			{
				H::redirect_msg('获取用户信息失败: ' . $access_user['errcode'] . ' ' . $access_user['errmsg']);
			}

			if (!$access_user['nickname'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('获取用户信息失败'));
			}

			if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_openid($access_token['openid']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

				if ($_GET['redirect'])
				{
					HTTP::redirect(base64_decode($_GET['redirect']));
				}
			}

			if ($user_info = $this->model('openid_weixin_weixin')->weixin_auto_register($access_token, $access_user))
			{
				if ($_GET['redirect'])
				{
					HTTP::redirect(base64_decode($_GET['redirect']));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('绑定微信成功'), '/m/');
				}
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('注册失败,请返回重新操作'));
			}
		}
		else
		{
			H::redirect_msg('授权失败, 请返回重新操作, URI: ' . $_SERVER['REQUEST_URI']);
		}
	}

	public function qr_login_action()
	{
		if (!$this->user_id AND $_GET['code'])
		{
			HTTP::redirect(get_js_url('/m/weixin/authorization/?redirect=' . urlencode(base64_encode(get_js_url('/m/weixin/qr_login/?token=' . $_GET['token']))) . '&code=' . $_GET['code'] . '&state=' . $_GET['state']));
		}

		if ($this->model('openid_weixin_weixin')->process_client_login($_GET['token'], $this->user_id))
		{
			H::redirect_msg('你已成功登录网站', '/m/');
		}
		else
		{
			H::redirect_msg('二维码已过期');
		}
	}
}