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

class main extends AWS_CONTROLLER
{
	function get_permission_action()
	{
	
	}

	public function setup()
	{
		if(!strstr($_SERVER['REQUEST_URI'], G_INDEX_SCRIPT))
		{
			HTTP::redirect(get_setting('base_url') . "/?/admin/");
		}
		
		$this->model('admin_session')->init($this->get_permission_action());
	}

	public function index_action()
	{
		$writable_check = array(
			'./cache' => is_really_writable(realpath('./cache')), 
			'./tmp' => is_really_writable(realpath('./tmp')), 
			get_setting('upload_dir') => is_really_writable(get_setting('upload_dir'))
		);
		
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
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 100));
		
		TPL::output("admin/index");
	}

	public function login_action()
	{
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
		}
		else if (admin_session_class::get_admin_uid() == $this->user_id)
		{
			HTTP::redirect("?/admin/");
		}
		
		TPL::output("admin/login");
	}

	/**
	 * 登录处理
	 */
	public function login_process_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if ((get_setting('admin_login_seccode') == 'Y') && ! core_captcha::validate($_POST['seccode_verify'], false))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'seccode_verify'
			), "-1", "请填写正确的验证码"));
		}
		
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
		}
		
		$user_name = trim($_POST['username']);
		
		$password = $_POST['password'];
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (! $user_info = $this->model('ucenter')->login($user_name, $password))
			{
				$user_info = $this->model('account')->check_login($user_name, $password);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($user_name, $password);
		}
		
		if ($user_info)
		{
			$this->model('account')->admin_logout();
			
			$this->model('account')->set_admin_login($user_info['uid']);
			
			$url = $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('?/admin/');
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('帐号或密码错误')));
		}
	}

	/**
	 * 退出
	 */
	function logout_action($return_url = '/')
	{
		$this->model('account')->admin_logout();
		
		HTTP::redirect($return_url);
	}
}