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
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/category/list/'
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
}