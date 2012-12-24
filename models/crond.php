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

class crond_class extends AWS_MODEL
{
	public function start()
	{
		$cornd_db_file = TEMP_PATH . 'cornd_db.php';
	
		if (file_exists($cornd_db_file))
		{
			$cornd_db = unserialize(file_get_contents($cornd_db_file));
		}
		
		if ($cornd_db['second'] < time() - 1)
		{
			$call_actions[] = 'second';
			
			$cornd_db['second'] = time();
		}
		
		if ($cornd_db['half_minute'] < time() - 30)
		{
			$call_actions[] = 'half_minute';
			
			$cornd_db['half_minute'] = time();
		}
		
		if ($cornd_db['minute'] < time() - 60)
		{
			$call_actions[] = 'minute';
			
			$cornd_db['minute'] = time();
		}
		
		if (date('YW', $cornd_db['week']) != date('YW', time()))
		{
			$call_actions[] = 'week';
			
			$cornd_db['week'] = time();
		}
		else if (date('Y-m-d', $cornd_db['day']) != date('Y-m-d', time()))
		{
			$call_actions[] = 'day';
			
			$cornd_db['day'] = time();
		}
		else if ($cornd_db['hour'] < time() - 3600)
		{
			$call_actions[] = 'hour';
			
			$cornd_db['hour'] = time();
		}
		else if ($cornd_db['half_hour'] < time() - 1800)
		{
			$call_actions[] = 'half_hour';
			
			$cornd_db['half_hour'] = time();
		}
		
		if ($call_actions)
		{
			file_put_contents($cornd_db_file, serialize($cornd_db));
		}
		
		return $call_actions;
	}
	
	// 每秒执行
	public function second($user_id)
	{
		
	}
	
	// 每半分钟执行
	public function half_minute($user_id)
	{
		$this->model('edm')->run_task();
	}
	
	// 每分钟执行
	public function minute($user_id)
	{
		@unlink(TEMP_PATH . 'plugins_table.php');
		@unlink(TEMP_PATH . 'plugins_model.php');
		
		$this->model('online')->online_active($user_id);
		$this->model('reputation')->calculate_by_uid($user_id);
	}
	
	// 每半小时执行
	public function half_hour($user_id)
	{
		
	}
	
	// 每小时执行
	public function hour($user_id)
	{
		$this->model('cache')->clean_expire();
		$this->model('system')->clean_session();
	}
	
	// 每日时执行
	public function day($user_id)
	{
		$this->model('answer')->calc_best_answer();
		$this->model('question')->auto_lock_question();
	}
	
	// 每周执行
	public function week($user_id)
	{
		$this->model('system')->clean_break_attach();
	}
}