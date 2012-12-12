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

class online_class extends AWS_MODEL
{
	
	var $db_update_intval = 30; //数据表更新时间间隔

	
	/**
	 * 根据条件获得在线用户列表
	 * @param $count
	 * @param $where
	 */
	public function get_db_online_users($count = false, $where = null, $limit = '10', $order_by = "last_active DESC")
	{
		if ($count)
		{
			return $this->count('users_online', $where);
		}
		else
		{
			return $this->fetch_all('users_online', $where, $order_by, $limit);
		}
	}

	/**
	 * 当前用户激活在线状态
	 */
	public function online_active($uid)
	{
		if (get_setting('online_count_open') == 'N' || !$uid)
		{
			return false;
		}
		
		$online_user = array(
			'last_active' => time(),
			'ip' => ip2long(fetch_ip()),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'active_url' => $_SERVER['HTTP_REFERER'],
		);
		
		if($this->get_user_online_by_uid($uid))
		{
			$this->update_user_online($uid, $online_user);
		}
		else
		{
			$online_user['uid'] = $uid;
			$this->insert('users_online', $online_user);
		}
		
		$this->delete_expire_users();	//清除在线过期用户
		
		$this->query("UPDATE " . get_table('users') . ' SET online_time = online_time + ' . intval($this->db_update_intval) . ', last_active = ' . time() . ' WHERE uid = ' . intval($uid));
		
		return true;
	}
	
	public function get_user_online_by_uid($uid)
	{
		return $this->fetch_row('users_online', 'uid = ' . intval($uid));
	}

	/**
	 * 更新用户在线数据
	 * @param $online_id
	 * @param $update_arr
	 */
	public function update_user_online($uid, $update_arr)
	{
		return $this->update('users_online', $update_arr, "uid = " . intval($uid));
	}

	/**
	 * 清除超过时间不在线的会员
	 */
	public function delete_expire_users()
	{
		$expire = time() - intval(get_setting('online_interval') * 60);
		
		return $this->delete('users_online', 'last_active < ' . $expire);
	}

	public function logout($uid = 0)
	{
		if(!$uid)
		{
			$uid = USER::get_client_uid();
		}
		
		return $this->delete('users_online', 'uid = ' . $uid);
	}

}
