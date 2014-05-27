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

class api extends AWS_CONTROLLER
{
	private $input_message;

	private $mp_menu;

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		$account_id = $_GET['account'] ?: 0;

		if (!$account_info = $this->model('weixin')->get_account_info_by_id($account_id) OR
			!$this->model('weixin')->check_signature($account_info['weixin_mp_token'], $_GET['signature'], $_GET['timestamp'], $_GET['nonce']))
		{
			die;
		}

		if ($_GET['echostr'])
		{
			echo htmlspecialchars($_GET['echostr']);
			die;
		}

		$this->input_message = $this->model('weixin')->fetch_message();

		$this->mp_menu = ($account_info['weixin_account_role'] == 'base' OR empty($account_info['weixin_app_id']) OR empty($account_info['weixin_app_secret'])) ? null : $account_info['weixin_mp_menu'];
	}

	public function index_action()
	{
		$this->model('weixin')->response_message($this->input_message, $this->mp_menu);
	}
}
