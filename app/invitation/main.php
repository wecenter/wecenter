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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function index_action()
	{
		if (!$this->user_info['email'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('当前帐号没有提供 Email, 此功能不可用'));
		}

		$this->crumb(AWS_APP::lang()->_t('邀请好友'), '/invitation/');

		TPL::output('invitation/index');
	}
}