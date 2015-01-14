<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
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
	public function list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('内容审核'), 'admin/approval/list/');

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));

		if (!$_GET['type'])
		{
			$_GET['type'] = 'question';
		}

		TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));

		TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));

		TPL::assign('article_count', $this->model('publish')->count('approval', "type = 'article'"));

		TPL::assign('article_comment_count', $this->model('publish')->count('approval', "type = 'article_comment'"));

		TPL::assign('unverified_modifies_count', $this->model('question')->count('question', 'unverified_modify_count <> 0'));

		if (get_setting('weibo_msg_enabled') == 'question')
		{
			TPL::assign('weibo_msg_count', $this->model('openid_weibo_weibo')->count('weibo_msg', 'question_id IS NULL AND ticket_id IS NULL'));
		}
		else if ($_GET['type'] == 'weibo_msg')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入微博消息至问题未启用')));
		}

		$receiving_email_global_config = get_setting('receiving_email_global_config');

		if ($receiving_email_global_config['enabled'] == 'question')
		{
			TPL::assign('received_email_count', $this->model('edm')->count('received_email', 'question_id IS NULL AND ticket_id IS NULL'));
		}
		else if ($_GET['type'] == 'received_email')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入邮件至问题未启用')));
		}

		switch ($_GET['type'])
		{
			case 'weibo_msg':
			case 'received_email':
				$approval_list = $this->model('admin')->fetch_page($_GET['type'], 'question_id IS NULL AND ticket_id IS NULL', 'id ASC', $_GET['page'], $this->per_page);

				$found_rows = $this->model('admin')->found_rows();

				break;

			case 'unverified_modify':
				$approval_list = $this->model('question')->fetch_page('question', 'unverified_modify_count <> 0', 'question_id ASC', $_GET['page'], $this->per_page);

				$found_rows = $this->model('question')->found_rows();

				break;

			default:
				$approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page);

				$found_rows = $this->model('publish')->found_rows();

				break;
		}

		if ($approval_list)
		{
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/admin/approval/list/type-' . $_GET['type']),
				'total_rows' => $found_rows,
				'per_page' => $this->per_page
			))->create_links());

			if ($_GET['type'] == 'unverified_modify')
			{
				foreach ($approval_list AS $key => $approval_info)
				{
					$approval_list[$key]['uid'] = $approval_info['published_uid'];

					if (!$approval_uids[$approval_list[$key]['uid']])
					{
						$approval_uids[$approval_list[$key]['uid']] = $approval_list[$key]['uid'];
					}

					$approval_list[$key]['unverified_modify'] = @unserialize($approval_info['unverified_modify']);
				}
			}
			else
			{
				foreach ($approval_list AS $approval_info)
				{
					if (!$approval_uids[$approval_info['uid']])
					{
						$approval_uids[$approval_info['uid']] = $approval_info['uid'];
					}
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
		if (!$_GET['action'] OR $_GET['action'] != 'edit')
		{
			$_GET['action'] = 'preview';
		}
		else
		{
			$this->crumb(AWS_APP::lang()->_t('待审项修改'), 'admin/approval/edit/');

			TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));
		}

		switch ($_GET['type'])
		{
			case 'weibo_msg':
				if (get_setting('weibo_msg_enabled') != 'question')
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入微博消息至问题未启用')));
				}

				$approval_item = $this->model('openid_weibo_weibo')->get_msg_info_by_id($_GET['id']);

				if ($approval_item['question_id'])
				{
					exit();
				}

				$approval_item['type'] = 'weibo_msg';

				break;

			case 'received_email':
				$receiving_email_global_config = get_setting('receiving_email_global_config');

				if ($receiving_email_global_config['enabled'] != 'question')
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导入邮件至问题未启用')));
				}

				$approval_item = $this->model('edm')->get_received_email_by_id($_GET['id']);

				if ($approval_item['question_id'])
				{
					exit();
				}

				$approval_item['type'] = 'received_email';

				break;

			default:
				$approval_item = $this->model('publish')->get_approval_item($_GET['id']);

				break;
		}

		if (!$approval_item)
		{
			exit();
		}

		switch ($approval_item['type'])
		{
			case 'question':
				$approval_item['title'] = htmlspecialchars($approval_item['data']['question_content']);

				$approval_item['content'] = htmlspecialchars($approval_item['data']['question_detail']);

				$approval_item['topics'] = htmlspecialchars(implode(',', $approval_item['data']['topics']));

				break;

			case 'answer':
				$approval_item['content'] = htmlspecialchars($approval_item['data']['answer_content']);

				break;

			case 'article':
				$approval_item['title'] = htmlspecialchars($approval_item['data']['title']);

				$approval_item['content'] = htmlspecialchars($approval_item['data']['message']);

				break;

			case 'article_comment':
				$approval_item['content'] = htmlspecialchars($approval_item['data']['message']);

				break;

			case 'weibo_msg':
				$approval_item['content'] = htmlspecialchars($approval_item['text']);

				if ($approval_item['has_attach'])
				{
					$approval_item['attachs'] = $this->model('publish')->get_attach('weibo_msg', $_GET['id']);
				}

				break;

			case 'received_email':
				$approval_item['title'] = htmlspecialchars($approval_item['subject']);

				$approval_item['content'] = htmlspecialchars($approval_item['content']);

				break;
		}

		if ($approval_item['data']['attach_access_key'])
		{
			$approval_item['attachs'] = $this->model('publish')->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']);
		}

		if ($_GET['action'] != 'edit')
		{
			$approval_item['content'] = nl2br(FORMAT::parse_bbcode($approval_item['content']));
		}

		TPL::assign('approval_item', $approval_item);

		TPL::output('admin/approval/' . $_GET['action']);
	}
}