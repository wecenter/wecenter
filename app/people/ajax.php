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

	public function setup()
	{
		$this->per_page = get_setting('contents_per_page');

		HTTP::no_cache_header();
	}

	public function user_actions_action()
	{
		if ((isset($_GET['perpage']) AND intval($_GET['perpage']) > 0))
		{
			$this->per_page = intval($_GET['perpage']);
		}

		$data = $this->model('actions')->get_user_actions($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", $_GET['actions'], $this->user_id);

		TPL::assign('list', $data);

		if (is_mobile())
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

	public function user_info_action()
	{
		if ($this->user_id == $_GET['uid'])
		{
			$user_info = $this->user_info;
		}
		else if (!$user_info = $this->model('account')->get_user_info_by_uid($_GET['uid'], ture))
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}

		if ($this->user_id != $user_info['uid'])
		{
			$user_follow_check = $this->model('follow')->user_follow_check($this->user_id, $user_info['uid']);
		}

		H::ajax_json_output(array(
			'reputation' => $user_info['reputation'],
			'agree_count' => $user_info['agree_count'],
			'thanks_count' => $user_info['thanks_count'],
			'type' => 'people',
			'uid' => $user_info['uid'],
			'user_name' => $user_info['user_name'],
			'avatar_file' => get_avatar_url($user_info['uid'], 'mid'),
			'signature' => $user_info['signature'],
			'focus' => ($user_follow_check ? true : false),
			'is_me' => (($this->user_id == $user_info['uid']) ? true : false),
			'url' => get_js_url('/people/' . $user_info['url_token']),
			'category_enable' => ((get_setting('category_enable') == 'Y') ? 1 : 0),
			'verified' => $user_info['verified'],
			'fans_count' => $user_info['fans_count']
		));
	}

	public function follows_action()
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
				$users_ids[] = $val['uid'];
			}

			if ($users_ids)
			{
				$follow_checks = $this->model('follow')->users_follow_check($this->user_id, $users_ids);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['follow_check'] = $follow_checks[$val['uid']];
				}
			}
		}

		TPL::assign('users_list', $users_list);

		TPL::output('people/ajax/follows');
	}

	public function topics_action()
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