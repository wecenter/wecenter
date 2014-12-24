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
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['search_avail'])
		{
			$rule_action['rule_type'] = 'black'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		}

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function search_result_action()
	{
		if (!in_array($_GET['search_type'], array('questions', 'topics', 'users', 'articles')))
		{
			$_GET['search_type'] = null;
		}

		$search_result = $this->model('search')->search($_GET['q'], $_GET['search_type'], $_GET['page'], get_setting('contents_per_page'), null, $_GET['is_recommend']);

		if ($this->user_id AND $search_result)
		{
			foreach ($search_result AS $key => $val)
			{
				switch ($val['type'])
				{
					case 'questions':
						$search_result[$key]['focus'] = $this->model('question')->has_focus_question($val['search_id'], $this->user_id);

						break;

					case 'topics':
						$search_result[$key]['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $val['search_id']);

						break;

					case 'users':
						$search_result[$key]['focus'] = $this->model('follow')->user_follow_check($this->user_id, $val['search_id']);

						break;
				}
			}
		}

		TPL::assign('search_result', $search_result);

		if (is_mobile())
		{
			TPL::output('m/ajax/search_result');
		}
		else
		{
			TPL::output('search/ajax/search_result');
		}
	}

	public function search_action()
	{
		$result = $this->model('search')->search($_GET['q'], $_GET['type'], 1, $_GET['limit'], $_GET['topic_ids'], $_GET['is_recommend']);

		if (!$result)
		{
			$result = array();
		}

		if ($_GET['is_question_id'] AND is_digits($_GET['q']))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['q']);

			if ($question_info)
			{
				$result[] = $this->model('search')->prase_result_info($question_info);
			}
		}

		H::ajax_json_output($result);
	}
}