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
	var $input_message;
	
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{		
		if (!$this->model('weixin')->check_signature($_GET['signature'], $_GET['timestamp'], $_GET['nonce']))
		{
			die;
		}
		
		if ($_GET['echostr'])
		{
			echo htmlspecialchars($_GET['echostr']);
			die;
		}
		
		$this->input_message = $this->model('weixin')->fetch_message();
	}
	
	public function index_action()
	{		
		$this->model('weixin')->response_message($this->input_message);
	}
}
