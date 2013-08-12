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

class nav_menu extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(307));
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('导航设置'), 'admin/nav_menu/');
		
		TPL::assign('nav_menu_list', $this->model('menu')->get_nav_menu_list());
		
		TPL::assign('feature_list', $this->model('feature')->get_feature_list());
		
		TPL::assign('category_list', $this->model('system')->build_category_html('question', 0, 0, null, true));
		
		TPL::assign('setting', get_setting());
		
		TPL::import_js(array(
			'admin/js/jquery.dragsort.js',
			'js/ajaxupload.js',
		));
		
		TPL::output("admin/nav_menu");
	}

	public function save_ajax_action()
	{
		if ($_POST['nav_sort'])
		{
			if ($menu_ids = explode(',', $_POST['nav_sort']))
			{
				foreach($menu_ids as $key => $val)
				{
					$this->model('menu')->update_nav_menu($val, array('sort' => $key));
				}
			}
		}
		
		if ($_POST['nav_menu'])
		{
			foreach($_POST['nav_menu'] as $key => $val)
			{
				$this->model('menu')->update_nav_menu($key, $val);
			}
		}
		
		$setting_update['category_display_mode'] = $_POST['category_display_mode'];
		
		$setting_update['nav_menu_show_child'] = isset($_POST['nav_menu_show_child']) ? 'Y' : 'N';
		
		$vars = $this->model('setting')->check_vars($setting_update);
		
		$this->model('setting')->set_vars($vars);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
	}

	public function add_menu_ajax_action()
	{
		switch ($_POST['type'])
		{
			case 'category' :
				$type_id = intval($_POST['type_id']);
				$category = $this->model('system')->get_category_info($type_id);
				$title = $category['title'];
				break;
			case 'feature' :
				$type_id = intval($_POST['type_id']);
				$feature = $this->model('feature')->get_feature_by_id($type_id);
				$title = $feature['title'];
				break;
			case 'custom' :
				$title = trim($_POST['title']);
				$description = trim($_POST['description']);
				$link = trim($_POST['link']);
				$type_id = 0;
				break;
		}
		
		if (!$title)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航标签不能为空')));
		}
		
		$this->model('menu')->add_nav_menu($title, $description, $_POST['type'], $type_id, $link);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1));
	}
	
	function remove_nav_menu_action()
	{
		$this->model('menu')->remove_nav_menu($_GET['nav_menu_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	function icon_upload_ajax_action()
	{
		$nav_menu_id = intval($_GET['nav_menu_id']);
		
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/nav_menu',
			'is_image' => TRUE,
			'file_name' => $nav_menu_id . '.jpg',
			'encrypt_name' => FALSE
		))->do_upload('icon_file');
		
		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				default:
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
				break;
				
				case 'upload_invalid_filetype':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
				break;
			}
		}
		
		if (! $upload_data = AWS_APP::upload()->data())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
		}
		
		if ($upload_data['is_image'] == 1)
		{
			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => $upload_data['full_path'],
				'width' => 50,
				'height' => 50
			))->resize();	
		}
		
		$this->model('menu')->update_nav_menu($nav_menu_id, array('icon' => basename($upload_data['full_path'])));
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'preview' => get_setting('upload_url') . '/nav_menu/' . basename($upload_data['full_path'])
		), 1, null));
	}
}