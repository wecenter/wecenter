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

class invitation_class extends AWS_MODEL
{
	public function get_unique_invitation_code()
	{
		$invitation_code = md5(uniqid(rand(), true) . fetch_salt(4));

		if ($this->fetch_row('invitation', "invitation_code = '" . $this->quote($invitation_code) . "'"))
		{
			return $this->get_unique_invitation_code();
		}
		else
		{
			return $invitation_code;
		}
	}

	public function add_invitation($uid, $invitation_code, $invitation_email, $add_time, $add_ip)
	{
		$this->query("UPDATE " . $this->get_table('users') . " SET invitation_available = invitation_available - 1 WHERE uid = " . intval($uid));

		return $this->insert('invitation', array(
			'uid' => intval($uid),
			'invitation_code' => $invitation_code,
			'invitation_email' => $invitation_email,
			'add_time' => $add_time,
			'add_ip' => $add_ip
		));
	}

	public function get_active_invitation_by_email($email)
	{
		return $this->fetch_row('invitation', "active_status = 0 AND invitation_email = '" . $this->quote($email) . "'");
	}

	public function get_invitation_list($uid, $limit = null, $orderby = "invitation_id DESC")
	{
		if ($uid)
		{
			$where = 'uid = ' . intval($uid);
		}

		return $this->fetch_all('invitation', $where, $orderby, $limit);
	}

	public function get_invitation_by_id($invitation_id)
	{
		return $this->fetch_row('invitation', 'invitation_id = ' . intval($invitation_id));
	}

	public function cancel_invitation_by_id($invitation_id)
	{
		if (!$invitation_info = $this->get_invitation_by_id($invitation_id))
		{
			return false;
		}

		if ($this->delete('invitation', 'invitation_id = ' . intval($invitation_id)))
		{
			$this->query("UPDATE " . $this->get_table('users') . " SET invitation_available = invitation_available + 1 WHERE uid = " . $invitation_info['uid']);
		}
	}

	public function get_invitation_by_code($invitation_code)
	{
		return $this->fetch_row('invitation', "invitation_code = '" . $this->quote($invitation_code) . "'");
	}

	public function check_code_available($invitation_code)
	{
		return $this->fetch_row('invitation', "active_status = 0 AND active_expire <> 1 AND invitation_code = '" . $this->quote($invitation_code) . "'");
	}

	public function invitation_code_active($invitation_code, $active_time, $active_ip, $active_uid)
	{
		return $this->update('invitation', array(
			'active_time' => $active_time,
			'active_ip' => $active_ip,
			'active_uid' => $active_uid,
			'active_status' => 1
		), "invitation_code = '" . $this->quote($invitation_code) . "' AND active_status = 0");
	}

	public function send_invitation_email($invitation_id)
	{
		$invitation_row = $this->get_invitation_by_id($invitation_id);

		if ($invitation_row['active_status'] == 1)
		{
			return true;
		}

		$user_info = $this->model('account')->get_user_info_by_uid($invitation_row['uid']);

		$email_hash = base64_encode(H::encode_hash(array(
			'email' => $invitation_row['invitation_email']
		)));

		return $this->model('email')->action_email('INVITE_REG', $invitation_row['invitation_email'], get_js_url('/account/register/email-' . urlencode($invitation_row['invitation_email']) . '__icode-' . $invitation_row['invitation_code']), array(
			'user_name' => $user_info['user_name'],
		));
	}

	public function send_batch_invitations($email_list, $uid, $user_name)
	{
		foreach ($email_list as $key => $email)
		{
			if ($this->model('account')->check_email($email))
			{
				continue;
			}

			$invitation_code = $this->get_unique_invitation_code();

			$this->model('invitation')->add_invitation($uid, $invitation_code, $email, time(), ip2long($_SERVER['REMOTE_ADDR']));

			$this->model('email')->action_email('INVITE_REG', $email, get_js_url('/account/register/email-' . urlencode($email) . '__icode-' . $invitation_code), array(
				'user_name' => $user_name,
			));
		}

		return true;
	}
}