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
	
	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function answers_list_action()
	{
		if (!$answer_list = $this->model('answer')->get_answer_list_by_question_id($_GET['question_id'], calc_page_limit($_GET['page'], $this->per_page), null, "agree_count DESC, against_count ASC, add_time ASC"))
		{
			$answer_list = array();
		}
			
		foreach ($answer_list as $key => $answer)
		{
			if ($answer['has_attach'])
			{
				$answer_list[$key]['attachs'] = $this->model('publish')->get_attach('answer', $answer['answer_id'], 'min');
			}
				
			if ($answer['answer_content'])
			{
				$answer_list[$key]['answer_content'] = FORMAT::parse_links(nl2br($answer['answer_content']));
			}
		}
		
		TPL::assign('answers_list', $answer_list);
		
		TPL::output('m/ajax/answers_list');
	}
}