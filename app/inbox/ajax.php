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
	
	public function send_action()
	{
		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入私信内容')));
		}
			
		if (!$recipient_user = $this->model('account')->get_user_info_by_username($_POST['recipient']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('接收私信的用户不存在')));
		}
		else
		{
			$recipient_uid = $recipient_user['uid'];
		}
			
		if ($recipient_uid == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能给自己发私信')));
		}
			
		if ($recipient_user['inbox_recv'])
		{
			if (! $this->model('message')->check_recv($recipient_user['uid'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了只有 Ta 关注的人才能给 Ta 发送私信')));
			}	
		}
			
		$this->model('message')->send_message($this->user_id, $recipient_uid, null, $_POST['message']);
			
		if ($_POST['return_url'])
		{
			$rsm = array(
				'url' => get_js_url(strip_tags($_POST['return_url']))
			);
		}
		else
		{
			$rsm = array(
					'url' => get_js_url('/inbox/')
			);
		}
				
		H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
	}
}