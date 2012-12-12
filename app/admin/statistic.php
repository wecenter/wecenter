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


if (!defined('IN_ANWSION'))
{
	die;
}

class statistic extends AWS_CONTROLLER
{
	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
		
		$this->crumb(AWS_APP::lang()->_t('数据统计'), 'admin/statistic/');
		
		TPL::import_css('admin/js/amcharts/style.css');
		TPL::import_js('admin/js/amcharts/amcharts.js');
		
		if (!$_POST['start_time'])
		{
			$_POST['start_time'] = date('Y-m-d', strtotime('Last week'));
		}
		
		if (!$_POST['end_time'])
		{
			$_POST['end_time'] = date('Y-m-d', strtotime('Today'));
		}
	}

	public function index_action()
	{
		$this->register_action();
	}
	
	public function register_action()
	{
		$statistic_list = $this->model('statistic')->get_user_register_list_by_day(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('account')->count('users'));
		TPL::assign('valid_count', $this->model('account')->count('users', 'valid_email = 1'));
		TPL::assign('today_count', $this->model('account')->count('users', 'reg_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('account')->count('users', 'reg_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('account')->count('users', 'reg_time > ' . strtotime(date('Y-m'))));
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 602));
		
		TPL::output('admin/statistic');
	}
	
	public function question_action()
	{
		$statistic_list = $this->model('statistic')->get_new_question_by_day(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('question')->count('question'));
		TPL::assign('question_with_answer_count', $this->model('question')->count('question', 'answer_count > 0'));
		TPL::assign('today_count', $this->model('question')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('question')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('question')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 602));
		
		TPL::output('admin/statistic');
	}
	
	public function answer_action()
	{
		$statistic_list = $this->model('statistic')->get_new_answer_by_day(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('question')->count('answer'));
		TPL::assign('today_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('answer')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 602));
		
		TPL::output('admin/statistic');
	}
	
	public function topic_action()
	{
		$statistic_list = $this->model('statistic')->get_new_topic_by_day(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('question')->count('answer'));
		TPL::assign('today_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('answer')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 602));
		
		TPL::output('admin/statistic');
	}
}