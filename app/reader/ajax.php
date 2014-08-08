<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
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
		$rule_action['rule_type'] = 'black';

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function questions_list_action()
	{
		if ($_GET['feature_id'])
		{
			$topic_ids = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']);

			if ($topic_ids)
			{
				$answers = $this->model('reader')->fetch_answers_list_by_topic_ids($topic_ids, $_GET['page'], 20);
			}
		}
		else
		{
			$answers = $this->model('reader')->fetch_answers_list($_GET['page'], 20);
		}

		$output = array();

		if ($answers)
		{
			foreach ($answers AS $key => $val)
			{
				$question_ids[$val['question_id']] = $val['question_id'];
				$uids[$val['uid']] = $val['uid'];
			}

			$questions_info = $this->model('question')->get_question_info_by_ids($question_ids);

			$question_topics = $this->model('topic')->get_topics_by_item_ids($question_ids, 'question');

			$users_info = $this->model('account')->get_user_info_by_uids($uids, TRUE);

			foreach ($answers AS $key => $val)
			{
				$output['answers'][$val['answer_id']] = array(
					'answer_id' => $val['answer_id'],
					'question_id' => $val['question_id'],
					'avatar' => get_avatar_url($val['uid'], 'mid'),
					'user_name' => $users_info[$val['uid']]['user_name'],
					'signature' => $users_info[$val['uid']]['signature'],
					'agree_count' => $val['agree_count'],
					'agree_users' => $this->model('answer')->get_vote_user_by_answer_id($val['answer_id']),
					'answer_content' => FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($val['answer_content']))),
					'add_time' => date_friendly($val['add_time']),
					'uid' => $val['uid'],
				);
			}

			foreach ($questions_info AS $key => $val)
			{
				$output['questions'][$val['question_id']] = array(
					'question_id' => $val['question_id'],
					'question_content' => $val['question_content'],
					'question_detail' => FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($val['question_detail']))),
					'answer_users' => $val['answer_users'],
					'focus_count' => $val['focus_count'],
					'view_count' => $val['view_count'],
					'topics' => $question_topics[$val['question_id']]
				);
			}
		}

		echo json_encode($output);
	}
}