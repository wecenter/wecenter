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
	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function follow_people_action()
	{
		if (! $_GET['uid'] OR $_GET['uid'] == $this->user_id)
		{
			die;
		}

		// 首先判断是否存在关注
		if ($this->model('follow')->user_follow_check($this->user_id, $_GET['uid']))
		{
			$action = 'remove';

			$this->model('follow')->user_follow_del($this->user_id, $_GET['uid']);
		}
		else
		{
			$action = 'add';

			$this->model('follow')->user_follow_add($this->user_id, $_GET['uid']);

			$this->model('notify')->send($this->user_id, $_GET['uid'], notify_class::TYPE_PEOPLE_FOCUS, notify_class::CATEGORY_PEOPLE, $this->user_id, array(
				'from_uid' => $this->user_id
			));

			$this->model('email')->action_email('FOLLOW_ME', $_GET['uid'], get_js_url('/people/' . $this->user_info['url_token']), array(
				'user_name' => $this->user_info['user_name'],
			));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $action
		), 1, null));
	}
}