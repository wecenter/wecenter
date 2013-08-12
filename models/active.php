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

class active_class extends AWS_MODEL
{
	public function active_code_generate()
	{
		return substr(strtolower(md5(uniqid(rand()))), 0, 20);
	}

	public function active_code_check($active_code, $active_type)
	{
		if (!$active_code)
		{
			return false;
		}
		
		if (! preg_match("/^[0-9A-Za-z]+$/", $active_code))
		{
			return false;
		}
		
		if ($active_info = $this->fetch_row('active_tbl', "active_type = " . intval($active_type) . " AND active_code = '" . $this->quote($active_code) . "' AND expire_time > " . time()))
		{
			if (!$active_info['active_time'] AND !$active_info['active_ip'] AND !$active_info['active_expire'])
			{
				return true;
			}
		}
		
		return false;
	}

	public function active_code_active($active_code, $active_type)
	{
		if (!$active_code)
		{
			return false;
		}
		
		if (! preg_match("/^[0-9A-Za-z]+$/", $active_code))
		{
			return false;
		}
		
		if (!$active_info = $this->fetch_row('active_tbl', "active_type = " . intval($active_type) . " AND active_code = '" . $this->quote($active_code) . "' AND ((active_time is NULL AND active_ip is NULL) OR (active_time = '' AND active_ip = ''))"))
		{
			return false;
		}
		
		$this->update('active_tbl', array(
			'active_time' => time(),
			'active_ip' => time(),
			'active_expire' => 1
		), 'active_id = ' . intval($active_info['active_id']));
		
		switch ($active_type)
		{		
			case 2 : //修改电子邮件
				return $this->model('account')->update_user_fields(array(
					'user_email' => $active_info['active_values']
				), $active_info['uid']);
			break;
			
			case 21 :
			case 11 :
				return $active_info['uid'];
			break;		
		}
		
		return true;
	}

	public function active_add($uid, $expire_time, $active_code, $active_type, $active_values = '', $active_type_code = '')
	{
		if ($active_id = $this->insert('active_tbl', array(
			'uid' => intval($uid),
			'expire_time' => intval($expire_time),
			'active_code' => $active_code,
			'active_type' => intval($active_type),
			'active_values' => $active_values,
			'active_type_code' => $active_type_code,
			'add_time' => time(),
			'add_ip' => ip2long(fetch_ip())
		)))
		{
			$this->update('active_tbl', array(
				'active_expire' => 1
			), "uid = " . intval($uid) . " AND active_type = " . intval($active_type) . " AND active_id <> " . intval($active_id));
		}
		
		return $active_id;
	
	}
	
	public function get_active_code_row($active_code, $active_type = 0)
	{
		if (!$active_code)
		{
			return false;
		}
		
		if (! preg_match("/^[0-9A-Za-z]+$/", $active_code))
		{
			return false;
		}
		
		return $this->fetch_row('active_tbl', "active_type = " . intval($active_type) . " AND active_code = '" . $this->quote($active_code) . "'");
	
	}
	
	public function new_valid_email($uid, $email = '')
	{
		if (!$uid AND !$email)
		{
			return false;
		}
		
		$active_code_hash = $this->active_code_generate();
		
		$active_id = $this->active_add($uid, (time() + 60 * 60 * 24), $active_code_hash, 21, '', 'VALID_EMAIL');

		if ($email)
		{
			$uid = $email;
		}
		
		return $this->model('email')->action_email('VALID_EMAIL', $uid, get_js_url('/account/valid_email_active/key-' . $active_code_hash));
	}
	
	public function new_find_password($uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		$active_code_hash = $this->active_code_generate();
		
		$active_id = $this->model('active')->active_add($uid, (time() + 60 * 60 * 24), $active_code_hash, 11, '', 'FIND_PASSWORD');
		
		return $this->model('email')->action_email('FIND_PASSWORD', $uid, get_js_url('/account/find_password/modify/key-' . $active_code_hash));
	}
	
	public function clean_expire()
	{
		return $this->delete('active_tbl', 'expire_time < ' . time());
	}
}