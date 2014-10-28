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

class edm extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function mail_action()
	{
		if ($task = $this->model('edm')->get_task_info($_GET['id']))
		{
			echo str_replace('[EMAIL]', 'email@address.com', $task['message']);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('您所访问的资源不存在'));
		}
	}

	public function ping_action()
	{
		$param = explode('|', $_GET['id']);

		if (md5(base64_decode($param[0]) . G_SECUKEY) == $param[1] AND $param[2])
		{
			$this->model('edm')->set_task_view($param[2], base64_decode($param[0]));

			echo 'Success';
		}
	}
	
	public function unsubscription_action()
	{
		if ($_GET['id'])
		{
			$arg = explode(',', $_GET['id']);
			
			$email = base64_decode($arg[0]);
			
			$human_verify = $arg[2];
		}
		
		if (md5($email . G_SECUKEY) == $arg[1])
		{
			if ($human_verify == ip2long(fetch_ip()))
			{
				$this->model('edm')->unsubscription($email);
				
				H::redirect_msg(AWS_APP::lang()->_t('%s 退订邮件成功', $email));
			}
			else
			{
				$unsubscription_link = get_js_url('/account/edm/unsubscription/' . $arg[0] . ',' . $arg[1] . ',' . ip2long(fetch_ip()));
				
				H::redirect_msg(AWS_APP::lang()->_t('是否确认退订邮件订阅? &nbsp; ( <a href="%s">继续</a> )', $unsubscription_link));
			}
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('退订链接无效'));
		}
	}
}