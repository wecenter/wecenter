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
		
		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('question')->get_questions_list(1, intval($_GET['limit']), 'new', $_GET['topic_ids'], $_GET['category_id'], null, $_GET['day']));
	}
	
	public function hot_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}
		
		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('question')->get_questions_list(1, intval($_GET['limit']), 'hot', $_GET['topic_ids'], $_GET['category_id'], null, $_GET['day']));
	}
	
	public function unresponsive_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}
		
		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('question')->get_questions_list(1, intval($_GET['limit']), 'unresponsive', $_GET['topic_ids'], $_GET['category_id'], null, $_GET['day']));
	}
	
	public function related_questions_action()
	{
		if (!$_GET['limit'] OR $_GET['limit'] > 100)
		{
			$_GET['limit'] = 10;
		}
		
		echo $this->model('aws_external')->format_js_question_ul_output($_GET['ul_class'], $this->model('search')->search_questions($_GET['q'], $_GET['topic_ids'], $_GET['limit']));
	}
}