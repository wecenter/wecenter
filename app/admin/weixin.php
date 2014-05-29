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

class weixin extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('微信'), "admin/weixin/reply/");
	}

	public function reply_action()
	{
		TPL::assign('rule_list', $this->model('weixin')->fetch_reply_rule_list());
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));

		TPL::output('admin/weixin/reply');
	}

	public function reply_add_action()
	{
		$this->crumb(AWS_APP::lang()->_t('添加规则'), "admin/weixin/reply_add/");

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));

		TPL::output('admin/weixin/reply_edit');
	}

	public function reply_edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('编辑规则'), "admin/weixin/reply_add/");

		if (!$rule_info = $this->model('weixin')->get_reply_rule_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('自定义回复规则不存在'));
		}

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));
		TPL::assign('rule_info', $rule_info);

		TPL::output('admin/weixin/reply_edit');
	}

	public function mp_menu_action()
	{
		$account_id = $_GET['id'] ?: 0;

		$account_info = $this->model('weixin')->get_account_info_by_id($account_id);

		if ($account_info['weixin_account_role'] == 'base' OR empty($account_info['weixin_app_id']) OR empty($account_info['weixin_app_secret']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('此功能不适用于未通过微信认证的订阅号'));
		}

		$this->crumb(AWS_APP::lang()->_t('菜单管理'), 'admin/weixin/mp_menu/');

		$this->model('weixin')->client_list_image_clean($account_info['weixin_mp_menu']);

		TPL::assign('account_id', $account_info['id']);
		TPL::assign('mp_menu', $account_info['weixin_mp_menu']);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(803));

		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list('id DESC', null, null));

		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('category_data', json_decode($this->model('system')->build_category_json('question'), true));
		}

		TPL::assign('reply_rule_list', $this->model('weixin')->fetch_unique_reply_rule_list());

		TPL::import_js('js/ajaxupload.js');
		TPL::import_js('js/md5.js');

		TPL::output('admin/weixin/mp_menu');
	}

	public function save_mp_menu_action()
	{
		$account_id = $_POST['id'] ?: 0;

		if ($_POST['button'])
		{
			if (!$mp_menu = $this->model('weixin')->process_mp_menu_post_data($_POST['button']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('远程服务器忙,请稍后再试')));
			}
		}

		$this->model('weixin')->update_setting_or_account($account_id, array(
			'weixin_mp_menu' => $mp_menu
		));

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function rule_save_action()
	{
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入回应内容')));
		}

		if ($_POST['id'])
		{
			if (!$rule_info = $this->model('weixin')->get_reply_rule_by_id($_POST['id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('自定义回复规则不存在')));
			}

			if ($_FILES['image']['name'])
			{
				AWS_APP::upload()->initialize(array(
					'allowed_types' => 'jpg,jpeg,png',
					'upload_path' => get_setting('upload_dir') . '/weixin/',
					'is_image' => TRUE
				))->do_upload('image');


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

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $upload_data['full_path'],
					'width' => 640,
					'height' => 320
				))->resize();

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => get_setting('upload_dir') . '/weixin/square_' . basename($upload_data['full_path']),
					'width' => 80,
					'height' => 80
				))->resize();

				unlink(get_setting('upload_dir') . '/weixin/' . $rule_info['image_file']);

				$rule_info['image_file'] = basename($upload_data['full_path']);
			}

			$this->model('weixin')->update_reply_rule($_POST['id'], $_POST['title'], $_POST['description'], $_POST['link'], $rule_info['image_file']);

			H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/weixin/reply/'), 1, null));
		}
		else
		{
			if (!$_POST['keyword'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入关键词')));
			}

			if ($this->model('weixin')->get_reply_rule_by_keyword($_POST['keyword']) AND !$_FILES['image']['name'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的文字回应关键词')));
			}

			if ($_FILES['image']['name'])
			{
				AWS_APP::upload()->initialize(array(
					'allowed_types' => 'jpg,jpeg,png',
					'upload_path' => get_setting('upload_dir') . '/weixin/',
					'is_image' => TRUE
				))->do_upload('image');


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

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $upload_data['full_path'],
					'width' => 640,
					'height' => 320
				))->resize();

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => get_setting('upload_dir') . '/weixin/square_' . basename($upload_data['full_path']),
					'width' => 80,
					'height' => 80
				))->resize();

				$image_file = basename($upload_data['full_path']);
			}

			$this->model('weixin')->add_reply_rule($_POST['keyword'], $_POST['title'], $_POST['description'], $_POST['link'], $image_file);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . 'admin/weixin/reply/'), 1, null));
	}

	public function save_reply_rule_status_action()
	{
		define('IN_AJAX', true);

		if ($_POST['rule_ids'])
		{
			foreach ($_POST['rule_ids'] AS $rule_id => $val)
			{
				$this->model('weixin')->update_reply_rule_enabled($rule_id, $_POST['enabled_status'][$rule_id]);
				$this->model('weixin')->update_reply_rule_sort($rule_id, $_POST['sort_status'][$rule_id]);
			}

			if ($_POST['is_subscribe'])
			{
				$this->model('setting')->set_vars(array(
					'weixin_subscribe_message_key' => $_POST['is_subscribe']
				));
			}

			if ($_POST['is_no_result'])
			{
				$this->model('setting')->set_vars(array(
					'weixin_no_result_message_key' => $_POST['is_no_result']
				));
			}
		}

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
	}

	public function reply_remove_action()
	{
		$this->model('weixin')->remove_reply_rule($_GET['id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function list_image_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/weixin/list_image/',
			'is_image' => TRUE,
			'file_name' => str_replace(array('/', '\\', '.'), '', $_GET['attach_access_key']) . '.jpg',
			'encrypt_name' => FALSE
		));

		if ($_GET['attach_access_key'])
		{
			AWS_APP::upload()->do_upload('list_image');
		}
		else
		{
			return false;
		}

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				default:
					die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
				break;

				case 'upload_invalid_filetype':
					die("{'error':'文件类型无效'}");
				break;

				case 'upload_invalid_filesize':
					die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_size_limit') .  " KB'}");
				break;
			}
		}

		if (! $upload_data = AWS_APP::upload()->data())
		{
			die("{'error':'上传失败, 请与管理员联系'}");
		}

		if ($upload_data['is_image'] == 1)
		{
			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => $upload_data['full_path'],
				'width' => 640,
				'height' => 320
			))->resize();

			AWS_APP::image()->initialize(array(
				'quality' => 90,
				'source_image' => $upload_data['full_path'],
				'new_image' => get_setting('upload_dir') . '/weixin/list_image/square_' . basename($upload_data['full_path']),
				'width' => 80,
				'height' => 80
			))->resize();
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function accounts_action()
	{
		$accounts_list = $this->model('weixin')->fetch_page('weixin_accounts', null, 'id ASC', null, null);
		$accounts_total = $this->model('weixin')->found_rows();

		$this->crumb(AWS_APP::lang()->_t('微信账号'), "admin/weixin/accounts/");

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(802));
		TPL::assign('accounts_list', $accounts_list);
		TPL::assign('accounts_total', $accounts_total);
		TPL::output('admin/weixin/accounts');
	}

	public function save_accounts_action()
	{
		define('IN_AJAX', TRUE);

		unset($_POST['_post_type']);

		foreach ($_POST AS $name => $array)
		{
			foreach ($_POST[$name] AS $key => $value)
			{
				$accounts_info[$key][$name] = $value;
			}
		}

		foreach ($accounts_info AS $account_info)
		{
			$account_info['weixin_mp_token'] = trim($account_info['weixin_mp_token']);

			if (empty($account_info['weixin_mp_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信公众平台接口 Token 不能为空')));
			}

			$account_info['id'] = intval($account_info['id']);

			if (empty($account_info['id']))
			{
				$this->model('weixin')->add_account($account_info);
			}
			else
			{
				$this->model('weixin')->update_setting_or_account($account_info['id'], $account_info);
			}
		}

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('添加和更新微信账号成功')));
	}

	public function del_account_action()
	{
		define('IN_AJAX', TRUE);

		if ($_POST['id'])
		{
			$this->model('weixin')->del_account($_POST['id']);

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信账号不存在')));
		}
	}

	public function sent_msgs_list_action()
	{
		$msgs_list = $this->model('weixin')->fetch_page('weixin_msg', null, 'id DESC', $_GET['page'], $this->per_page);
		$msgs_total = $this->model('weixin')->found_rows();

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . 'admin/weixin/sent_msgs_list/',
			'total_rows' => $msgs_total,
			'per_page' => $this->per_page
		))->create_links());

		$this->crumb(AWS_APP::lang()->_t('群发列表'), "admin/weixin/sent_msgs_list/");

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(804));
		TPL::assign('msgs_list', $msgs_list);
		TPL::assign('msgs_total', $msgs_total);
		TPL::output('admin/weixin/sent_msgs_list');
	}

	public function sent_msg_details_action()
	{
		$msg_details = $this->model('weixin')->get_msg_details_by_id($_GET['id']);

		if (!$msg_details)
		{
			H::redirect_msg(AWS_APP::lang()->_t('群发消息不存在'));
		}

		if ($msg_details['article_ids'])
		{
			$articles_info = $this->model('article')->get_article_info_by_ids($msg_details['article_ids']);
		}

		if ($msg_details['question_ids'])
		{
			$questions_info = $this->model('question')->get_question_info_by_ids($msg_details['question_ids']);
		}

		$this->crumb(AWS_APP::lang()->_t('查看群发消息'), "admin/weixin/sent_msg_details/");

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(804));
		TPL::assign('msg_details', $msg_details);
		TPL::assign('articles_info', $articles_info);
		TPL::assign('questions_info', $questions_info);
		TPL::output('admin/weixin/sent_msg_details');
	}

	public function unsent_msg_action()
	{
		if (get_setting('weixin_account_role') != 'service' OR empty(get_setting('weixin_app_id')) OR empty(get_setting('weixin_app_secret')))
		{
			H::redirect_msg(AWS_APP::lang()->_t('此功能只适用于通过微信认证的服务号'));
		}

		$groups = $this->model('weixin')->get_groups();

		if (!is_array($groups))
		{
			H::redirect_msg(AWS_APP::lang()->_t('获取微信分组失败，错误为：<br />') . $groups);
		}

		$article_ids = AWS_APP::cache()->get('unsent_article_ids');

		$this->crumb(AWS_APP::lang()->_t('群发消息'), "admin/weixin/unsent_msg/");

		if ($article_ids)
		{
			TPL::assign('article_ids', $article_ids);
		}

		$question_ids = AWS_APP::cache()->get('unsent_question_ids');

		if ($question_ids)
		{
			TPL::assign('question_ids', $question_ids);
		}

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(804));
		TPL::assign('groups', $groups);
		TPL::output('admin/weixin/unsent_msg');
	}

	public function send_msg_action()
	{
		define('IN_AJAX', TRUE);

		$group_id = intval($_POST['group_id']);

		if (!isset($group_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要群发的分组')));
		}

		$groups = $this->model('weixin')->get_groups();

		$group_name = $groups[$group_id]['name'];

		if (!isset($group_name))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('选择的分组不存在')));
		}

		$article_ids = array_unique(array_filter(explode(',', $_POST['article_ids'])));

		$question_ids = array_unique(array_filter(explode(',', $_POST['question_ids'])));

		if (empty($article_ids) AND empty($question_ids))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请添加要群发的文章或问题 id')));
		}

		$total = count($article_ids) + count($question_ids);

		if ($total > 10)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('最多可添加 10 个文章和问题')));
		}

		if (!empty($article_ids))
		{
			$error_msg = $this->model('weixin')->add_articles_to_mpnews($article_ids);

			if (isset($error_msg))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传文章作者的头像失败，错误为：') . $error_msg));
			}
		}

		if (!empty($question_ids))
		{
			$error_msg = $this->model('weixin')->add_questions_to_mpnews($question_ids);

			if (isset($error_msg))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传问题作者的头像失败，错误为：') . $error_msg));
			}
		}

		$error_msg = $this->model('weixin')->upload_mpnews();

		if (isset($error_msg))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传图文消息失败，错误为：') . $error_msg));
		}

/*
		if ($_FILES['msg_img']['error'] === UPLOAD_ERR_OK)
		{
			if ($_FILES['msg_img']['type'] != 'image/jpeg')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许上传 jpeg 格式的图片')));
			}

			if ($_FILES['msg_img']['size'] > '262144')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('图片最大为 256KB')));
			}

			if (is_uploaded_file($_FILES['msg_img']['tmp_name']))
			{
				$msg_img = $_FILES['msg_img']['tmp_name'];
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('非法的上传文件')));
			}
		}
		else
		{
			$msg_img = AWS_APP::config()->get('weixin')->default_list_image;
		}
*/

		$error_msg = $this->model('weixin')->send_msg($group_id, 'mpnews');

		if (isset($error_msg))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('群发任务提交失败，错误为：') . $error_msg));
		}

		$this->model('weixin')->save_sent_msg($group_name, $article_ids, $question_ids, $groups[$group_id]['count']);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('群发任务提交成功')));
	}
}