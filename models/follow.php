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

class follow_class extends AWS_MODEL
{
	public function user_follow_add($fans_uid, $friend_uid)
	{		
		if ($fans_uid == $friend_uid)
		{
			return false;
		}
		
		if (! $this->model('account')->check_uid($fans_uid) || ! $this->model('account')->check_uid($friend_uid))
		{
			return false;
		}
		
		if ($this->user_follow_check($fans_uid, $friend_uid))
		{
			return false;
		}
		else
		{			
			$result = $this->insert('user_follow', array(
				'fans_uid' => intval($fans_uid),
				'friend_uid' => intval($friend_uid),
				'add_time' => time()
			));
			
			$this->update_user_count($friend_uid);
			$this->update_user_count($fans_uid);
			
			return $result;
		}
	
	}

	public function user_follow_check($fans_uid, $friend_uid)
	{
		if (! $fans_uid OR ! $friend_uid)
		{
			return false;
		}
		
		if ($fans_uid == $friend_uid)
		{
			return false;
		}
		
		return $this->count('user_follow', "fans_uid = " . intval($fans_uid) . " AND friend_uid = " . intval($friend_uid));	
	}
	
	public function users_follow_check($fans_uid, $friend_uids)
	{		
		if (! $fans_uid OR ! is_array($friend_uids))
		{
			return false;
		}
		
		$user_follow = $this->fetch_all('user_follow', "fans_uid = " . intval($fans_uid) . " AND friend_uid IN (" . implode(',', $friend_uids) . ")");
		
		foreach ($user_follow AS $key => $val)
		{
			$result[$val['friend_uid']] = TRUE;
		}
		
		return $result;
	}
	
	public function user_follow_del($fans_uid, $friend_uid)
	{
		if (! $fans_uid OR ! $friend_uid)
		{
			return false;
		}
		
		if (! $this->user_follow_check($fans_uid, $friend_uid))
		{
			return false;
		}
		else
		{
			$result = $this->delete('user_follow', "fans_uid = " . intval($fans_uid) . " AND friend_uid = " . intval($friend_uid));
			
			$this->update_user_count($friend_uid);
			$this->update_user_count($fans_uid);
			
			return $result;
		}
	}

	/**
	 * 获取单个用户的粉丝列表
	 * 
	 * @param  $friend_uid		
	 * @param  $limit
	 */
	public function get_user_fans($friend_uid, $limit = 20)
	{		
		if (!$user_fans = $this->fetch_all('user_follow', 'friend_uid = ' . intval($friend_uid), 'add_time DESC', $limit))
		{
			return false;
		}
		
		foreach ($user_fans AS $key => $val)
		{
			$fans_uids[$val['fans_uid']] = $val['fans_uid'];
		}
		
		return $this->model('account')->get_user_info_by_uids($fans_uids, true);
	}

	/**
	 * 获取单个用户的关注列表(我关注的人)
	 * 
	 * @param  $friend_uid
	 * @param  $limit
	 */
	public function get_user_friends($fans_uid, $limit = 20)
	{
		if (!$user_follow = $this->fetch_all('user_follow', 'fans_uid = ' . intval($fans_uid), 'add_time DESC', $limit))
		{
			return false;
		}
		
		foreach ($user_follow AS $key => $val)
		{
			$friend_uids[$val['friend_uid']] = $val['friend_uid'];
		}
		
		return $this->model('account')->get_user_info_by_uids($friend_uids, true);
	}
	
	public function get_user_friends_ids($fans_uid)
	{
		if (!$user_follow = $this->fetch_all('user_follow', 'fans_uid = ' . intval($fans_uid)))
		{
			return false;
		}
		
		foreach ($user_follow AS $key => $val)
		{
			$friend_uids[$val['friend_uid']] = $val['friend_uid'];
		}
		
		return $friend_uids;
	}
	
	public function get_fans_count($friend_uid)
	{
		return $this->count('user_follow', 'friend_uid = ' . intval($friend_uid));
	}

	public function get_friends_count($fans_uid)
	{
		return $this->count('user_follow', 'fans_uid = ' . intval($fans_uid));
	}
	
	public function update_user_count($uid)
	{	
		return $this->update('users', array(
			'fans_count' => $this->get_fans_count($uid),
			'friend_count' => $this->get_friends_count($uid)
		), 'uid = ' . intval($uid));
	}
}