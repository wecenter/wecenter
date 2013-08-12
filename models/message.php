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

class message_class extends AWS_MODEL
{

	/**
	 * 用户发送短信息类
	 * @param $sender_uid	发送用户ID
	 * @param $recipient_uid	接收用户ID
	 * @param $notice_title	短信息标题
	 * @param $notice_content	短信息内容
	 * @param $notice_type		短信息类型，0-普通消息10-系统发的消息，不能回复11-系统通知
	 * @param $sender_del		发送者删除，默认0
	 * @param $recipient_del	接收者删除，默认0
	 */
	public function send_message($sender_uid, $recipient_uid, $notice_title, $notice_content, $notice_type = 0, $sender_del = 0, $recipient_del = 0)
	{
		if (empty($sender_uid) || empty($recipient_uid) || empty($notice_content))
		{
			return false;
		}
		
		//会话不存在则创建
		if (! $dialog_info = $this->get_dialog($sender_uid, $recipient_uid))
		{
			$dialog_id = $this->insert('notice_dialog', array(
				'sender_uid' => $sender_uid,  // 发送者UID
				'sender_unread' => 0,  // 发送者未读
				'recipient_uid' => $recipient_uid,  // 接收者UID
				'recipient_unread' => 1,  // 接收者未读
				'add_time' => time(),  // 添加时间
				'last_time' => time(),  // 最后更新时间
				'last_notice_id' => 0,  // 最后短消息ID
				'sender_count' => 1,  // 发送者显示对话条数
				'recipient_count' => 1,  // 接收者显示对话条数
				'all_count' => 1 // 总对话条数
			));
		}
		else
		{
			$dialog_id = $dialog_info['dialog_id'];
			
			if ($dialog_info['sender_uid'] == $sender_uid)
			{
				$this->add_dialog_unread($dialog_id, 'recipient_unread');
			}
			else if ($dialog_info['recipient_uid'] == $sender_uid)
			{
				$this->add_dialog_unread($dialog_id, 'sender_unread');
			}
		}
		
		$message_id = $this->insert('notice', array(
			'dialog_id' => $dialog_id,  // 会话ID
			'notice_title' => htmlspecialchars($notice_title), 
			'notice_content' => htmlspecialchars($notice_content), 
			'add_time' => time(),  // 添加时间
			'sender_uid' => $sender_uid,  // 最后短消息ID
			'notice_type' => $notice_type
		));
		
		// 增加附加表数据
		$this->insert('notice_recipient', array(
			'dialog_id' => $dialog_id, 
			'notice_id' => $message_id, 
			'sender_uid' => $sender_uid, 
			'sender_time' => time(), 
			'recipient_uid' => $recipient_uid, 
			'recipient_time' => 0
		));
		
		// 更新用户未读通知汇总数据
		$this->model('account')->update_notic_unread($recipient_uid);
		
		$userinfo = $this->model('account')->get_user_info_by_uid($sender_uid);
		
		$this->model('email')->action_email('NEW_MESSAGE', $recipient_uid, get_js_url('/inbox/'), array(
			'user_name' => $userinfo['user_name'],
		));
		
		return $message_id;
	}

	/**
	 * 更新对话表未读数
	 * @param $dialog_id
	 * @param $filed
	 */
	public function add_dialog_unread($dialog_id, $filed)
	{
		if (! in_array($filed, array('sender_unread','recipient_unread')))
		{
			return false;
		}
		
		$data = array(
			'last_time' => time(), 
			'sender_count' => 'sender_count + 1', 
			'recipient_count' => 'recipient_count + 1', 
			'all_count' => 'all_count + 1', 
			$filed => $filed . ' + 1'
		);
		
		foreach ($data as $key => $val)
		{
			$update_sql[] = "`{$key}` = {$val}";
		}
		
		$this->shutdown_query("UPDATE " . $this->get_table('notice_dialog') . " SET " . implode(',', $update_sql) . " WHERE dialog_id = " . intval($dialog_id));
		
		return true;
	}

