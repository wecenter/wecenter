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

class ajax extends AWS_ADMIN_CONTROLLER
{	
	public function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function login_process_action()
	{
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
		}
		
		if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请填写正确的验证码'));
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (! $user_info = $this->model('ucenter')->login($this->user_info['email'], $_POST['password']))
			{
				$user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
		}
		
		if ($user_info['uid'])
		{
			$this->model('admin')->set_admin_login($user_info['uid']);
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('/admin/')
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('帐号或密码错误')));
		}
	}
	
	public function save_settings_action()
	{
		if ($_POST['upload_dir'] AND preg_match('/(.*)\/$/i', $_POST['upload_dir']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传文件存放绝对路径不能以 / 结尾')));
		}

		if ($_POST['upload_url'] AND preg_match('/(.*)\/$/i', $_POST['upload_url']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传目录外部访问 URL 地址不能以 / 结尾')));
		}

		if ($_POST['request_route_custom'])
		{
			$_POST['request_route_custom'] = trim($_POST['request_route_custom']);

			if ($request_routes = explode("\n", $_POST['request_route_custom']))
			{
				foreach ($request_routes as $key => $val)
				{
					if (! strstr($val, '==='))
					{
						continue;
					}

					list($m, $n) = explode('===', $val);

					if (substr($n, 0, 1) != '/' OR substr($m, 0, 1) != '/')
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
					}

					if (strstr($m, '/admin') OR strstr($n, '/admin'))
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则不允许设置 /admin 路由')));
					}
				}
			}
		}

		if ($_POST['censoruser'])
		{
			$_POST['censoruser'] = trim($_POST['censoruser']);
		}

		if ($_POST['report_reason'])
		{
			$_POST['report_reason'] = trim($_POST['report_reason']);
		}

		if ($_POST['sensitive_words'])
		{
			$_POST['sensitive_words'] = trim($_POST['sensitive_words']);
		}

		$curl_require_setting = array('qq_login_enabled', 'sina_weibo_enabled', 'qq_t_enabled');

		if (array_intersect(array_keys($_POST), $curl_require_setting))
		{
			foreach ($curl_require_setting AS $key)
			{
				if ($_POST[$key] == 'Y' AND !function_exists('curl_init'))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微博登录、QQ 登录等功能须服务器支持 CURL')));
				}
			}
		}

		if ($_POST['set_notification_settings'])
		{
			if ($notify_actions = $this->model('notify')->notify_action_details)
			{
				$notification_setting = array();

				foreach ($notify_actions as $key => $val)
				{
					if (! isset($_POST['new_user_notification_setting'][$key]) && $val['user_setting'])
					{
						$notification_setting[] = intval($key);
					}
				}
			}

			$_POST['new_user_notification_setting'] = $notification_setting;
		}

		if ($_POST['set_email_settings'])
		{
			$email_settings = array(
				'FOLLOW_ME' => 'N',
				'QUESTION_INVITE' => 'N',
				'NEW_ANSWER' => 'N',
				'NEW_MESSAGE' => 'N',
				'QUESTION_MOD' => 'N',
			);

			if ($_POST['new_user_email_setting'])
			{
				foreach ($_POST['new_user_email_setting'] AS $key => $val)
				{
					unset($email_settings[$val]);
				}
			}

			$_POST['new_user_email_setting'] = $email_settings;
		}

		$this->model('setting')->set_vars($_POST);

		if ($_POST['wecenter_access_token'])
		{
			$this->model('weixin')->get_weixin_app_id_setting_var();
		}

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
	}
}