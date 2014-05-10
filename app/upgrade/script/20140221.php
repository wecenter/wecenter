<?php

if (!defined('IN_ANWSION'))
{
	die;
}

if (get_setting('weixin_app_id') AND get_setting('wecenter_access_token'))
{
	$this->model('setting')->set_vars(array(
		'weixin_account_role' => 'service'
	));
}