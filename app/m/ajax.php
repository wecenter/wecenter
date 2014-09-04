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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

define('IN_MOBILE', true);

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array(
			'hot_topics_list'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function favorite_list_action()
	{
		if ($_GET['tag'])
		{
			$this->crumb(AWS_APP::lang()->_t('标签') . ': ' . $_GET['tag'], '/favorite/tag-' . $_GET['tag']);
		}

		if ($action_list = $this->model('favorite')->get_item_list($_GET['tag'], $this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
		{
			foreach ($action_list AS $key => $val)
			{
				$item_ids[] = $val['item_id'];
			}

			TPL::assign('list', $action_list);
		}
		else
		{
			if (!$_GET['page'] OR $_GET['page'] == 1)
			{
				$this->model('favorite')->remove_favorite_tag(null, null, $_GET['tag'], $this->user_id);
			}
		}

		TPL::output('m/ajax/favorite_list');
	}

	public function inbox_list_action()
	{
		if ($inbox_dialog = $this->model('message')->get_inbox_message($_GET['page'], get_setting('contents_per_page'), $this->user_id))
		{
			foreach ($inbox_dialog as $key => $val)
			{
				$dialog_ids[] = $val['id'];

				if ($this->user_id == $val['recipient_uid'])
				{
					$inbox_dialog_uids[] = $val['sender_uid'];
				}
				else
				{
					$inbox_dialog_uids[] = $val['recipient_uid'];
				}
			}
		}

		if ($inbox_dialog_uids)
		{
			if ($users_info_query = $this->model('account')->get_user_info_by_uids($inbox_dialog_uids))
			{
				foreach ($users_info_query as $user)
				{
					$users_info[$user['uid']] = $user;
				}
			}
		}

		if ($dialog_ids)
		{
			$last_message = $this->model('message')->get_last_messages($dialog_ids);
		}

		if ($inbox_dialog)
		{
			foreach ($inbox_dialog as $key => $value)
			{
				if ($value['recipient_uid'] == $this->user_id AND $value['recipient_count']) // 当前处于接收用户
				{
					$data[$key]['user_name'] = $users_info[$value['sender_uid']]['user_name'];
					$data[$key]['url_token'] = $users_info[$value['sender_uid']]['url_token'];

					$data[$key]['unread'] = $value['recipient_unread'];
					$data[$key]['count'] = $value['recipient_count'];

					$data[$key]['uid'] = $value['sender_uid'];
				}
				else if ($value['sender_uid'] == $this->user_id AND $value['sender_count']) // 当前处于发送用户
				{
					$data[$key]['user_name'] = $users_info[$value['recipient_uid']]['user_name'];
					$data[$key]['url_token'] = $users_info[$value['recipient_uid']]['url_token'];

					$data[$key]['unread'] = $value['sender_unread'];
					$data[$key]['count'] = $value['sender_count'];
					$data[$key]['uid'] = $value['recipient_uid'];
				}

				$data[$key]['last_message'] = $last_message[$value['id']];
				$data[$key]['update_time'] = $value['update_time'];
				$data[$key]['id'] = $value['id'];
			}
		}

		TPL::assign('list', $data);

		TPL::output('m/ajax/inbox_list');
	}

	public function hot_topics_list_action()
	{
		TPL::assign('hot_topics_list', $this->model('topic')->get_topic_list(null, 'discuss_count DESC', 5, $_GET['page']));

		TPL::output('m/ajax/hot_topics_list');
	}

}