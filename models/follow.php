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

	/**
	 * 添加关注
	 * 
	 * @param  $fans_uid			用户UID
	 * @param  $friend_uid  所关注的用户UID
	 * 
	 * @return bool
	 */
	function user_follow_add($fans_uid, $friend_uid)
	{
		$fans_uid = intval($fans_uid);
		$friend_uid = intval($friend_uid);
		
		if (! $fans_uid || ! $friend_uid)
		{
			return false;
		}
		
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
			$insert_data['fans_uid'] = $fans_uid;
			$insert_data['friend_uid'] = $friend_uid;
			$insert_data['add_time'] = time();
			
			$result = $this->insert('user_follow', $insert_data);
			
			$this->update_user_count($friend_uid);
			$this->update_user_count($fans_uid);
			
			return $result;
		}
	
	}

	/**
	 * 检查关注是否存在
	 * 
	 * @param  $fans_uid			用户 UID
	 * @param  $friend_uid		  所关注的用户 UID
	 * 
	 * @return bool
	 */
	function user_follow_check($fans_uid, $friend_uid)
	{
		$fans_uid = intval($fans_uid);
		$friend_uid = intval($friend_uid);
		
		if (! $fans_uid || ! $friend_uid)
		{
			return false;
		}
		
		if ($fans_uid == $friend_uid)
		{
			return false;
		}
		
		return $this->count('user_follow', "fans_uid = {$fans_uid} AND friend_uid = {$friend_uid}");	
	}
	
	/**
	 * 检查关注是否存在
	 * 
	 * @param  $fans_uid			用户 UID
	 * @param  $friend_uid		  所关注的用户 UID
	 * 
	 * @return bool
	 */
	function users_follow_check($fans_uid, $friend_uids)
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

	/**
	 * 删除关注
	 * 
	 * @param  $fans_uid	用户UID
	 * @param  $friend_uid	被关注的UID
	 */
	function user_follow_del($fans_uid, $friend_uid)
	{
		$fans_uid = intval($fans_uid);
		$friend_uid = intval($friend_uid);
		
		if (! $fans_uid || ! $friend_uid)
		{
			return false;
		}
		
		if (! $this->user_follow_check($fans_uid, $friend_uid))
		{
			return false;
		}
		else
		{
			$result = $this->delete("user_follow", "fans_uid = {$fans_uid} AND friend_uid = {$friend_uid}");
			
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
	function get_user_fans($friend_uid, $limit = 20)
	{
		$friend_uid = intval($friend_uid);
		
		if (! $friend_uid)
		{
			return false;
		}
		
		$sql = "SELECT UF.*, MEM.uid, MEM.user_name, MEM.url_token, MEM.avatar_file, MEM.reputation, MEM.agree_count, MEM.thanks_count,
			MEB.*
			FROM   " . $this->get_table("user_follow") . " AS UF 
			LEFT JOIN " . $this->get_table("users") . " AS MEM ON UF.fans_uid = MEM.uid 
			LEFT JOIN " . $this->get_table("users_attrib") . " AS MEB ON UF.fans_uid = MEB.uid
			WHERE friend_uid = {$friend_uid} AND MEM.UID > 0 AND (MEM.group_id <> 3 AND MEM.forbidden = 0)";
		
		if ($user_fans = $this->query_all($sql, $limit))
		{
			foreach($user_fans as $key => $val)
			{
				if (!$val['uid'])
				{
					unset($user_fans[$key]);
				}
				else if (!$val['url_token'])
				{
					$user_fans[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}
		
		return $user_fans;
	}

	/**
	 * 获取单个用户的关注列表(我关注的人)
	 * 
	 * @param  $friend_uid
	 * @param  $limit
	 */
	function get_user_friends($fans_uid, $limit = 20)
	{
		$fans_uid = intval($fans_uid);
		
		if (! $fans_uid)
		{
			return false;
		}
		
		$sql = "SELECT UF.*,
				MEM.uid, MEM.user_name, MEM.url_token, MEM.avatar_file, MEM.reputation, MEM.agree_count, MEM.thanks_count, MEM.url_token,
				MEB.*
				FROM " . $this->get_table('user_follow') . " AS UF 
				LEFT JOIN " . $this->get_table('users') . " AS MEM 
				ON UF.friend_uid = MEM.uid
				LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB				
				ON UF.friend_uid = MEB.uid
				WHERE fans_uid = {$fans_uid} AND MEM.uid > 0 AND (MEM.group_id <> 3 AND MEM.forbidden = 0) ORDER BY UF.add_time DESC";
		
		if ($user_friends = $this->query_all($sql, $limit))
		{
			foreach($user_friends as $key => $val)
			{
				if (!$val['uid'])
				{
					unset($user_friends[$key]);
				}
				else if (!$val['url_token'])
				{
					$user_friends[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}
		
		return $user_friends;
	}

	/**
	 * 获取多个用户的关注列表
	 * 
	 * @param  $fans_uid_array
	 * @param  $limit
	 */
	function get_users_friends($fans_uid_array, $limit = 20)
	{
		if (! $fans_uid_array || ! is_array($fans_uid_array))
		{
			return false;
		}
		
		$fans_uids = implode(',', $fans_uid_array);
		
		if (! $fans_uids)
		{
			return false;
		}
		
		$sql = "SELECT UF.*,
				MEM.uid, MEM.user_name, MEM.url_token, MEM.avatar_file,
				MEB.*, UF.fans_uid  
				FROM " . $this->get_table('user_follow') . " AS UF 
				LEFT JOIN " . $this->get_table('users') . " AS MEM 
				ON UF.friend_uid = MEM.uid
				LEFT JOIN " . $this->get_table('users_attrib') . " AS MEB				
				ON UF.friend_uid = MEB.uid
				WHERE fans_uid IN ({$fans_uids}) AND (MEM.group_id <> 3 AND MEM.forbidden = 0) ORDER BY UF.add_time DESC";
		
		if ($users_friends = $this->query_all($sql, $limit))
		{
			foreach($users_friends as $key => $val)
			{
				if (!$val['uid'])
				{
					unset($user_friends[$key]);
				}
				else if (!$val['url_token'])
				{
					$users_friends[$key]['url_token'] = urlencode($val['uid']);
				}
			}
		}
		
		return $users_friends;
	}

	/**
	 * 获得用户粉丝数量
	 */
	function get_fans_count($friend_uid)
	{
		return $this->count('user_follow', 'friend_uid = ' . intval($friend_uid));
	}

	/**
	 * 获得用户关注的人的数量
	 */
	function get_friends_count($fans_uid)
	{
		return $this->count('user_follow', 'fans_uid = ' . intval($fans_uid));
	}
	
	function update_user_count($uid)
	{	
		return $this->update('users', array(
			'fans_count' => $this->get_fans_count($uid),
			"friend_count" => $this->get_friends_count($uid)
		), 'uid = ' . intval($uid));
	}
}