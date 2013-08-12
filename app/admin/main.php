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

class main extends AWS_ADMIN_CONTROLLER
{
	public function index_action()
	{
		if (!defined('IN_SAE'))
		{
			$writable_check = array(
				'cache' => is_really_writable(ROOT_PATH . 'cache/'), 
				'tmp' => is_really_writable(ROOT_PATH . './tmp/'), 
				get_setting('upload_dir') => is_really_writable(get_setting('upload_dir'))
			);
			
			TPL::assign('writable_check', $writable_check);
		}
		
		TPL::assign('users_count', $this->model('system')->count('users'));
		TPL::assign('users_valid_email_count', $this->model('system')->count('users', 'valid_email = 1'));
		TPL::assign('question_count', $this->model('system')->count('question'));
		TPL::assign('answer_count', $this->model('system')->count('answer'));
		TPL::assign('question_count', $this->model('system')->count('question'));
		TPL::assign('question_no_answer_count', $this->model('system')->count('question', 'answer_count = 0'));
		TPL::assign('best_answer_count', $this->model('system')->count('question', 'best_answer > 0'));
		TPL::assign('topic_count', $this->model('system')->count('topic'));
		TPL::assign('attach_count', $this->model('system')->count('attach'));
		TPL::assign('approval_question_count', $this->model('publish')->count('approval', "type = 'question'"));
		TPL::assign('approval_answer_count', $this->model('publish')->count('approval', "type = 'answer'"));
		
		$this->crumb(AWS_APP::lang()->_t('管理中心首页'), "admin/main/");
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(100));
		
		TPL::import_js('js/chart.js');
		
		TPL::assign('statistic_questions', $this->model('statistic')->get_new_question_by_month(strtotime('-12 months'), time()));
		TPL::assign('statistic_answers', $this->model('statistic')->get_new_answer_by_month(strtotime('-12 months'), time()));
		
		TPL::output("admin/index");
	}

	public function login_action()
	{
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
		}
		else if (AWS_APP::session()->admin_login)
		{
			HTTP::redirect(get_setting('base_url') . '/?/admin/');
		}
		
		TPL::output("admin/login");
	}
	
	public function login_process_ajax_action()
	{
		define('IN_AJAX', TRUE);
				
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
		}
		
		if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请填写正确的验证码'));
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (! $user_info = $this->model('ucenter')->login($_POST['username'], $_POST['password']))
			{
				$user_info = $this->model('account')->check_login($_POST['username'], $_POST['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($_POST['username'], $_POST['password']);
		}
		
		if ($user_info['uid'])
		{
			$this->model('admin')->set_admin_login($user_info['uid']);
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('?/admin/')
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帐号或密码错误')));
		}
	}
	
	function logout_action($return_url = '/')
	{
		$this->model('admin')->admin_logout();
		
		HTTP::redirect($return_url);
	}
}