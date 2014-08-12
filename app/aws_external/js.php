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

class js extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function last_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('posts')->get_posts_list('question', 1, intval($_GET['limit']), 'new', explode(',', $_GET['topic_ids']), $_GET['category_id'], null, $_GET['day'], $_GET['is_recommend']));
	}

	public function hot_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('posts')->get_hot_posts('question', $_GET['category_id'], explode(',', $_GET['topic_ids']), $_GET['day'], 1, $_GET['limit']));

	}

	public function unresponsive_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('posts')->get_posts_list('question', 1, intval($_GET['limit']), 'new', explode(',', $_GET['topic_ids']), $_GET['category_id'], '0', $_GET['day'], $_GET['is_recommend']));
	}

	public function related_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('search')->search_questions($_GET['q'], $_GET['topic_ids'], 1, $_GET['limit']));
	}

	public function new_users_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_users_ul_output($_GET['ul_class'], $this->model('account')->get_users_list(null, $_GET['limit'], true, false, 'uid DESC'));
	}

	public function hot_users_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_users_ul_output($_GET['ul_class'], $this->model('account')->get_users_list(null, $_GET['limit'], true, false, 'answer_count DESC'));
	}

	public function new_topics_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_external')->format_js_topics_ul_output($_GET['ul_class'], $this->model('topic')->get_topic_list(null, 'topic_id DESC', $_GET['limit']));
	}

	public function hot_topics_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		if ($hot_topics = $this->model('topic')->get_hot_topics($_GET['category_id'], $_GET['limit']))
		{
			echo $this->model('aws_external')->format_js_topics_ul_output($_GET['ul_class'], $hot_topics);
		}
	}
}