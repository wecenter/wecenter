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

class weixin extends AWS_CONTROLLER
{
	public function setup()
	{
		$this->model('admin_session')->init();
		
		$this->crumb(AWS_APP::lang()->_t('微信'), "admin/weixin/reply/");
	}
	
	public function reply_action()
	{
		TPL::assign('rule_list', $this->model('weixin')->fetch_reply_rule_list());
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 801));
		
		TPL::output('admin/weixin/reply');
	}
	
	public function reply_add_action()
	{
		$this->crumb(AWS_APP::lang()->_t('添加规则'), "admin/weixin/reply_add/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 801));
		
		TPL::output('admin/weixin/reply_edit');
	}
	
	public function rule_save_action()
	{
		if ($_POST['id'])
		{
			// edit
		}
		else
		{
			if (!$_POST['keyword'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入关键词')));
			}
			
			if (!$_POST['title'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入回应内容')));
			}
			
			if ($_FILES['image']['name'])
			{
				AWS_APP::upload()->initialize(array(
					'allowed_types' => 'jpg,jpeg,png',
					'upload_path' => get_setting('upload_dir') . '/weixin/reply/',
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
					'width' => 80,
					'height' => 80
				))->resize();	
				
				$image_file = basename($upload_data['full_path']);
			}
			
			$this->model('weixin')->add_reply_rule($_POST['keyword'], $_POST['title'], $_POST['description'], $image_file);
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/weixin/reply/'), 1, null));
	}
	
	public function rule_remove_action()
	{
		$this->model('weixin')->remove_reply_rule($_GET['id']);
			
		H::ajax_json_output(null, 1, null);
	}
}