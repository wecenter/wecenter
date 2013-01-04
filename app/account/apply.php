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

class apply extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
	
	}

	function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('申请帐户'), '/account/apply/');
		
		TPL::import_css('css/login.css');
		
		TPL::assign('r_uname', HTTP::get_cookie('r_uname'));
		
		TPL::assign('return_url', $_SERVER['HTTP_REFERER']);
		
		TPL::output("account/apply");
	}

	function apply_ajax_action()
	{
		if ($this->model('account')->check_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'email'
			), -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
		}
		
		$length = strlen(iconv('UTF-8', 'gb2312', $_POST['reason']));
		
		if ($length < 20)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'reason'
			), -1, AWS_APP::lang()->_t('申请理由必须大于 10 个字')));
		}
		
		file_put_contents(ROOT_PATH . 'apply_list.txt', $_POST['email'] . "\t||\t" . $_POST['reason'] . "\n", FILE_APPEND);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}