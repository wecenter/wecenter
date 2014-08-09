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


if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function index_action()
	{
		if (!$page_info = $this->model('page')->get_page_by_url_token($_GET['id']) OR $page_info['enabled'] == 0)
		{
			HTTP::error_404();
		}

		if ($page_info['title'])
		{
			TPL::assign('page_title', $page_info['title']);
		}

		if ($page_info['keywords'])
		{
			TPL::set_meta('keywords', $page_info['keywords']);
		}

		if ($page_info['description'])
		{
			TPL::set_meta('description', $page_info['description']);
		}

		TPL::assign('page_info', $page_info);

		TPL::output('page/index');
	}
}