<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class api extends AWS_CONTROLLER
{
	//var $api_data;
	
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{
		/*if (!$input = file_get_contents('php://input'))
		{
			die;
		}
		
		if ($xml = new Zend_Config_Xml('<?xml version="1.0"?>' . $input))
		{
			$this->api_data = $xml->toArray();
		}*/
		
		if (!$this->model('weixin')->check_signature($_GET['signature'], $_GET['timestamp'], $_GET['nonce']))
		{
			die;
		}
	}
	
	public function index_action()
	{
		$this->model('weixin')->response_message();
	}
}
