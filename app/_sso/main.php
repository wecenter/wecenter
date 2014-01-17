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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function get_user_info_by_session_id_action()
	{
		if ($session_info = $this->model('system')->fetch_row('sessions', "id = '" . $this->model('system')->quote($_GET['session_id']) . "'"))
		{
			if (time() <= ($session_info['modified'] + $session_info['lifetime']))
			{
				$session_data = explode('|', $session_info['data']);
				
				unset($session_data[0]);
				
				$session_data = implode($session_data, '|');
				
				print_r(unserialize($session_data)); die;
				
				echo json_encode(unserialize($session_data));
			}
		}
	}
}