<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
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

	public function active_code_active($active_code, $active_type_code)
	{
		if (!$active_type_code)
		{
			return false;
		}

		if (!$active_info = $this->fetch_row('active_data', "active_type_code = '" . $this->quote($active_type_code) . "' AND active_code = '" . $this->quote($active_code) . "' AND ((active_time is NULL AND active_ip is NULL) OR (active_time = '' AND active_ip = ''))"))
		{
			return false;
		}

		$this->update('active_data', array(
			'active_time' => time(),
			'active_ip' => time(),
		), 'active_id = ' . intval($active_info['active_id']));

		switch ($active_type_code)
		{
			case 'VALID_EMAIL':
			case 'FIND_PASSWORD':
				return $active_info['uid'];
			break;
		}

		return true;
	}

	public function new_active_code($uid, $expire_time, $active_code, $active_type_code = null)
	{
		if ($active_id = $this->insert('active_data', array(
			'uid' => intval($uid),
			'expire_time' => intval($expire_time),
			'active_code' => $active_code,
			'active_type_code' => $active_type_code,
			'add_time' => time(),
			'add_ip' => ip2long(fetch_ip())
		)))
		{
			$this->delete('active_data', "uid = " . intval($uid) . " AND active_type_code = '" . $this->quote($active_type) . "' AND active_id <> " . intval($active_id));
		}

		return $active_id;

	}

	public function get_active_code($active_code, $active_type_code = null)
	{
		if (!$active_code)
		{
			return false;
		}

		return $this->fetch_row('active_data', "active_code = '" . $this->quote($active_code) . "' AND active_type_code = '" . $this->quote($active_type_code) . "'");

	}

	public function new_valid_email($uid, $email = null, $server = 'master')
	{
		if (!$uid)
		{
			return false;
		}

		$active_code_hash = $this->active_code_generate();

		$active_id = $this->new_active_code($uid, (time() + 60 * 60 * 24), $active_code_hash, 'VALID_EMAIL');

		if ($email)
		{
			$uid = $email;
		}

		return $this->model('email')->action_email('VALID_EMAIL', $uid, get_js_url('/account/valid_email_active/key-' . $active_code_hash), $server);
	}

	public function new_find_password($uid, $server = 'master')
	{
		if (!$uid)
		{
			return false;
		}

		$active_code_hash = $this->active_code_generate();

		$active_id = $this->model('active')->new_active_code($uid, (time() + 60 * 60 * 24), $active_code_hash, 'FIND_PASSWORD');

		return $this->model('email')->action_email('FIND_PASSWORD', $uid, get_js_url('/account/find_password/modify/key-' . $active_code_hash), $server);
	}

	public function clean_expire()
	{
		return $this->delete('active_data', 'expire_time < ' . time());
	}

	public function set_user_email_valid_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		return $this->update('users', array(
			'valid_email' => 1,
		), 'uid = ' . intval($uid));
	}

	public function active_user_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		return $this->update('users', array(
			'group_id' => 4,
		), 'uid = ' . intval($uid));
	}

	public function send_valid_email_crond()
	{
		$now = time();

		$lock_time = AWS_APP::cache()->get('send_valid_email_locker');

		if ($lock_time AND $now - $lock_time <= 300)
		{
			return false;
		}

		$where[] = 'valid_email <> 1';

		$where[] = 'reg_time < ' . ($now - 604800);

		$last_sent_id = get_setting('last_sent_valid_email_id');

		if ($last_sent_id)
		{
			$where[] = 'uid > ' . intval($last_sent_id);
		}

		$invalid_email_users = $this->fetch_all('users', implode(' AND ', $where), null, 200);

		if (!$invalid_email_users)
		{
			return false;
		}

		AWS_APP::cache()->set('send_valid_email_locker', $now, 300);

		foreach ($invalid_email_users AS $invalid_email_user)
		{
			if ($invalid_email_user['email'])
			{
				$this->new_valid_email($invalid_email_user['uid'], $invalid_email_user['email'], 'slave');
			}

			$this->model('setting')->set_vars(array(
				'last_sent_valid_email_id' => $invalid_email_user['uid']
			));
		}

		AWS_APP::cache()->delete('send_valid_email_locker');

		return true;
	}
}