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

class category extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('分类设置'), "admin/category/list/");
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(302));
	}

	public function list_action()
	{
		TPL::assign('list', json_decode($this->model('system')->build_category_json('question'), true));
		
		TPL::assign('category_option', $this->model('system')->build_category_html('question', 0, 0, null, false));
		
		TPL::output('admin/category/list');
	}

	public function edit_action()
	{
		if (!$category_info = $this->model('system')->get_category_info($_GET['category_id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定分类不存在'));
		}
		
		TPL::assign('category', $category_info);
		TPL::assign('category_option', $this->model('system')->build_category_html($category_info['type'], 0, $category['parent_id'], null, false));
		
		TPL::output('admin/category/edit');
	}

	public function save_sort_action()
	{
		define('IN_AJAX', TRUE);
		
		if (is_array($_POST['category']))
		{
			foreach ($_POST['category'] as $key => $val)
			{
				$this->model('category')->update_category($key, array(
					'sort' => intval($val['sort'])
				));
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类排序已自动保存')));
	}
	
	public function save_action()
	{
		define('IN_AJAX', TRUE);
		
		if ($_GET['category_id'] AND $_POST['parent_id'] AND $category_list = $this->model('system')->fetch_category('question', $_GET['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统允许最多二级分类, 当前分类下有子分类, 不能移动到其它分类')));
		}
		
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入分类名称')));
		}
		
		if ($_POST['url_token'])
		{
			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名只允许输入英文或数字')));
			}
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_GET['category_id'] != $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名不可以全为数字')));
			}
	
			if ($this->model('category')->check_url_token($_POST['url_token'], $_GET['category_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
			}
		}
		
		if (! $_GET['category_id'])
		{
			$category_id = $this->model('category')->add_category('question', $_POST['title'], $_POST['parent_id']);
		}
		else
		{
			$category_id = intval($_GET['category_id']);
		}
		
		$category = $this->model('system')->get_category_info($category_id);
		
		if ($category['id'] == $_POST['parent_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
		}
		
		$update_data = array(
			'title' => $_POST['title'], 
			'parent_id' => $_POST['parent_id'],
			'url_token' => $_POST['url_token'],
		);
		
		$this->model('category')->update_category($category_id, $update_data);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/category/list/'
		), 1, null));
	}

	public function category_remove_action()
	{
		define('IN_AJAX', TRUE);
		
		if (intval($_POST['category_id']) == 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('默认分类不可删除')));
		}
		
		if ($this->model('category')->contents_exists($_POST['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
		}
		
		$this->model('category')->delete_category('question', $_POST['category_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function move_contents_action()
	{
		$this->crumb(AWS_APP::lang()->_t('批量移动'), "admin/category/list/");
		
		TPL::assign('from_category', $this->model('system')->build_category_html('question', 0, $_GET['category_id']));
		
		TPL::assign('target_category', $this->model('system')->build_category_html('question', 0, null));
		
		TPL::output('admin/category/move_contents');
	}
	
	public function move_contents_process_action()
	{
		if (!is_array($_POST['from_ids']) OR !$_POST['target_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
		}
		
		if (in_array($_POST['target_id'], $_POST['from_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
		}
		
		$this->model('category')->move_contents($_POST['from_ids'], $_POST['target_id']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/category/list/'
		), 1, null));
	}
}