	/**
	 * 标示已读对话
	 * @param $dialog_id
	 */
	public function read_message($dialog_id, $uid)
	{
		// 更新短信息列表
		if ($this->update('notice_recipient', array(
				'recipient_time' => time()
		), 'recipient_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id)))
		{
			$this->update('notice_dialog', array(
				'sender_unread' => 0
			), 'sender_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id));
			
			$this->update('notice_dialog', array(
				'recipient_unread' => 0
			), "recipient_uid = " . intval($uid) . " AND dialog_id = " . intval($dialog_id));
				
			// 更新用户未读通知汇总数据
			$this->model('account')->update_notic_unread($uid);
		}
		
		return true;
	}
	
	public function get_dialog_by_id($dialog_id)
	{
		return $this->fetch_row('notice_dialog', 'dialog_id = ' . intval($dialog_id));
	}

	/**
	 * 阅读短信息
	 */
	public function get_message_by_dialog_id($dialog_id, $uid)
	{
		if ($notice_query = $this->fetch_all('notice', 'dialog_id = ' . intval($dialog_id), 'notice_id DESC'))
		{
			foreach ($notice_query AS $key => $val)
			{
				$notice[$val['notice_id']] = $val;
				
				$notice_id[] = $val['notice_id'];
			}
			
			$notice_recipient = $this->fetch_all('notice_recipient', 'notice_id IN (' . implode(',', $notice_id) . ') AND ((sender_uid = ' . intval($uid) . ' AND sender_del = 0) OR (recipient_uid = ' . $uid . ' AND recipient_del = 0))');
			
			foreach ($notice_recipient AS $key => $val)
			{
				if ($notice[$val['notic_id']])
				{
					$notice[$val['notic_id']] = array_merge($notice[$val['notice_id']], $val);
				}
			}
		}
		
		return $notice;
	}

	/**
	 * 删除对话信息
	 */
	public function delete_dialog($dialog_id, $uid)
	{
		// 更新短信息表
		$this->update('notice_dialog', array(
			'sender_count' => 0
		), 'sender_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id));
		
		$this->update('notice_dialog', array(
			'recipient_count' => 0
		), 'recipient_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id));
		
		// 更新接收表
		$this->update('notice_recipient', array(
			'recipient_del' => 1
		), 'recipient_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id));
		
		$this->update('notice_recipient', array(
			'sender_del' => 1
		), 'sender_uid = ' . intval($uid) . ' AND dialog_id = ' . intval($dialog_id));
		
		$this->model('account')->update_notic_unread($uid);
		
		return true;
	}

	/**
	 * 
	 * 列出用户相关短信息
	 * @param $cur_page 页码
	 * @param $page_size 页面条数
	 * 
	 * @return array
	 */
	public function list_message($page = 1, $limit = 10, $uid = null)
	{
		if ($list_msg = $this->fetch_page('notice_dialog', '(sender_uid = ' . intval($uid) . ' AND sender_count > 0) OR (recipient_uid = ' . intval($uid) . ' AND recipient_count > 0)', 'last_time DESC', $page, $limit))
		{
			foreach ($list_msg as $recordset)
			{
				$dialog_ids[] = $recordset['dialog_id'];

				if ($uid == $recordset['recipient_uid'])
				{
					$send_ids[] = $recordset['sender_uid'];
				}
				else
				{
					$send_ids[] = $recordset['recipient_uid'];
				}
			}
		}
		
		$result['diag_ids'] = $dialog_ids;
		$result['content_list'] = $list_msg;
		$result['user_list'] = $send_ids;
		
		return $result;
	}
	
	public function get_last_messages($dialog_ids)
	{
		if (!is_array($dialog_ids))
		{
			return false;
		}
				
		foreach ($dialog_ids as $dialog_id)
		{
			$dialog_message = $this->fetch_row('notice', 'dialog_id = ' . intval($dialog_id), 'notice_id DESC');
			
			$last_message[$dialog_id] = cjk_substr($dialog_message['notice_content'], 0, 60, 'UTF-8', '...');
		}
		
		return $last_message;
	}
	
	public function get_dialog($sender_uid, $recipient_uid)
	{
		return $this->fetch_row('notice_dialog', "(`sender_uid` = " . intval($sender_uid) . " AND `recipient_uid` = " . intval($recipient_uid) . ")  OR (`recipient_uid` = " . intval($sender_uid) . " AND `sender_uid` = " . intval($recipient_uid) . ")");
	}

	/**
	 * 判断用户是否设置了关注人才能接收
	 */
	public function check_recv($uid, $sender_uid)
	{
		$user_focus = $this->model('follow')->get_user_friends_ids($uid);
			
		if (! in_array($sender_uid, $user_focus))
		{
			return false;
		}
		
		return true;
	}
}