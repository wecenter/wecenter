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

class verify_class extends AWS_MODEL
{
	public function add_apply($uid, $reason)
	{
		return $this->insert('verify_apply', array(
			'uid' => $uid,
			'reason' => htmlspecialchars($reason),
			'time' => time()
		));
	}
	
	public function fetch_apply($uid)
	{
		return $this->fetch_row('verify_apply', 'uid = ' . intval($uid));
	}
	
	public function approval_list($page, $limit)
	{
		return $this->fetch_page('verify_apply', null, 'time ASC', $page, $limit);
	}
	
	public function approval_verify($id)
	{
		$apply = $this->fetch_row('verify_apply', 'id = ' . intval($id));
		
		$this->update('users', array(
			'verified' => 1
		), 'uid = ' . intval($apply['uid']));
		
		return $this->delete('verify_apply', 'id = ' . intval($id));
	}
	
	public function decline_verify($id)
	{
		return $this->delete('verify_apply', 'id = ' . intval($id));
	}
}