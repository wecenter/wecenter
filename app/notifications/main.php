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
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		return $rule_action;
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('通知'), '/notifications/');

		// 友情链接
		$links_setting = get_setting('links_setting');

		if ($links_setting['enabled'] == 'Y' AND $links_setting['show_on_all_page'] == 'Y' AND ($links_setting['hide_when_login'] == 'N' OR $links_setting['hide_when_login'] != 'N' AND !$this->user_id))
		{
			$links_list = $this->model('admin')->fetch_all('links', "viable = 'Y'", 'rank ASC');

			if ($links_setting['random'] == 'Y')
			{
				shuffle($links_list);
			}

			TPL::assign('links_list', $links_list);
		}

		TPL::output('notifications/index');
	}
}