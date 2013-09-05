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
			H::redirect_msg(AWS_APP::lang()->_t('自动回复规则不存在'));
		}
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));
		TPL::assign('rule_info', $rule_info);
		
		TPL::output('admin/weixin/reply_edit');
	}
	
	public function mp_menu_action()
	{
		if (!AWS_APP::config()->get('weixin')->app_id OR !AWS_APP::config()->get('weixin')->app_secret)
		{
			H::redirect_msg(AWS_APP::lang()->_t('请先在 system/config/weixin.php 中配置 App Id'));
		}
		
		$this->crumb(AWS_APP::lang()->_t('菜单管理'), "admin/weixin/mp_menu/");
		
		TPL::assign('mp_menu', get_setting('weixin_mp_menu'));
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(803));
		
		TPL::output('admin/weixin/mp_menu');
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
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('自动回复规则不存在')));
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
			
			$this->model('weixin')->update_reply_rule($_POST['id'], $_POST['event_key'], $_POST['title'], $_POST['description'], $_POST['link'], $rule_info['image_file']);
		
			H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/weixin/reply/'), 1, null));
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
			
			$this->model('weixin')->add_reply_rule($_POST['keyword'], $_POST['event_key'], $_POST['title'], $_POST['description'], $_POST['link'], $image_file);
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/weixin/reply/'), 1, null));
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
	
	public function publish_action()
	{
		TPL::assign('rule_list', $this->model('weixin')->fetch_publish_rule_list());
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(802));
		
		TPL::output('admin/weixin/publish');
	}
	
	public function publish_add_action()
	{
		$this->crumb(AWS_APP::lang()->_t('添加规则'), "admin/weixin/publish_add/");
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(802));
		
		TPL::output('admin/weixin/publish_edit');
	}
	
	public function publish_edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('编辑规则'), "admin/weixin/publish_add/");
		
		if (!$rule_info = $this->model('weixin')->get_publish_rule_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('发文指令规则不存在'));
		}
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(802));
		TPL::assign('rule_info', $rule_info);
		
		TPL::output('admin/weixin/publish_edit');
	}
	
	public function publish_save_action()
	{
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入回应内容')));
		}
		
		if ($_POST['id'])
		{
			if (!$rule_info = $this->model('weixin')->get_publish_rule_by_id($_POST['id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('发文指令规则不存在')));
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
			
			$this->model('weixin')->update_publish_rule($_POST['id'], $_POST['title'], $_POST['description'], $_POST['link'], $_POST['publish_type'], $_POST['item_id'], $_POST['topics'], $rule_info['image_file']);
		
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/weixin/publish/'), 1, null));
		}
		else
		{
			if (!$_POST['keyword'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入关键词')));
			}
			
			if ($this->model('weixin')->get_publish_rule_by_keyword($_POST['keyword']))
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
			
			$this->model('weixin')->add_publish_rule($_POST['keyword'], $_POST['title'], $_POST['description'], $_POST['link'], $_POST['publish_type'], $_POST['item_id'], $_POST['topics'], $image_file);
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/weixin/publish/'), 1, null));
	}
	
	public function save_publish_rule_enabled_action()
	{
		if ($_POST['rule_ids'])
		{
			foreach ($_POST['rule_ids'] AS $rule_id => $val)
			{
				if ($val != $_POST['enabled_status'][$rule_id])
				{
					$this->model('weixin')->update_publish_rule_enabled($rule_id, $_POST['enabled_status'][$rule_id]);
				}
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('启用状态已自动保存')));
	}
	
	public function publish_remove_action()
	{
		$this->model('weixin')->remove_publish_rule($_GET['id']);
			
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function save_mp_menu_action()
	{
		if (!$_POST['button'])
		{
			$_POST['button'] = array();
		}
		
		uasort($_POST['button'], 'array_key_sort_asc_callback');
		
		foreach ($_POST['button'] AS $key => $val)
		{
			if ($val['sub_button'])
			{
				unset($_POST['button'][$key]['key']);
				
				uasort($_POST['button'][$key]['sub_button'], 'array_key_sort_asc_callback');
				
				foreach ($_POST['button'][$key]['sub_button'] AS $sub_key => $sub_value)
				{
					if ($_POST['button'][$key]['sub_button'][$sub_key]['name'] == '' OR $_POST['button'][$key]['sub_button'][$sub_key]['key'] == '')
					{
						unset($_POST['button'][$key]['sub_button'][$sub_key]);
						
						continue;
					}
					
					if (substr($_POST['button'][$key]['sub_button'][$sub_key]['key'], 0, 7) == 'http://' OR substr($_POST['button'][$key]['sub_button'][$sub_key]['key'], 0, 8) == 'https://')
					{
						$_POST['button'][$key]['sub_button'][$sub_key]['type'] = 'view';
						$_POST['button'][$key]['sub_button'][$sub_key]['url'] = $_POST['button'][$key]['sub_button'][$sub_key]['key'];
					}
					else
					{
						$_POST['button'][$key]['sub_button'][$sub_key]['type'] = 'click';
					}
				}
			}
			else
			{
				$_POST['button'][$key]['type'] = 'click';
			}
			
			if ($_POST['button'][$key]['name'] == '')
			{
				unset($_POST['button'][$key]);
			}
			
			if (substr($_POST['button'][$key]['key'], 0, 7) == 'http://' OR substr($_POST['button'][$key]['key'], 0, 8) == 'https://')
			{
				$_POST['button'][$key]['type'] = 'view';
				$_POST['button'][$key]['url'] = $_POST['button'][$key]['key'];
			}
		}
				
		$this->model('setting')->set_vars(array(
			'weixin_mp_menu' => $_POST['button']
		));
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}