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

class topic extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(303));
	}

	public function list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('话题管理'), 'admin/topic/list/');

		if ($_POST)
		{
			foreach ($_POST as $key => $val)
			{
				if ($key == 'keyword')
				{
					$val = rawurlencode($val);
				}

				$param[] = $key . '-' . $val;
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/admin/topic/list/' . implode('__', $param))
			), 1, null));
		}

		$where = array();

		if ($_GET['keyword'])
		{
			$where[] = "topic_title LIKE '" . $this->model('topic')->quote($_GET['keyword']) . "%'";
		}

		if ($_GET['discuss_count_min'] OR $_GET['discuss_count_min'] == '0')
		{
			$where[] = 'discuss_count >= ' . intval($_GET['discuss_count_min']);
		}

		if ($_GET['discuss_count_max'] OR $_GET['discuss_count_max'] == '0')
		{
			$where[] = 'discuss_count <= ' . intval($_GET['discuss_count_max']);
		}

		if (base64_decode($_GET['start_date']))
		{
			$where[] = 'add_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}

		if (base64_decode($_GET['end_date']))
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}

		$topic_list = $this->model('topic')->get_topic_list(implode(' AND ', $where), 'topic_id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('topic')->found_rows();

		if ($topic_list)
		{
			foreach ($topic_list AS $key => $topic_info)
			{
				$action_log = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 1, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
					ACTION_LOG::ADD_TOPIC,
					ACTION_LOG::MOD_TOPIC,
					ACTION_LOG::MOD_TOPIC_DESCRI,
					ACTION_LOG::MOD_TOPIC_PIC,
					ACTION_LOG::DELETE_TOPIC,
					ACTION_LOG::ADD_RELATED_TOPIC,
					ACTION_LOG::DELETE_RELATED_TOPIC
				)), -1);

				$action_log = $action_log[0];

				$topic_list[$key]['last_edited_uid'] = $action_log['uid'];

				$topic_list[$key]['last_edited_time'] = $action_log['add_time'];

				$last_edited_uids[] = $topic_list[$key]['last_edited_uid'];
			}

			$users_info_query = $this->model('account')->get_user_info_by_uids($last_edited_uids);

			if ($users_info_query)
			{
				foreach ($users_info_query AS $user_info)
				{
					$users_info[$user_info['uid']] = $user_info;
				}
			}
		}

		$parent_topic_list = $this->model('topic')->get_parent_topics();

		$url_param = array();

		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/topic/list/') . implode('__', $url_param),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('topics_count', $total_rows);
		TPL::assign('list', $topic_list);
		TPL::assign('users_info', $users_info);
		TPL::assign('parent_topic_list', $parent_topic_list);

		TPL::output('admin/topic/list');
	}

	public function parent_action()
	{
		$this->crumb(AWS_APP::lang()->_t('根话题'), 'admin/topic/parent/');

		$topic_list = $this->model('topic')->get_topic_list('is_parent = 1', 'topic_id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('topic')->found_rows();

		if ($topic_list)
		{
			foreach ($topic_list AS $key => $topic_info)
			{
				$action_log = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 1, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
					ACTION_LOG::ADD_TOPIC,
					ACTION_LOG::MOD_TOPIC,
					ACTION_LOG::MOD_TOPIC_DESCRI,
					ACTION_LOG::MOD_TOPIC_PIC,
					ACTION_LOG::DELETE_TOPIC,
					ACTION_LOG::ADD_RELATED_TOPIC,
					ACTION_LOG::DELETE_RELATED_TOPIC
				)), -1);

				$action_log = $action_log[0];

				$topic_list[$key]['last_edited_uid'] = $action_log['uid'];

				$topic_list[$key]['last_edited_time'] = $action_log['add_time'];

				$last_edited_uids[] = $topic_list[$key]['last_edited_uid'];
			}

			$users_info_query = $this->model('account')->get_user_info_by_uids($last_edited_uids);

			foreach ($users_info_query AS $user_info)
			{
				$users_info[$user_info['uid']] = $user_info;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/topic/parent/'),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('list', $topic_list);
		TPL::assign('users_info', $users_info);

		TPL::output('admin/topic/parent');
	}

	public function edit_action()
	{
		if ($_GET['topic_id'])
		{
			$this->crumb(AWS_APP::lang()->_t('话题编辑'), 'admin/topic/edit/');

			$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']);

			if (!$topic_info)
			{
				H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/admin/topic/list/');
			}

			TPL::assign('topic_info', $topic_info);
		}
		else
		{
			$this->crumb(AWS_APP::lang()->_t('新建话题'), 'admin/topic/edit/');
		}

		TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());

		TPL::import_js('js/fileupload.js');

		TPL::output('admin/topic/edit');
	}
}