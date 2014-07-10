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
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array(
			'explore'
		);
		
		if ($this->user_info['permission']['visit_explore'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
		}
		
		return $rule_action;
	}

	public function setup()
	{
		if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE' AND !$_GET['ignore_ua_check'])
		{
			switch ($_GET['app'])
			{
				default:
					HTTP::redirect('/m/');
				break;
			}
		}
		
		if ($_GET['ignore_ua_check'] == 'TRUE')
		{
			HTTP::set_cookie('_ignore_ua_check', 'TRUE', (time() + 3600 * 24 * 7));
		}
	}
	
	public function index_action()
	{		
		if (! $this->user_id)
		{
			HTTP::redirect('/explore/');
		}
		
		if (! $this->user_info['email'])
		{
			HTTP::redirect('/account/complete_profile/');
		}
		
		// 边栏可能感兴趣的人或话题
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'home/index'))
		{
			$recommend_users_topics = $this->model('module')->recommend_users_topics($this->user_id);
			
			TPL::assign('sidebar_recommend_users_topics', $recommend_users_topics);
		}
		
		// 边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'home/index'))
		{
			$sidebar_hot_users = $this->model('module')->sidebar_hot_users($this->user_id);
			
			TPL::assign('sidebar_hot_users', $sidebar_hot_users);
		}
		
		$this->crumb(AWS_APP::lang()->_t('首页'), '/');
		
		TPL::import_js('js/app/index.js');
		
		if ($_GET['first_login'])
		{
			TPL::import_js('js/ajaxupload.js');
		}
		
		TPL::output('home/index');
	}

	public function explore_action()
	{
		HTTP::redirect('/explore/');
	}
}