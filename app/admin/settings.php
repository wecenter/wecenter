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

class settings extends AWS_ADMIN_CONTROLLER
{
	public function index_action()
	{
		TPL::assign('styles', $this->model('setting')->get_ui_styles());
		TPL::assign('notification_settings', get_setting('new_user_notification_setting'));
		TPL::assign('notify_actions', $this->model('notify')->notify_action_details);
		
		$this->crumb(AWS_APP::lang()->_t('系统设置'), 'admin/settings');
		
		TPL::assign('setting', get_setting(null, false));
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(101));
		
		TPL::output('admin/settings');
	}
	
	public function save_action()
	{		
		define('IN_AJAX', TRUE);
		
		if ($_POST['upload_dir'] && preg_match('/(.*)\/$/i', $_POST['upload_dir']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传文件存放绝对路径不能以 / 结尾')));
		}
		
		if ($_POST['upload_url'] && preg_match('/(.*)\/$/i', $_POST['upload_url']))
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
					
					if (substr($n, 0, 1) != '/' || substr($m, 0, 1) != '/')
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
					}
					
					if (strstr($m, '/admin') || strstr($n, '/admin'))
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
			foreach ($curl_require_setting as $key)
			{
				if ($_POST[$key] == 'Y' && !function_exists('curl_init'))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微博登录、QQ 登录等功能须服务器支持 CURL')));
				}
			}
		}
		
		if (!$_POST['newer_content_type'])
		{
			$_POST['newer_content_type'] = array();
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
		
		$this->model('setting')->set_vars($this->model('setting')->check_vars($_POST));
		
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
	}
}