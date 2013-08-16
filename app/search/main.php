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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white";
		
		if ($this->user_info['permission']['search_avail'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['rule_type'] = "black"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		}
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{
		HTTP::no_cache_header();
		
		$this->crumb(AWS_APP::lang()->_t('搜索'), '/search/');
	}
	
	public function index_action()
	{
		if ($_POST['q'])
		{
			HTTP::redirect('/search/q-' . base64_encode($_POST['q']));
		}
		
		$keyword = htmlspecialchars(base64_decode($_GET['q']));
		
		$this->crumb($keyword, '/search/q-' . urlencode($keyword));
		
		if (!$keyword)
		{
			HTTP::redirect('/');	
		}
		
		TPL::assign('keyword', $keyword);
		TPL::assign('split_keyword', implode(' ', $this->model('system')->analysis_keyword($keyword)));
		
		TPL::output('search/index');
	}
}