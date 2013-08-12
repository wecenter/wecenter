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
		$rule_action['rule_type'] = 'black';
		
		return $rule_action;
	}
	
	public function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function index_action()
	{
		$this->run();
	}
	
	public function run_action()
	{
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');             // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: no-cache, must-revalidate');           // HTTP/1.1
		header('Pragma: no-cache');                                   // HTTP/1.0
		
		@set_time_limit(0);
		
		if ($call_actions = $this->model('crond')->start())
		{
			foreach ($call_actions AS $call_action)
			{			
				if ($plugins = AWS_APP::plugins()->parse('crond', 'main', $call_action))
				{
					foreach ($plugins AS $plugin_file)
					{
						include($plugin_file);
					}
				}
			
				$call_function = $call_action;
			
				$this->$call_function($this->user_id);
			}
		}
		
		if (AWS_APP::config()->get('system')->debug)
		{
			TPL::output('global/debuger.tpl.htm');
		}
	}
	
	// 每秒执行
	public function second($user_id = null)
	{
		$this->model('crond')->second($user_id);
	}
	
	// 每半分钟执行
	public function half_minute($user_id = null)
	{
		$this->model('crond')->half_minute($user_id);
	}
	
	// 每分钟执行
	public function minute($user_id = null)
	{
		$this->model('crond')->minute($user_id);
	}
	
	// 每半小时执行
	public function half_hour($user_id = null)
	{
		$this->model('crond')->half_hour($user_id);
	}
	
	// 每小时执行
	public function hour($user_id = null)
	{
		$this->model('crond')->hour($user_id);
	}
	
	// 每日时执行
	public function day($user_id = null)
	{
		$this->model('crond')->day($user_id);
	}
	
	// 每周执行
	public function week($user_id = null)
	{
		$this->model('crond')->week($user_id);
	}
}