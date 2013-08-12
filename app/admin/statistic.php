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

class statistic extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('数据统计'), 'admin/statistic/');
		
		if (!$_POST['start_time'])
		{
			$_POST['start_time'] = date('Y-m-d', strtotime('-6 months'));
		}
		
		if (!$_POST['end_time'])
		{
			$_POST['end_time'] = date('Y-m-d', strtotime('Today'));
		}
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(502));
		
		TPL::import_js('js/chart.js');
	}

	public function index_action()
	{
		$this->register_action();
	}
	
	public function register_action()
	{
		$statistic_list = $this->model('statistic')->get_user_register_list_by_month(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('account')->count('users'));
		TPL::assign('valid_count', $this->model('account')->count('users', 'valid_email = 1'));
		TPL::assign('today_count', $this->model('account')->count('users', 'reg_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('account')->count('users', 'reg_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('account')->count('users', 'reg_time > ' . strtotime(date('Y-m'))));
		
		TPL::output('admin/statistic');
	}
	
	public function question_action()
	{
		$statistic_list = $this->model('statistic')->get_new_question_by_month(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		
		TPL::assign('total_count', $this->model('question')->count('question'));
		TPL::assign('question_with_answer_count', $this->model('question')->count('question', 'answer_count > 0'));
		TPL::assign('today_count', $this->model('question')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('question')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('question')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::output('admin/statistic');
	}
	
	public function answer_action()
	{
		$statistic_list = $this->model('statistic')->get_new_answer_by_month(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('question')->count('answer'));
		TPL::assign('today_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('answer')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::output('admin/statistic');
	}
	
	public function topic_action()
	{
		$statistic_list = $this->model('statistic')->get_new_topic_by_month(strtotime($_POST['start_time']), strtotime($_POST['end_time']));
		
		TPL::assign('statistic_list', $statistic_list);
		TPL::assign('total_count', $this->model('question')->count('answer'));
		TPL::assign('today_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m-d'))));
		TPL::assign('week_count', $this->model('answer')->count('question', 'add_time > ' . strtotime('last Monday')));
		TPL::assign('month_count', $this->model('answer')->count('question', 'add_time > ' . strtotime(date('Y-m'))));
		
		TPL::output('admin/statistic');
	}
}