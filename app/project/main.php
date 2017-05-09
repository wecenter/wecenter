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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';

		$rule_action['actions'] = array(
			'publish'
		);

		return $rule_action;
	}

	public function setup()
	{
		if (get_setting('project_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('活动系统未启用'), '/');
		}

		$this->crumb(AWS_APP::lang()->_t('活动'), '/project/');

		TPL::import_css('css/project.css');
	}

	public function index_action()
	{
		if ($_GET['id'])
		{
			if ($_GET['notification_id'])
			{
				$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
			}

			/*if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
			{
				HTTP::redirect('/m/project/' . $_GET['id']);
			}*/

			if (!$project_info = $this->model('project')->get_project_info_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('项目不存在或已被删除'));
			}

			if ($project_info['approved'] != 1 OR $project_info['status'] == 'OFFLINE')
			{
				if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND $project_info['uid'] != $this->user_id)
				{
					H::redirect_msg(AWS_APP::lang()->_t('项目目前待审核中或已下线'));
				}
			}

			$this->crumb($project_info['title'], '/project/' . $project_info['id']);

			$project_info['attachs'] = $this->model('publish')->get_attach('project', $project_info['id'], 'min');

			$project_info['attachs_ids'] = FORMAT::parse_attachs($project_info['description'], true);

			$project_info['description'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($project_info['description'])));

			$project_info['user_info'] = $this->model('account')->get_user_info_by_uid($project_info['uid']);

			$project_info['category_info'] = $this->model('system')->get_category_info($project_info['category_id']);

			$project_topics = $this->model('topic')->get_topics_by_item_id($project_info['id'], 'project');

			TPL::assign('project_topics', $project_topics);

			TPL::assign('project_products', $this->model('project')->get_products_by_project_id($project_info['id']));

			foreach ($project_topics AS $key => $val)
			{
				$project_topics_ids[] = $val['topic_id'];
			}

			TPL::assign('question_related_list', $this->model('posts')->get_posts_list('question', 1, 10, null, $project_topics_ids));

			TPL::assign('sponsored_users', $this->model('project')->get_sponsored_users($project_info['id'], $project_info['sponsored_users'], $project_info['project_type']));

			if ($this->user_id)
			{
				TPL::assign('like_status', $this->model('project')->get_like_status_by_uid($project_info['id'], $this->user_id));

				$project_order = $this->model('project')->get_single_project_order_by_uid($this->user_id, $project_info['id']);

				if (get_setting('upload_enable') == 'Y' AND $project_info['project_type'] == 'EVENT' AND $project_order)
				{
					TPL::import_js('js/fileupload.js');

					TPL::assign('attach_access_key', md5($this->user_id . time()));
				}

				TPL::assign('project_order', $project_order);
			}

			if ($this->model('posts')->get_posts_list_total() != $project_info['discuss_count'])
			{
				$this->model('project')->update_project_discuss_count($project_info['id'], $this->model('posts')->get_posts_list_total());
			}

			TPL::assign('project_info', $project_info);

			TPL::output('project/index');
		}
		else
		{
			/*if (is_mobile() AND HTTP::get_cookie('_ignore_ua_check') != 'TRUE')
			{
				HTTP::redirect('/m/project_square/');
			}*/

			switch ($_GET['sort'])
			{
				default:
					$order_by = 'add_time DESC';
				break;

				case 'amount':
				case 'sponsored_users':
				case 'update_time':
					$order_by = $_GET['sort'] . ' DESC';
				break;
			}

			if (get_setting('category_enable') == 'Y')
			{
				TPL::assign('category_list', $this->model('menu')->get_nav_menu_list('project'));
			}

			if ($project_list = $this->model('project')->get_projects_list($_GET['category_id'], 1, 'ONLINE', $_GET['page'], 9, $order_by))
			{
				TPL::assign('project_list', $project_list);
			}

			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/project/'),
				'total_rows' => $this->model('project')->found_rows(),
				'per_page' => get_setting('contents_per_page')
			))->create_links());

			TPL::output('project/square');
		}
	}

	public function publish_action()
	{
		if ($_GET['id'])
		{
			if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个项目'), '/project/' . $_GET['id']);
			}

			if (!$project_info = $this->model('project')->get_project_info_by_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('该活动不存在'), '/project/');
			}

			$this->crumb(AWS_APP::lang()->_t('编辑活动'), '/project/publish/' . $_GET['id']);

			TPL::assign('project_topics', $this->model('topic')->get_topics_by_item_id($project_info['id'], 'project'));

			TPL::assign('project_products', $this->model('project')->get_products_by_project_id($project_info['id']));

			TPL::assign('project_info', $project_info);
		}
		else
		{
			if (!$this->user_info['permission']['publish_project'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布活动'), '/project/');
			}

			$this->crumb(AWS_APP::lang()->_t('发布活动'), '/project/publish/');
		}

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			// editor
			TPL::import_js('js/editor/ckeditor/ckeditor.js');
			TPL::import_js('js/editor/ckeditor/adapters/jquery.js');
		}

		TPL::import_js('js/jquery.date_input.js');

		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('project_category_list', $this->model('system')->build_category_html('question', 0, $project_info['category_id']));
		}

		TPL::assign('attach_access_key', md5($this->user_id . time()));

		TPL::output('project/publish');
	}
}
