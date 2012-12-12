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


if (!defined('IN_ANWSION'))
{
	die;
}

class email_class extends AWS_MODEL
{
	public $user_id;
	
	const TYPE_FOLLOW_ME = 11; //有人关注的了我
	const TYPE_QUESTION_INVITE = 13; //有人邀请我回复问题
	const TYPE_NEW_ANSWER = 14; //我关注的问题有新回复
	const TYPE_NEW_MESSAGE = 15; //有人向我发送私信
	const TYPE_VALID_EMAIL = 101; //验证邮箱
	const TYPE_INVITE_REG = 102; //邀请注册
	const TYPE_FIND_PASSWORD = 103; //找回密码
	const TYPE_QUESTION_SHARE = 104; //分享问题
	const TYPE_ANSWER_SHARE = 105; //分享回复
	const TYPE_INVITE_QUESTION = 106; //站外邀请
	const TYPE_TOPIC_SHARE = 107; //分享话题
	const TYPE_QUESTION_MOD = 108; //问题被修改
	const TYPE_QUESTION_DEL = 109; //问题被删除

	
	public function setup()
	{
		$this->user_id = USER::get_client_uid();
	}

	/**
	 * 动作邮件
	 */
	function action_email($action = 0, $uid, $link, $data = array())
	{		
		if (!H::valid_email($uid))
		{
			if (! $this->check_email_setting($uid, $action))
			{
				return false;
			}
		}
		
		$email_message = (array)AWS_APP::config()->get('email_message');
		
		foreach ($email_message[$action] as $key => $val)
		{
			$$key = str_replace('[#user_name#]', $data['user_name'], $val);
			$$key = str_replace('[#site_name#]', get_setting('site_name'), $$key);
			$$key = str_replace('[#question_title#]', $data['question_title'], $$key);
			
			if (preg_match('/question_detail/i', $$key))
			{
				$$key = str_replace('[#question_detail#]', $data['question_detail'], $$key);
			}
		}
		
		return $this->send($uid, $subject, $message, $link, $link_title);
	}

	/**
     * 发送邮件
     */
	function send($uid, $subject, $message, $link = null, $link_title = null)
	{
		if (H::valid_email($uid))
		{
			$email = $uid;
		}
		else if (is_numeric($uid))
		{			
			if (! $user_info = $this->model('account')->get_user_info_by_uid($uid))
			{
				return false;
			}
			
			$username = $user_info['user_name'];
			$email = $user_info['email'];
		}
		else
		{
			return false;
		}
		
		TPL::assign('username', $username);
		TPL::assign('subject', $subject);
		TPL::assign('message', $message);
		TPL::assign('link', $link);
		TPL::assign('link_title', $link_title);
		
		return load_class('core_mail')->send_mail(null, get_setting('site_name'), $email, $username, $subject, TPL::output('global/email_template', false));
	}

	/**
     * 获得用户email设置记录
     * @param unknown_type $uid
     */
	function get_email_setting($uid)
	{
		if (!$uid)
		{
			return false;
		}
		
		return $this->fetch_row('users_email_setting', 'uid = ' . intval($uid));
	}

	/**
     * 判断用户email设置
     * @param unknown_type $uid
     * @param unknown_type $type
     */
	function check_email_setting($uid, $type)
	{
		if (in_array($type, array(
			11, 
			12, 
			13, 
			14, 
			15
		)))
		{
			if ($uid == $this->user_id)
			{
				return false;
			}
			
			$user_setting = $this->get_email_setting($uid);
			
			if ($user_setting['sender_' . $type] == 1)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		return true;
	}
}