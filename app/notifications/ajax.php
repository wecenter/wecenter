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

class ajax extends AWS_CONTROLLER
{
	var $per_page;

	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		return $rule_action;
	}

	function setup()
	{
		HTTP::no_cache_header();

		$this->per_page = get_setting('notifications_per_page');
	}

	public function list_action()
	{
		if ($_GET['limit'])
		{
			$per_page = intval($_GET['limit']);
		}
		else
		{
			$per_page = $this->per_page;
		}

		$list = $this->model('notify')->list_notification($this->user_id, $_GET['flag'], intval($_GET['page']) * $per_page . ', ' . $per_page);

		if (!$list AND $this->user_info['notification_unread'] != 0)
		{
			$this->model('account')->update_notification_unread($this->user_id);
		}

		TPL::assign('flag', $_GET['flag']);
		TPL::assign('list', $list);

		if ($_GET['template'] == 'header_list')
		{
			TPL::output("notifications/ajax/header_list");
		}
		else if (is_mobile())
		{
			TPL::output('m/ajax/notifications_list');
		}
		else
		{
			TPL::output("notifications/ajax/list");
		}
	}

	public function read_notification_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}
		else
		{
			$this->model('notify')->mark_read_all($this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}