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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function login_process_action()
	{
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (!$user_info = $this->model('ucenter')->login($_GET['user_name'], $_GET['password']))
			{
				$user_info = $this->model('account')->check_login($_GET['user_name'], $_GET['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($_GET['user_name'], $_GET['password']);
		}

		if ($user_info)
		{
			if ($user_info['forbidden'] == 1)
			{
				echo jsonp_encode(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
			}

			if ($_POST['net_auto_login'])
			{
				$expire = 60 * 60 * 24 * 360;
			}

			$this->model('account')->update_user_last_login($user_info['uid']);
			$this->model('account')->logout();

			$this->model('account')->setcookie_login($user_info['uid'], $_GET['user_name'], $_GET['password'], $user_info['salt'], $expire);

			echo jsonp_encode(AWS_APP::RSM(null, 1, null));
		}
		else
		{
			echo jsonp_encode(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
	}

	public function check_hash_login_action()
	{
		if ($user_info = $this->model('account')->check_hash_login($_POST['user_name'], $_POST['password']))
		{
			echo json_encode($user_info);
		}
	}
}
