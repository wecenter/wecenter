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
		$rule_action['rule_type'] = 'white';

		$rule_action['actions'] = array(
			
		);

		return $rule_action;
	}

	public function index_action()
	{
		TPL::output('work/index');
	}

	public function index_square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('工单'), '/work/');

		
		TPL::output('work/square');
	}

	public function data_action()
	{
		TPL::output('work/data');
	}

	public function topic_action()
	{
		TPL::output('work/topic');
	}

}