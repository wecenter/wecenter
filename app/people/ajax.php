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

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		$rule_action['actions'] = array(
			'user_info'
		);
		
		if ($this->user_info['permission']['visit_people'])
		{
			$rule_action['actions'][] = 'user_actions';
			$rule_action['actions'][] = 'follows';
			$rule_action['actions'][] = 'topics';
		}
		
		return $rule_action;
	}

	function setup()
	{
		$this->per_page = get_setting('contents_per_page');
		
		HTTP::no_cache_header();
	}

	function user_actions_action()
	{
		if ((isset($_GET['perpage']) && (intval($_GET['perpage']) > 0)))
		{
			$this->per_page = intval($_GET['perpage']);
		}
		
		$data = $this->model('account')->get_user_actions($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", $_GET['actions'], $this->user_id, !$_GET['distint']);
		
		TPL::assign('list', $data);
		
		if ($_GET['template'] == 'm')
		{
			$template_dir = 'm';
		}
		else
		{
			$template_dir = 'people';
		}
		
		if ($_GET['actions'] == '201')
		{
			TPL::output($template_dir . '/ajax/user_actions_questions_201');
		}
		else if ($_GET['actions'] == '101')
		{
			TPL::output($template_dir . '/ajax/user_actions_questions_101');
		}
		else
		{
			TPL::output($template_dir . '/ajax/user_actions');
		}
	}
	
	function user_info_action()
	{
		if (!$_GET['uid'])
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}
		
		if (!$user = $this->model('account')->get_user_info_by_uid($_GET['uid'], ture))
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}
		
		if ($this->user_id)
		{
			$user_follow_check = $this->model('follow')->user_follow_check($this->user_id, $user['uid']);
		}

		$user_info['reputation'] = intval($user['reputation']);
		$user_info['agree_count'] = intval($user['agree_count']);
		$user_info['thanks_count'] = intval($user['thanks_count']);
		$user_info['type'] = 'people';
		$user_info['uid'] = $user['uid'];
		$user_info['user_name'] = $user['user_name'];
		$user_info['avatar_file'] = get_avatar_url($user['uid'], 'mid');
		$user_info['signature'] = $user['signature'];
		$user_info['focus'] = $user_follow_check ? true : false;
		$user_info['is_me'] = ($this->user_id == $user['uid']) ? true : false;
		$user_info['url'] = get_js_url('/people/' . $user['url_token']);
		$user_info['category_enable'] = (get_setting('category_enable') == 'Y') ? 1 : 0;
		$user_info['verified'] = $user['verified'];
		
		H::ajax_json_output($user_info);
	}
	
	function follows_action()
	{
		switch ($_GET['type'])
		{
			case 'follows':
				$users_list = $this->model('follow')->get_user_friends($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;
			
			case 'fans':
				$users_list = $this->model('follow')->get_user_fans($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;
		}
		
		if ($users_list AND $this->user_id)
		{
			foreach ($users_list as $key => $val)
			{
				switch ($_GET['type'])
				{
					case 'follows':
						$users_ids[] = $val['friend_uid'];
					break;
					
					case 'fans':
						$users_ids[] = $val['fans_uid'];
					break;
				}
			}
			
			if ($users_ids)
			{
				$follow_checks = $this->model('follow')->users_follow_check($this->user_id, $users_ids);
					
				foreach ($users_list as $key => $val)
				{
					switch ($_GET['type'])
					{
						case 'follows':
							$users_list[$key]['follow_check'] = $follow_checks[$val['friend_uid']];
						break;
						
						case 'fans':
							$users_list[$key]['follow_check'] = $follow_checks[$val['fans_uid']];
						break;
					}
				}
			}
		}
		
		TPL::assign('users_list', $users_list);
		
		TPL::output('people/ajax/follows');
	}

	function topics_action()
	{
		if ($topic_list = $this->model('topic')->get_focus_topic_list($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}") AND $this->user_id)
		{
			$topic_ids = array();
			
			foreach ($topic_list as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}
			
			if ($topic_ids)
			{
				$topic_focus = $this->model('topic')->has_focus_topics($this->user_id, $topic_ids);
				
				foreach ($topic_list as $key => $val)
				{
					$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']];
				}
			}
		}
		
		TPL::assign('topic_list', $topic_list);
		
		TPL::output('people/ajax/topics');
	}
}