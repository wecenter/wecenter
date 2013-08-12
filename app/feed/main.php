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

	function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		header('Content-type: text/xml; charset=UTF-8');
		
		date_default_timezone_set('UTC');
	}

	public function index_action()
	{
		if ($_GET['topic'])
		{
			$list = $this->model('question')->get_question_list_by_topic_ids($_GET['topic'], 1, 20);
		}
		else
		{
			$list = $this->model('question')->get_questions_list(1, 20, 'new', null, $_GET['category']);
		}
		
		TPL::assign('list', $list);
		
		TPL::output('global/feed');
	}
}