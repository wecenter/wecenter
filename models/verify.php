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

class verify_class extends AWS_MODEL
{
	public function add_apply($uid, $name, $reason, $type, $data = array(), $attach = null)
	{
		if ($verify_apply = $this->fetch_apply($uid))
		{
			$this->remove_apply($verify_apply['id']);
		}
		
		return $this->insert('verify_apply', array(
			'uid' => $uid,
			'name' => htmlspecialchars($name),
			'reason' => htmlspecialchars($reason),
			'data' => serialize($data),
			'type' => htmlspecialchars($type),
			'attach' => $attach,
			'time' => time()
		));
	}
	
	public function fetch_apply($uid)
	{
		if ($verify_apply = $this->fetch_row('verify_apply', 'uid = ' . intval($uid)))
		{
			$verify_apply['data'] = unserialize($verify_apply['data']);
		}
		
		return $verify_apply;
	}
	
	public function remove_apply($id)
	{
		if ($verify_apply = $this->fetch_row('verify_apply', 'id = ' . intval($id)))
		{
			if ($verify_apply['attach'])
			{
				unlink(get_setting('upload_dir') . '/verify/' . $verify_apply['attach']);
			}
			
			return $this->delete('verify_apply', 'id = ' . intval($id));
		}
	}
	
	public function approval_list($page, $limit)
	{
		if ($approval_list = $this->fetch_page('verify_apply', '`status` = 0', 'time ASC', $page, $limit))
		{
			foreach ($approval_list AS $key => $val)
			{
				$approval_list[$key]['data'] = unserialize($val['data']);
			}
		}
		
		return $approval_list;
	}
	
	public function approval_verify($id)
	{
		if (!$verify_apply = $this->fetch_row('verify_apply', 'id = ' . intval($id)))
		{
			return false;
		}
		
		$this->update('verify_apply', array(
			'status' => 1
		), 'id = ' . intval($id));
		
		if ($verify_apply['type'])
		{
			$verified = $verify_apply['type'];
		}
		else
		{
			$verified = 'personal';
		}
		
		return $this->update('users', array(
			'verified' => $verified
		), 'uid = ' . intval($verify_apply['uid']));
	}
	
	public function decline_verify($id)
	{
		if (!$verify_apply = $this->fetch_row('verify_apply', 'id = ' . intval($id)))
		{
			return false;
		}
		
		return $this->update('verify_apply', array(
			'status' => -1
		), 'id = ' . intval($id));
	}
}