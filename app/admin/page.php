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

class page extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('页面管理'), "admin/page/");
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(308));
	}
	
	public function index_action()
	{
		TPL::assign('page_list', $this->model('page')->fetch_page_list($_GET['page'], 20));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/page/',
			'total_rows' => $this->model('page')->found_rows(),
			'per_page' => 20
		))->create_links());
		
		TPL::output('admin/page/list');
	}
	
	public function add_action()
	{
		$this->crumb(AWS_APP::lang()->_t('添加页面'), "admin/page/add/");
		
		TPL::output('admin/page/publish');
	}
	
	public function edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('编辑页面'), "admin/page/edit/");
		
		if (!$page_info = $this->model('page')->get_page_by_url_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('页面不存在'));
		}
		
		TPL::assign('page_info', $page_info);
		
		TPL::output('admin/page/publish');
	}
	
	public function page_add_action()
	{
		define('IN_AJAX', true);
		
		if (!$_POST['url_token'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入页面 URL')));
		}
		
		if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
		}
		
		if ($this->model('page')->get_page_by_url_token($_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的页面 URL')));
		}
		
		$this->model('page')->add_page($_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => G_INDEX_SCRIPT . 'admin/page/'
		), 1, null));
	}
	
	public function page_edit_action()
	{
		define('IN_AJAX', true);
		
		if (!$page_info = $this->model('page')->get_page_by_url_id($_POST['page_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面不存在')));
		}
		
		if (!$_POST['url_token'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入页面 URL')));
		}
		
		if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
		}
		
		if ($_page_info = $this->model('page')->get_page_by_url_token($_POST['url_token']))
		{
			if ($_page_info['id'] != $_page_info['id'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的页面 URL')));
			}
		}
		
		$this->model('page')->update_page($_POST['page_id'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => G_INDEX_SCRIPT . 'admin/page/'
		), 1, null));
	}
	
	public function save_enabled_status_action()
	{
		define('IN_AJAX', true);
		
		if ($_POST['page_ids'])
		{
			foreach ($_POST['page_ids'] AS $page_id => $val)
			{
				$this->model('page')->update_page_enabled($page_id, $_POST['enabled_status'][$page_id]);
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('启用状态已自动保存')));
	}
}