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

		$category_ids_map = array(
			1 => 2,
		);

		if ($category_ids_map[$_GET['category']] AND $_GET['category'])
		{
			$_GET['category_id'] = $category_ids_map[$_GET['category']];
		}
	}

	public function last_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_offical_external')->format_js_question_output($this->model('posts')->get_posts_list('question', 1, intval($_GET['limit']), 'new', explode(',', $_GET['topic_ids']), $_GET['category_id'], null, $_GET['day']));
	}

	public function hot_users_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}

		echo $this->model('aws_offical_external')->format_js_users_output($this->model('aws_offical_external')->k2_hot_users(0, $_GET['limit']));
	}
}