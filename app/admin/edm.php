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

class edm extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('邮件群发'), "admin/edm/");
	}

	public function groups_action()
	{
		$groups_list = $this->model('edm')->fetch_groups($_GET['page'], $this->per_page);
		$total_rows = $this->model('edm')->found_rows();

		if ($groups_list)
		{
			foreach ($groups_list AS $key => $val)
			{
				$groups_list[$key]['users'] = $this->model('edm')->calc_group_users($val['id']);
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/edm/groups/'),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('groups_list', $groups_list);

		TPL::assign('reputation_user_group', $this->model('account')->get_user_group_list(1));
		TPL::assign('system_user_group', $this->model('account')->get_user_group_list(0));

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(701));

		TPL::output('admin/edm/groups');
	}

	public function tasks_action()
	{
		$tasks_list = $this->model('edm')->fetch_tasks($_GET['page'], $this->per_page);
		$total_rows = $this->model('edm')->found_rows();

		if ($tasks_list)
		{
			foreach ($tasks_list AS $key => $val)
			{
				$tasks_list[$key]['users'] = $this->model('edm')->calc_task_users($val['id']);
				$tasks_list[$key]['views'] = $this->model('edm')->calc_task_views($val['id']);
				$tasks_list[$key]['sent'] = $this->model('edm')->calc_task_sent($val['id']);
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/edm/tasks/'),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('tasks_list', $tasks_list);

		TPL::assign('usergroups', $this->model('edm')->fetch_groups());

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(702));

		TPL::output('admin/edm/tasks');
	}

	public function remove_task_action()
	{
		$this->model('edm')->remove_task($_GET['id']);

		H::redirect_msg(AWS_APP::lang()->_t('任务已删除'), '/admin/edm/tasks/');
	}

	public function remove_group_action()
	{
		$this->model('edm')->remove_group($_GET['id']);

		H::redirect_msg(AWS_APP::lang()->_t('用户群已删除'), '/admin/edm/groups/');
	}

	public function export_active_users_action()
	{
		if ($export = $this->model('edm')->fetch_task_active_emails($_GET['id']))
		{
			HTTP::force_download_header('export.txt');

			foreach ($export AS $key => $data)
			{
				echo $data['email'] . "\r\n";
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('没有活跃用户'), '/admin/edm/tasks/');
		}
	}
}