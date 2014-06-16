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

		if ($_GET['type'] == 'weibo')
		{
			$approval_list = $this->model('weibo')->fetch_page('weibo_msg', 'question_id IS NULL', 'created_at ASC', $_GET['page'], $this->per_page);

			$found_rows = $this->model('weibo')->found_rows();
		}
		else
		{
			$approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page);

			$found_rows = $this->model('publish')->found_rows();
		}

		TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));

		TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));

		TPL::assign('article_count', $this->model('publish')->count('approval', "type = 'article'"));

		TPL::assign('article_comment_count', $this->model('publish')->count('approval', "type = 'article_comment'"));

		TPL::assign('weibo_msg_count', $this->model('weibo')->count('weibo_msg', 'question_id IS NULL'));

		if ($approval_list)
		{
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/admin/approval/list/type-' . $_GET['type']),
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

		TPL::assign($_GET['type'] . '_count', $found_rows);

		TPL::assign('approval_list', $approval_list);

		TPL::output('admin/approval/list');
	}

	public function preview_action()
	{
		if ($_GET['type'] == 'weibo_msg')
		{
			$approval_item = $this->model('weibo')->get_msg_info_by_id($_GET['weibo_id']);

			if (empty($approval_item['question_id']))
			{
				exit();
			}
			else
			{
				$approval_item['type'] = 'weibo_msg';
			}
		}
		else
		{
			$approval_item = $this->model('publish')->get_approval_item($_GET['id']);
		}

		if (empty($approval_item))
		{
			exit();
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

			case 'weibo_msg':
				$approval_item['content'] = $approval_item['text'];
		}

		if ($approval_item['data']['attach_access_key'])
		{
			$approval_item['attachs'] = $this->model('publish')->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']);
		}

		TPL::assign('approval_item', $approval_item);

		TPL::output('admin/approval/preview');
	}
}