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

class approval extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('内容审核'), 'admin/approval/');
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));
	}
	
	public function list_action()
	{
		if (!$_GET['type'])
		{
			$_GET['type'] = 'question';
		}
		
		switch ($_GET['type'])
		{
			case 'question':
				TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));
			break;
			
			case 'answer':
				TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));
			break;
			
			case 'artice':
				TPL::assign('artice_count', $this->model('publish')->count('approval', "type = 'artice'"));
			break;
			
			case 'artice_comment':
				TPL::assign('artice_comment_count', $this->model('publish')->count('approval', "type = 'artice_comment'"));
			break;
		}
		
		if ($approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page))
		{
			$found_rows = $this->model('publish')->found_rows();
			
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_setting('base_url') . '/?/admin/approval/list/type-' . $_GET['type'], 
				'total_rows' => $found_rows, 
				'per_page' => $this->per_page
			))->create_links());
			
			foreach ($approval_list AS $key => $val)
			{
				if (!$approval_uids[$val['uid']])
				{
					$approval_uids[$val['uid']] = $val['uid'];
				}
			}
			
			TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($approval_uids));
		}
		
		TPL::assign($_GET['type'] . '_count', intval($found_rows));
		
		TPL::assign('approval_list', $approval_list);
		
		TPL::output('admin/approval/list');
	}
	
	public function preview_action()
	{
		if (!$approval_item = $this->model('publish')->get_approval_item($_GET['id']))
		{
			die;
		}
		
		switch ($approval_item['type'])
		{
			case 'question':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['question_detail'])));
			break;
			
			case 'answer':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['answer_content'])));
			break;
			
			case 'article':
			case 'article_comment':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['message'])));
			break;
		}
		
		if ($approval_item['data']['attach_access_key'])
		{
			$approval_item['attachs'] = $this->model('publish')->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']);
		}
		
		TPL::assign('approval_item', $approval_item);
		
		TPL::output('admin/approval/preview');
	}
	
	public function batch_action()
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
}