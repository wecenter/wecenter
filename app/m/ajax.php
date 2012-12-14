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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	var $per_page = 10;
	
	function setup()
	{
		HTTP::no_cache_header();
	}

	public function inbox_list_action()
	{
		$list = $this->model('message')->list_message($_GET['page'], $this->per_page, $this->user_id);
				
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
				if (($value['sender_uid'] == $this->user_id) && ($value['sender_count'] > 0)) //当前处于发送用户
				{
					$tmp['user_name'] = $users_info[$value['recipient_uid']]['user_name'];
					$tmp['url_token'] = $users_info[$value['recipient_uid']]['url_token'];
					
					$tmp['unread'] = $value['sender_unread'];
					$tmp['count'] = $value['sender_count'];
					$tmp['uid'] = $value['recipient_uid'];
				}
				else if (($value['recipient_uid'] == $this->user_id) && ($value['recipient_count'] > 0)) ////当前处于接收用户
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

		TPL::output("mobile/ajax/inbox_list");
	}
	
	public function notifications_list_action()
	{
		$list = $this->model('notify')->list_notification($this->user_id, $_GET['flag'], intval($_GET['page']) * $this->per_page . ', ' . $this->per_page);
		
		if (empty($list) && $this->user_info['notification_unread'] != 0)
		{
			$this->model('account')->increase_user_statistics(account_class::NOTIFICATION_UNREAD, 0, $this->user_id);
		}
		
		TPL::assign('flag', $_GET['flag']);
		
		TPL::assign('list', $list);
		
		TPL::output("mobile/ajax/notifications_list");
	}
	
	public function get_answer_comments_action()
	{
		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);
		
		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$user_info = $this->model('account')->get_user_info_by_uid($val['uid']);
			$comments[$key]['user_name'] = $user_info['user_name'];
		}
		
		$answer = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		
		TPL::assign('question', $this->model('question')->get_question_info_by_id($answer['question_id']));
		
		TPL::assign('comments', $comments);
		
		TPL::output("mobile/ajax/comments");
	}

	public function get_question_comments_action()
	{
		if ($comments = $this->model('question')->get_question_comments($_GET['question_id']))
		{
			foreach ($comments as $key => $val)
			{
				$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
				$user_info = $this->model('account')->get_user_info_by_uid($val['uid']);
				$comments[$key]['user_name'] = $user_info['user_name'];
			}
		}
		
		TPL::assign('question', $this->model('question')->get_question_info_by_id($_GET['question_id']));
		
		TPL::assign('comments', $comments);
		
		TPL::output("mobile/ajax/comments");
	}
	
	public function search_action()
	{
		$limit = intval($_GET['page']) * $this->per_page . ', ' . $this->per_page;
		
		$keyword = rawurldecode($_GET['q']);
		
		$search_result = $this->model('search')->search($keyword, 'all', $limit);
		
		foreach ($search_result AS $key => $val)
		{
			switch ($val['type'])
			{
				case 1:
					$search_result[$key]['focus'] = $this->model("question")->has_focus_question($val['sno'], $this->user_id);
					break;
		
				case 2:
					$search_result[$key]['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $val['sno']);
					break;
		
				case 3:
					$search_result[$key]['focus'] = $this->model('follow')->user_follow_check($this->user_id, $val['sno']);
					break;
			}
		}
		
		TPL::assign('search_result', $search_result);
		
		TPL::output("mobile/ajax/search_list");
	}
}