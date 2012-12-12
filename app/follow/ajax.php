<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
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

	public function follow_people_action()
	{
		$action = null;
		$friend_uid = intval($_GET['uid']);
		
		if (! $friend_uid)
		{
			return false;
		}
		
		$follow = $this->model('follow');
		
		//首先判断是否存在关注
		if ($follow->user_follow_check($this->user_id, $friend_uid))
		{
			$action = 'remove';
			
			$follow->user_follow_del($this->user_id, $friend_uid);
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'type' => 'remove'
			), 1, null));
		}
		else
		{
			$action = "add";
			
			$follow->user_follow_add($this->user_id, $friend_uid);
				
			$this->model('notify')->send($this->user_id, $friend_uid, notify_class::TYPE_PEOPLE_FOCUS, notify_class::CATEGORY_PEOPLE, $this->user_id, array(
				'from_uid' => $this->user_id
			));
				
			$this->model('email')->action_email(email_class::TYPE_FOLLOW_ME, $friend_uid, get_js_url('/people/' . $this->user_info['url_token']), array(
				'user_name' => $this->user_info['user_name'],
			));
				
			H::ajax_json_output(AWS_APP::RSM(array(
				'type' => 'add'
			), 1, null));
		}
	}

}