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
	var $per_page = 20;

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
			'base_url' => get_setting('base_url') . '/?/admin/edm/groups/', 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::assign('groups_list', $groups_list);
		
		TPL::assign('reputation_user_group', $this->model('account')->get_user_group_list(1));
		TPL::assign('system_user_group', $this->model('account')->get_user_group_list(0));
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(701));
		
		TPL::output('admin/edm/groups');
	}
	
	public function add_group_action()
	{
		@set_time_limit(0);
		
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写用户群名称')));
		}
				
		$usergroup_id = $this->model('edm')->add_group($_POST['title']);
				
		switch ($_POST['import_type'])
		{
			case 'text':
				if ($email_list = explode("\n", str_replace(array("\r", "\t"), "\n", $_POST['email'])))
				{
					foreach ($email_list AS $key => $email)
					{
						$this->model('edm')->add_user_data($usergroup_id, $email);
					}
				}
			break;
			
			case 'system_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_user_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'reputation_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_reputation_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'last_active':
				if ($_POST['last_active'])
				{
					$this->model('edm')->import_system_email_by_last_active($usergroup_id, $_POST['last_active']);
				}
			break;
			
			case 'last_login':
				if ($_POST['last_active'])
				{
					$this->model('edm')->import_system_email_by_last_login($usergroup_id, $_POST['last_active']);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户群添加完成')));
	}
	
	public function add_task_action()
	{
		if (trim($_POST['title']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写任务名称')));
		}
					
		if (trim($_POST['subject']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写邮件标题')));
		}
					
		if (intval($_POST['usergroup_id']) == 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户群组')));
		}
					
		if (trim($_POST['from_name']) == '')
		{
			$_POST['from_name'] = get_setting('site_name');
		}
					
		$task_id = $this->model('edm')->add_task($_POST['title'], $_POST['subject'], $_POST['message'], $_POST['from_name']);
		
		$this->model('edm')->import_group_data_to_task($task_id, $_POST['usergroup_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('任务建立完成')));
					
	}
	
	public function remove_task_action()
	{
		$this->model('edm')->remove_task($_GET['id']);
		
		H::redirect_msg(AWS_APP::lang()->_t('任务已删除'), get_setting('base_url') . '/?/admin/edm/tasks/');
	}
	
	public function remove_group_action()
	{
		$this->model('edm')->remove_group($_GET['id']);
		
		H::redirect_msg(AWS_APP::lang()->_t('用户群已删除'), get_setting('base_url') . '/?/admin/edm/groups/');
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
			'base_url' => get_setting('base_url') . '/?/admin/edm/tasks/', 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::assign('tasks_list', $tasks_list);
		
		TPL::assign('usergroups', $this->model('edm')->fetch_groups());
		
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(702));
		
		TPL::output('admin/edm/tasks');
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
			H::redirect_msg(AWS_APP::lang()->_t('没有活跃用户'));
		}
	}
}