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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['guest'] = array();
		$rule_action['user'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('私信'), '/inbox/');
	}

	
	public function index_action()
	{
		$list = $this->model('message')->list_message($_GET['page'], get_setting('contents_per_page'), $this->user_id);
		
		$list_total_rows = $this->model('message')->found_rows();
				
		if ($list['user_list'])
		{
			if ($users_info_query = $this->model('account')->get_user_info_by_uids($list['user_list']))
			{
				foreach ($users_info_query as $user)
				{
					$users_info[$user['uid']] = $user;
				}
			}
		}
		
		if ($list['diag_ids'])
		{
			$last_message = $this->model('message')->get_last_messages($list['diag_ids']);
		}
				
		if ($list['content_list'])
		{
			$data = array();
			
			foreach ($list['content_list'] as $key => $value)
			{
				if ($value['sender_uid'] == $this->user_id AND $value['sender_count'] > 0) // 当前处于发送用户
				{
					$tmp['user_name'] = $users_info[$value['recipient_uid']]['user_name'];
					$tmp['url_token'] = $users_info[$value['recipient_uid']]['url_token'];
					
					$tmp['unread'] = $value['sender_unread'];
					$tmp['count'] = $value['sender_count'];
					$tmp['uid'] = $value['recipient_uid'];
				}
				else if ($value['recipient_uid'] == $this->user_id AND $value['recipient_count'] > 0) // 当前处于接收用户
				{
					$tmp['user_name'] = $users_info[$value['sender_uid']]['user_name'];
					$tmp['url_token'] = $users_info[$value['sender_uid']]['url_token'];
					
					$tmp['unread'] = $value['recipient_unread'];
					$tmp['count'] = $value['recipient_count'];
					
					$tmp['uid'] = $value['sender_uid'];
				}
				
				$tmp['last_message'] = $last_message[$value['dialog_id']];
				
				$tmp['last_time'] = $value['last_time'];
				$tmp['dialog_id'] = $value['dialog_id'];
				
				$data[] = $tmp;
			}
		}
		
		TPL::assign('list', $data);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/inbox/'), 
			'total_rows' => $list_total_rows,
			'per_page' => get_setting('contents_per_page')
		))->create_links());
		
		TPL::assign('list', $data);
		
		TPL::output("inbox/index");
	}
	
	public function delete_dialog_action()
	{
		$this->model('message')->delete_dialog($_GET['dialog_id'], $this->user_id);
		
		if ($_SERVER['HTTP_REFERER'])
		{
			HTTP::redirect($_SERVER['HTTP_REFERER']);
		}
		else
		{
			HTTP::redirect('/inbox/');
		}
	}

	public function read_action()
	{		
		if (!$dialog = $this->model('message')->get_dialog_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/inbox/');
		}
		
		$this->model('message')->read_message($_GET['id'], $this->user_id);
		
		if ($list = $this->model('message')->get_message_by_dialog_id($_GET['id'], $this->user_id))
		{
			if ($dialog['sender_uid'] != $this->user_id)
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['sender_uid']);
			}
			else
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['recipient_uid']);
			}
			
			foreach ($list as $key => $value)
			{
				$value['notice_content'] = FORMAT::parse_links($value['notice_content']);
				
				$value['user_name'] = $recipient_user['user_name'];
				$value['url_token'] = $recipient_user['url_token'];
					
				$list_data[] = $value;
			}
		}
		
		$this->crumb(AWS_APP::lang()->_t('私信对话') . ': ' . $recipient_user['user_name'], '/inbox/read/' . intval($_GET['id']));
		
		TPL::assign('list', $list_data);
		TPL::assign('recipient_user', $recipient_user);
		
		TPL::output("inbox/read");
	}
}
