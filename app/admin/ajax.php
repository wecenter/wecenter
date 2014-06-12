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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_ADMIN_CONTROLLER
{	
	public function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function login_process_action()
	{
		if (! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
		}
		
		if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请填写正确的验证码'));
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (! $user_info = $this->model('ucenter')->login($this->user_info['email'], $_POST['password']))
			{
				$user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
		}
		
		if ($user_info['uid'])
		{
			$this->model('admin')->set_admin_login($user_info['uid']);
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('/admin/')
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('帐号或密码错误')));
		}
	}
	
	public function save_settings_action()
	{
		if ($_POST['upload_dir'] AND preg_match('/(.*)\/$/i', $_POST['upload_dir']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传文件存放绝对路径不能以 / 结尾')));
		}

		if ($_POST['upload_url'] AND preg_match('/(.*)\/$/i', $_POST['upload_url']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传目录外部访问 URL 地址不能以 / 结尾')));
		}

		if ($_POST['request_route_custom'])
		{
			$_POST['request_route_custom'] = trim($_POST['request_route_custom']);

			if ($request_routes = explode("\n", $_POST['request_route_custom']))
			{
				foreach ($request_routes as $key => $val)
				{
					if (! strstr($val, '==='))
					{
						continue;
					}

					list($m, $n) = explode('===', $val);

					if (substr($n, 0, 1) != '/' OR substr($m, 0, 1) != '/')
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
					}

					if (strstr($m, '/admin') OR strstr($n, '/admin'))
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则不允许设置 /admin 路由')));
					}
				}
			}
		}

		if ($_POST['censoruser'])
		{
			$_POST['censoruser'] = trim($_POST['censoruser']);
		}

		if ($_POST['report_reason'])
		{
			$_POST['report_reason'] = trim($_POST['report_reason']);
		}

		if ($_POST['sensitive_words'])
		{
			$_POST['sensitive_words'] = trim($_POST['sensitive_words']);
		}

		$curl_require_setting = array('qq_login_enabled', 'sina_weibo_enabled', 'qq_t_enabled');

		if (array_intersect(array_keys($_POST), $curl_require_setting))
		{
			foreach ($curl_require_setting AS $key)
			{
				if ($_POST[$key] == 'Y' AND !function_exists('curl_init'))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微博登录、QQ 登录等功能须服务器支持 CURL')));
				}
			}
		}

		if ($_POST['set_notification_settings'])
		{
			if ($notify_actions = $this->model('notify')->notify_action_details)
			{
				$notification_setting = array();

				foreach ($notify_actions as $key => $val)
				{
					if (! isset($_POST['new_user_notification_setting'][$key]) && $val['user_setting'])
					{
						$notification_setting[] = intval($key);
					}
				}
			}

			$_POST['new_user_notification_setting'] = $notification_setting;
		}

		if ($_POST['set_email_settings'])
		{
			$email_settings = array(
				'FOLLOW_ME' => 'N',
				'QUESTION_INVITE' => 'N',
				'NEW_ANSWER' => 'N',
				'NEW_MESSAGE' => 'N',
				'QUESTION_MOD' => 'N',
			);

			if ($_POST['new_user_email_setting'])
			{
				foreach ($_POST['new_user_email_setting'] AS $key => $val)
				{
					unset($email_settings[$val]);
				}
			}

			$_POST['new_user_email_setting'] = $email_settings;
		}

		$this->model('setting')->set_vars($_POST);

		if ($_POST['wecenter_access_token'])
		{
			$this->model('weixin')->get_weixin_app_id_setting_var();
		}

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统设置修改成功')));
	}
	
	public function approval_manage_action()
	{
		if (!is_array($_POST['approval_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
		}
		
		switch ($_POST['batch_type'])
		{
			case 'approval':
			case 'decline':
				$func = $_POST['batch_type'] . '_publish';
				
				foreach ($_POST['approval_ids'] AS $approval_id)
				{
					$this->model('publish')->$func($approval_id);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function article_manage_action()
	{
		if (empty($_POST['article_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章进行操作')));
		}

		switch ($_POST['action'])
		{
			case 'del':
				foreach ($_POST['article_ids'] AS $article_id)
				{
					$this->model('article')->remove_article($article_id);
				}

				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			break;

			case 'send':
				$result = $this->model('weixin')->add_article_or_question_ids_to_cache($_POST['article_ids'], null);

				H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
			break;
		}
	}
	
	public function save_category_sort_action()
	{
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
	
	public function save_category_action()
	{
		if ($_POST['category_id'] AND $_POST['parent_id'] AND $category_list = $this->model('system')->fetch_category('question', $_POST['category_id']))
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
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_POST['category_id'] != $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名不可以全为数字')));
			}
	
			if ($this->model('category')->check_url_token($_POST['url_token'], $_POST['category_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
			}
		}
		
		if (! $_POST['category_id'])
		{
			$category_id = $this->model('category')->add_category('question', $_POST['title'], $_POST['parent_id']);
		}
		else
		{
			$category_id = intval($_POST['category_id']);
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
			'url' => get_js_url('admin/category/list/')
		), 1, null));
	}

	public function remove_category_action()
	{
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
	
	public function move_category_contents_action()
	{
		if (!$_POST['from_id'] OR !$_POST['target_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
		}
		
		if ($_POST['target_id'] == $_POST['from_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
		}
		
		$this->model('category')->move_contents($_POST['from_id'], $_POST['target_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function edm_add_group_action()
	{
		@set_time_limit(0);
		
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写用户群名称')));
		}
				
		$usergroup_id = $this->model('edm')->add_group($_POST['title']);
				
		switch ($_POST['import_type'])
		{
			case 'text':
				if ($email_list = explode("\n", str_replace(array("\r", "\t"), "\n", $_POST['email'])))
				{
					foreach ($email_list AS $key => $email)
					{
						$this->model('edm')->add_user_data($usergroup_id, $email);
					}
				}
			break;
			
			case 'system_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_user_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'reputation_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_reputation_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'last_active':
				if ($_POST['last_active'])
				{
					$this->model('edm')->import_system_email_by_last_active($usergroup_id, $_POST['last_active']);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function edm_add_task_action()
	{
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写任务名称')));
		}
					
		if (trim($_POST['subject']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写邮件标题')));
		}
					
		if (intval($_POST['usergroup_id']) == 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户群组')));
		}
					
		if (trim($_POST['from_name']) == '')
		{
			$_POST['from_name'] = get_setting('site_name');
		}
					
		$task_id = $this->model('edm')->add_task($_POST['title'], $_POST['subject'], $_POST['message'], $_POST['from_name']);
		
		$this->model('edm')->import_group_data_to_task($task_id, $_POST['usergroup_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('任务建立完成')));
					
	}
	
	public function save_feature_action()
	{
		$feature_id = intval($_GET['feature_id']);
		
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题标题不能为空')));
		}
		
		if ($feature_id)
		{
			$feature = $this->model('feature')->get_feature_by_id($feature_id);
		}
		
		if ($_POST['url_token'])
		{
			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名只允许输入英文或数字')));
			}
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($feature_id != $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名不可以全为数字')));
			}
		
			if ($this->model('feature')->check_url_token($_POST['url_token'], $feature_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名已经被占用请更换一个')));
			}
		}
		
		if (! $feature_id)
		{		
			$feature_id = $this->model('feature')->add_feature($_POST['title']);

			if ($_POST['add_nav_menu'])
			{
				$this->model('menu')->add_nav_menu($_POST['title'], htmlspecialchars($_POST['description']), 'feature', $feature_id);
			}
		}
		
		if ($_POST['topics'])
		{			
			if ($topics = explode("\n", $_POST['topics']))
			{
				$this->model('feature')->empty_topics($feature_id);
			}
			
			foreach ($topics AS $key => $topic_title)
			{
				if ($topic_info = $this->model('topic')->get_topic_by_title(trim($topic_title)))
				{
					$this->model('feature')->add_topic($feature_id, $topic_info['topic_id']);
				}
			}
		}
	
		$update_data = array(
			'title' => $_POST['title'],
			'description' => htmlspecialchars($_POST['description']),
			'css' => htmlspecialchars($_POST['css']),
			'url_token' => $_POST['url_token'],
			'seo_title' => htmlspecialchars($_POST['seo_title'])
		);
	
		if ($_FILES['icon']['name'])
		{
			AWS_APP::upload()->initialize(array(
				'allowed_types' => 'jpg,jpeg,png,gif',
				'upload_path' => get_setting('upload_dir') . '/feature',
				'is_image' => TRUE
			))->do_upload('icon');
			
			
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

			foreach (AWS_APP::config()->get('image')->feature_thumbnail as $key => $val)
			{
				$thumb_file[$key] = $upload_data['file_path'] . $feature_id . "_" . $val['w'] . "_" . $val['h'] . '.jpg';
				
				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();	
			}
			
			unlink($upload_data['full_path']);
			
			$update_data['icon'] = basename($thumb_file['min']);
		}
	
		$this->model('feature')->update_feature($feature_id, $update_data);
	
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/admin/feature/list/')
		), 1, null));
	}
	
	public function remove_feature_action()
	{
		$this->model('feature')->delete_feature($_POST['feature_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function save_feature_status_action()
	{
		if ($_POST['feature_ids'])
		{
			foreach ($_POST['feature_ids'] AS $feature_id => $val)
			{
				$this->model('feature')->update_feature_enabled($feature_id, $_POST['enabled_status'][$feature_id]);
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
	}
	
	public function save_nav_menu_action()
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
		
		$this->model('setting')->set_vars($setting_update);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
	}

	public function add_nav_menu_action()
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
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function remove_nav_menu_action()
	{
		$this->model('menu')->remove_nav_menu($_POST['id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function nav_menu_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/nav_menu',
			'is_image' => TRUE,
			'file_name' => intval($_GET['id']) . '.jpg',
			'encrypt_name' => FALSE
		))->do_upload('attach');
		
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
		
		$this->model('menu')->update_nav_menu($_GET['id'], array('icon' => basename($upload_data['full_path'])));
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'preview' => get_setting('upload_url') . '/nav_menu/' . basename($upload_data['full_path'])
		), 1, null));
	}
}