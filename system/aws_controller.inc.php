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

class AWS_CONTROLLER
{
	public $user_id;
	public $user_info;
	
	public function __construct()
	{		
		$this->user_id = USER::get_client_uid();
		
		$this->user_info = $this->model('account')->get_user_info_by_uid($this->user_id, TRUE);
		
		if ($this->user_id)
		{
			$user_group = $this->model('account')->get_user_group($this->user_info['group_id'], $this->user_info['reputation_group']);
			
			if ($this->user_info['default_timezone'])
			{
				date_default_timezone_set($this->user_info['default_timezone']);
			}
		}
		else
		{
			$user_group = $this->model('account')->get_group_by_id(99);
		}
		
		$this->user_info['group_name'] = $user_group['group_name'];
		$this->user_info['permission'] = $user_group['permission'];
		
		AWS_APP::session()->permission = $this->user_info['permission'];
		
		if ($this->user_info['forbidden'] == 1)
		{
			$this->model('account')->logout();
			
			H::redirect_msg(AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录'), '/');
		}
		else
		{
			TPL::assign('user_id', (int)$this->user_id);
			TPL::assign('user_info', $this->user_info);
		}
		
		if ($this->user_id and ! $this->user_info['permission']['human_valid'])
		{
			unset(AWS_APP::session()->human_valid);
		}
		else if ($this->user_info['permission']['human_valid'] and ! is_array(AWS_APP::session()->human_valid))
		{
			AWS_APP::session()->human_valid = array();
		}
		
		TPL::import_css(array(
			'css/common.css',
			'css/btn-sprite.css',
			'css/icon-sprite.css',
			'js/plug_module/style.css', 
		));
		
		if (defined('SYSTEM_LANG'))
		{
			TPL::import_js(get_setting('base_url') . '/language/' . SYSTEM_LANG . '.js');
		}
		
		TPL::import_js(array(
			'js/jquery.js',
			'js/jquery.form.js',
			'js/bootstrap.js',
			'js/functions.js',
			'js/aw_template.js',
			'js/hogan.js',
			'js/plug_module/plug-in_module.js',
			'js/common.js',
			'js/global.js',
			'js/app.js',
		));
		
		$this->crumb(get_setting('site_name'), get_setting('base_url'));
		
		if ($plugins = AWS_APP::plugins()->parse($_GET['app'], $_GET['c'], 'setup'))
		{
			foreach ($plugins as $plugin_file)
			{
				include ($plugin_file);
			}
		}
		
		if (get_setting('site_close') == 'Y' AND $this->user_info['group_id'] != 1 AND !in_array($_GET['app'], array('account', 'upgrade')))
		{
			$this->model('account')->logout();
			
			H::redirect_msg(get_setting('close_notice'), '/account/login/');
		}
		
		$this->setup();
	}

	public function setup() {}

	public function is_post()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			return TRUE;
		}
		
		return FALSE;
	}

	public function model($model)
	{
		return AWS_APP::model($model);
	}

	public function crumb($name, $url = null)
	{
		$this->_crumb(htmlspecialchars_decode($name), $url);
	}

	public function _crumb($name, $url = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $value)
			{
				$this->crumb($key, $value);
			}
			
			return $this;
		}
		
		$crumb_template = $this->crumb;
		
		if (strlen($url) > 1 and substr($url, 0, 1) == '/')
		{
			$url = get_setting('base_url') . substr($url, 1);
		}
		
		$this->crumb[] = array(
			'name' => $name, 
			'url' => $url
		);
		
		$crumb_template['last'] = array(
			'name' => $name, 
			'url' => $url
		);
		
		TPL::assign('crumb', $crumb_template);
		
		foreach ($this->crumb as $key => $crumb)
		{
			$title = $crumb['name'] . ' - ' . $title;
		}
		
		TPL::assign('page_title', htmlspecialchars(rtrim($title, ' - ')));
		
		return $this;
	}
	
	public function publish_approval_valid()
	{
		if ($this->user_info['permission']['publish_approval'] == 1)
		{
			if (!$this->user_info['permission']['publish_approval_time']['start'] AND !$this->user_info['permission']['publish_approval_time']['end'])
			{
				return true;
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] < $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') > $this->user_info['permission']['publish_approval_time']['start'] AND date('H') < $this->user_info['permission']['publish_approval_time']['end'])
				{
					return true;
				}
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] > $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') > $this->user_info['permission']['publish_approval_time']['start'] OR date('H') < $this->user_info['permission']['publish_approval_time']['end'])
				{
					return true;
				}
			}
			
			if ($this->user_info['permission']['publish_approval_time']['start'] == $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (date('H') == $this->user_info['permission']['publish_approval_time']['start'])
				{
					return true;
				}
			}
		}
		
		return false;
	}
}