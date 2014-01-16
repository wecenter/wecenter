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
	public function get_user_info_by_session_id_action()
	{
		if ($session_info = $this->model('system')->fetch_row('session', "id = '" . $this->model('system')->quote($_GET['session_id']) . "'"))
		{
			if (time() <= ($session_info['modified'] + $session_info['lifetime']))
			{
				$session_data = explode('|', $session_info['data']);
				
				echo json_encode($session_data[1]);
			}
		}
	}
}