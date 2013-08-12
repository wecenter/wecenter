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

class online_class extends AWS_MODEL
{
	var $db_update_intval = 60;	// 计划任务执行间隔
	
	public function online_active($uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		$data = array(
			'uid' => $uid,
			'last_active' => time(),
			'ip' => ip2long(fetch_ip()),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'active_url' => $_SERVER['HTTP_REFERER'],
		);
		
		if ($this->count('users_online', 'uid = ' . intval($uid)))
		{
			$this->shutdown_update('users_online', $data, 'uid = ' . intval($uid));
		}
		else
		{			
			$this->insert('users_online', $data);
		}
		
		$this->delete_expire_users();
		
		$this->shutdown_query("UPDATE " . get_table('users') . ' SET online_time = online_time + ' . intval($this->db_update_intval) . ', last_active = ' . time() . ' WHERE uid = ' . intval($uid));
		
		return true;
	}
	
	public function delete_expire_users()
	{		
		return $this->delete('users_online', 'last_active < ' . (time() - 1800));
	}
}
