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
		TPL::assign('category_option', $this->model('system')->build_category_html('question'));
		
		TPL::output("admin/category/list");
	}

	public function edit_action()
	{
		$category = $this->model('system')->get_category_info($_GET['category_id']);
		
		$nav_menu = $this->model('menu')->get_nav_menu_list(null, false, true);
		
		TPL::assign('category', $category);
		TPL::assign('menu_category_ids', $nav_menu['category_ids']);
		TPL::assign('category_option', $this->model('system')->build_category_html('question', 0, $category['parent_id'], null, false));
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
		
		$category_id = intval($_GET['category_id']);
		$parent_id = intval($_POST['parent_id']);
		
		if ($category_id > 0 AND $parent_id > 0 AND $category_list = $this->model('system')->fetch_category('question', $category_id))
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
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($category_id != $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名不可以全为数字')));
			}
	
			if ($this->model('category')->check_url_token($_POST['url_token'], $category_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
			}
		}
		
		//增加新分类
		if (! $category_id)
		{
			$category_id = $this->model('category')->add_category('question', $_POST['title'], $parent_id);
		}
		
		$category = $this->model('system')->get_category_info($category_id);
		
		if ($_POST['add_nav_menu'] == 1)
		{
			$this->model('menu')->add_nav_menu($_POST['title'], '', 'category', $category_id);
		}
		
		if ($category['id'] == $parent_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
		}
		
		$update_data = array(
			'title' => $_POST['title'], 
			'parent_id' => $parent_id,
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
		
		$category_id = intval($_GET['category_id']);
		
		if ($category_id == 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('默认分类不可删除')));
		}
		
		if ($this->model('category')->question_exists($category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类下存在问题, 请先批量移动问题到其它分类, 再删除当前分类')));
		}
		
		$this->model('category')->delete_category('question', $category_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function question_move_action()
	{
		$this->crumb(AWS_APP::lang()->_t('问题批量移动'), "admin/category/list/");
		
		TPL::assign('from_category', $this->model('system')->build_category_html('question', 0, intval($_GET['category_id'])));
		
		TPL::assign('target_category', $this->model('system')->build_category_html('question', 0, null));
		
		TPL::output('admin/category/question_move');
	}
	
	public function question_move_process_action()
	{
		if (!is_array($_POST['from_ids']) OR !$_POST['target_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
		}
		
		if (in_array($_POST['target_id'], $_POST['from_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
		}
		
		$this->model('question')->question_move_category($_POST['from_ids'], $_POST['target_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('问题批量移动处理完成')));
	}
}