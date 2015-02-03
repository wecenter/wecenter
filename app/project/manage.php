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

class manage extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		return $rule_action;
	}

	public function setup()
	{
		if (get_setting('project_enabled') != 'Y')
		{
			H::redirect_msg(AWS_APP::lang()->_t('活动系统未启用'), '/');
		}

		$this->crumb(AWS_APP::lang()->_t('活动'), '/project/');
		$this->crumb(AWS_APP::lang()->_t('我发布的活动'), '/project/manage/');

		TPL::import_css('css/project.css');
	}

	public function index_action()
	{
		if ($project_list = $this->model('project')->get_projects_list_by_uid($this->user_id, null, null, $_GET['page'], get_setting('contents_per_page')))
		{
			TPL::assign('project_list', $project_list);
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/project/manage/'),
			'total_rows' => $this->model('project')->found_rows(),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::output('project/manage/index');
	}

	public function order_action()
	{
		if ($order_list = $this->model('project')->get_project_publisher_order_list($this->user_id, $_GET['id'], $_GET['page'], get_setting('contents_per_page')))
		{
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/project/manage/order/' . $_GET['id']),
				'total_rows' => $this->model('project')->found_rows(),
				'per_page' => get_setting('contents_per_page')
			))->create_links());

			foreach ($order_list AS $key => $val)
			{
				$order_list[$key]['order_status'] = $this->model('project')->get_order_status($val);
			}
		}

		TPL::assign('order_list', $order_list);

		TPL::output('project/manage/order');
	}
}