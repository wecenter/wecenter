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

class message_class extends AWS_MODEL
{
	public function send_message($sender_uid, $recipient_uid, $message)
	{
		if (!$sender_uid OR !$recipient_uid OR !$message)
		{
			return false;
		}

		if (! $inbox_dialog = $this->get_dialog_by_user($sender_uid, $recipient_uid))
		{
			$inbox_dialog_id = $this->insert('inbox_dialog', array(
				'sender_uid' => $sender_uid,
				'sender_unread' => 0,
				'recipient_uid' => $recipient_uid,
				'recipient_unread' => 0,
				'add_time' => time(),
				'update_time' => time(),
				'sender_count' => 0,
				'recipient_count' => 0
			));
		}
		else
		{
			$inbox_dialog_id = $inbox_dialog['id'];
		}

		$message_id = $this->insert('inbox', array(
			'dialog_id' => $inbox_dialog_id,
			'message' => htmlspecialchars($message),
			'add_time' => time(),
			'uid' => $sender_uid
		));

		$this->update_dialog_count($inbox_dialog_id, $sender_uid);

		$this->model('account')->update_inbox_unread($recipient_uid);
		//$this->model('account')->update_inbox_unread($sender_uid);

		if ($user_info = $this->model('account')->get_user_info_by_uid($sender_uid))
		{
			$this->model('email')->action_email('NEW_MESSAGE', $recipient_uid, get_js_url('/inbox/'), array(
				'user_name' => $user_info['user_name'],
			));
		}

		return $message_id;
	}

	public function set_message_read($dialog_id, $uid, $receipt = true)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->update('inbox_dialog', array(
				'sender_unread' => 0
			), 'sender_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			if ($receipt)
			{
				$this->update('inbox', array(
				'receipt' => time()
				), 'receipt = 0 AND uid = ' . $inbox_dialog['recipient_uid'] . ' AND dialog_id = ' . intval($dialog_id));
			}

		}

		if ($inbox_dialog['recipient_uid'] == $uid)
		{
			$this->update('inbox_dialog', array(
				'recipient_unread' => 0
			), "recipient_uid = " . intval($uid) . " AND id = " . intval($dialog_id));

			if ($receipt)
			{
				$this->update('inbox', array(
					'receipt' => time()
				), 'receipt = 0 AND uid = ' . $inbox_dialog['sender_uid'] . ' AND dialog_id = ' . intval($dialog_id));
			}
		}

		$this->model('account')->update_inbox_unread($uid);

		return true;
	}

	public function update_dialog_count($dialog_id, $uid)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

		$this->update('inbox_dialog', array(
			'sender_count' => $this->count('inbox', 'uid IN(' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND sender_remove = 0 AND dialog_id = ' . intval($dialog_id)),
			'recipient_count' => $this->count('inbox', 'uid IN(' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND recipient_remove = 0 AND dialog_id = ' . intval($dialog_id)),
			'update_time' => time()
		), 'id = ' . intval($dialog_id));

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->query("UPDATE " . get_table('inbox_dialog') . " SET recipient_unread = recipient_unread + 1 WHERE id = " . intval($dialog_id));
		}
		else
		{
			$this->query("UPDATE " . get_table('inbox_dialog') . " SET sender_unread = sender_unread + 1 WHERE id = " . intval($dialog_id));
		}
	}

	public function get_dialog_by_id($dialog_id)
	{
		return $this->fetch_row('inbox_dialog', 'id = ' . intval($dialog_id));
	}

	public function get_message_by_dialog_id($dialog_id)
	{
		if ($inbox = $this->fetch_all('inbox', 'dialog_id = ' . intval($dialog_id), 'id DESC'))
		{
			foreach ($inbox AS $key => $val)
			{
				$message[$val['id']] = $val;
			}
		}

		return $message;
	}

	public function delete_dialog($dialog_id, $uid)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->set_message_read($dialog_id, $uid, false);

			$this->update('inbox_dialog', array(
				'sender_count' => 0
			), 'sender_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			$this->update('inbox', array(
				'sender_remove' => 1
			), 'uid IN (' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND dialog_id = ' . intval($dialog_id));
		}

		if ($inbox_dialog['recipient_uid'] == $uid)
		{
			$this->set_message_read($dialog_id, $inbox_dialog['recipient_uid'], false);

			$this->update('inbox_dialog', array(
				'recipient_count' => 0
			), 'recipient_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			$this->update('inbox', array(
				'recipient_remove' => 1
			), 'uid IN (' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND dialog_id = ' . intval($dialog_id));
		}

		$this->model('account')->update_inbox_unread($uid);

		return true;
	}

	public function get_inbox_message($page = 1, $limit = 10, $uid = null)
	{
		return $this->fetch_page('inbox_dialog', '(sender_uid = ' . intval($uid) . ' AND sender_count > 0) OR (recipient_uid = ' . intval($uid) . ' AND recipient_count > 0)', 'update_time DESC', $page, $limit);
	}

	public function get_last_messages($dialog_ids)
	{
		if (!is_array($dialog_ids))
		{
			return false;
		}

		foreach ($dialog_ids as $dialog_id)
		{
			$dialog_message = $this->fetch_row('inbox', 'dialog_id = ' . intval($dialog_id), 'id DESC');

			$last_message[$dialog_id] = cjk_substr($dialog_message['message'], 0, 60, 'UTF-8', '...');
		}

		return $last_message;
	}

	public function get_dialog_by_user($sender_uid, $recipient_uid)
	{
		return $this->fetch_row('inbox_dialog', "(`sender_uid` = " . intval($sender_uid) . " AND `recipient_uid` = " . intval($recipient_uid) . ") OR (`recipient_uid` = " . intval($sender_uid) . " AND `sender_uid` = " . intval($recipient_uid) . ")");
	}

	public function check_permission($uid, $sender_uid)
	{
		if ($user_friends_ids = $this->model('follow')->get_user_friends_ids($uid))
		{
			if (! in_array($sender_uid, $user_friends_ids))
			{
				return false;
			}

			return true;
		}
	}

	public function removed_message_clean()
	{
		$this->delete('inbox', 'sender_remove = 1 AND recipient_receipt = 1');
		$this->delete('inbox_dialog', 'sender_count = 0 AND recipient_count = 0');
	}
}