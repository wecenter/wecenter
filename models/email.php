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

class email_class extends AWS_MODEL
{
	/*
	VALID_EMAIL // 验证邮箱
	INVITE_REG // 邀请注册
	FIND_PASSWORD // 找回密码
	INVITE_QUESTION // 站外邀请
	
	FOLLOW_ME // 有人关注的了我
	QUESTION_INVITE // 有人邀请我回复问题
	NEW_ANSWER // 我关注的问题有新回复
	NEW_MESSAGE // 有人向我发送私信
	QUESTION_MOD // 问题被修改
	QUESTION_DEL // 问题被删除
	*/
	
	public function action_email($action, $email, $link, $data = array())
	{
		if (!H::valid_email($email))
		{
			$user_info = $this->model('account')->get_user_info_by_uid($email);
			
			if ($user_info['email_settings'][$action] == 'N')
			{
				return false;
			}
			
			$email = $user_info['email'];
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
		
		if (in_array($action, array(
			'VALID_EMAIL',
			'INVITE_REG',
			'FIND_PASSWORD',
		)))
		{
			return $this->send($email, $subject, $message, $link, $link_title);
		}
		else
		{
			return $this->insert('mail_queue', array(
				'send_to' => $email,
				'subject' => $subject,
				'message' => $this->get_mail_template($user_info['user_name'], $subject, $message, $link, $link_title)
			));
		}
	}
	
	public function get_mail_template($username, $subject, $message, $link = null, $link_title = null)
	{
		TPL::assign('username', $username);
		TPL::assign('subject', $subject);
		TPL::assign('message', $message);
		TPL::assign('link', $link);
		TPL::assign('link_title', $link_title);
		
		return TPL::output('global/email_template', false);
	}
	
	public function send($email, $subject, $message, $link = null, $link_title = null)
	{
		if (is_numeric($email))
		{			
			if (! $user_info = $this->model('account')->get_user_info_by_uid($email))
			{
				return false;
			}
			
			$username = $user_info['user_name'];
			
			$email = $user_info['email'];
		}
		
		return AWS_APP::mail()->send($email, $subject, $this->get_mail_template($username, $subject, $message, $link, $link_title), get_setting('site_name'), $username);
	}
	
	public function send_mail_queue($limit = 10)
	{
		if ($mail_queue = $this->fetch_all('mail_queue', 'is_error = 0', 'id ASC', $limit))
		{
			foreach ($mail_queue AS $key => $val)
			{
				if ($error_message = AWS_APP::mail()->send($val['send_to'], $val['subject'], $val['message'], get_setting('site_name')))
				{
					$this->shutdown_update('mail_queue', array(
						'is_error' => 1,
						'error_message' => $error_message
					), 'id = ' . $val['id']);
				}
				else
				{
					$this->delete('mail_queue', 'id = ' . $val['id']);
				}
			}
		}
	}
	
	public function mail_queue_error_clean()
	{
		return $this->delete('mail_queue', 'is_error = 1');
	}
}