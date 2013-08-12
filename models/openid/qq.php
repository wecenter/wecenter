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

class openid_qq_class extends AWS_MODEL
{	
	function qq_login($callback)
	{		
		return load_class('Services_Tencent_QQConnect_V2')->qq_login(get_setting('qq_login_app_id'), $callback);
	}
	
	function request_access_token($callback)
	{
		return load_class('Services_Tencent_QQConnect_V2')->qq_callback(get_setting('qq_login_app_id'), $callback, get_setting('qq_login_app_key'));
	}
	
	function request_user_info()
	{
		return load_class('Services_Tencent_QQConnect_V2')->get_user_info();
	}
	
	function get_user_info_by_open_id($open_id)
	{
		return $this->fetch_row('users_qq', 'type = \'qq\' AND name = \'' . $this->quote($open_id) . '\'');
	}
	
	function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_qq', 'type = \'qq\' AND uid = ' . intval($uid));
	}
	
	function bind_account($uinfo, $redirect, $uid, $is_ajax = false)
	{
		if (! $openid = load_class('Services_Tencent_QQConnect_V2')->get_openid())
		{
			
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录')));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('与 QQ 通信出错, 请重新登录'), '/account/logout/');
			}
		}
		
		if ($openid_info = $this->get_user_info_by_uid($uid))
		{
			if ($openid_info['name'] != $openid)
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('QQ 账号已经被其他账号绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('QQ 账号已经被其他账号绑定'), '/account/logout/');
				}
			}
		}
		
		if (! $users_qq = $this->get_user_info_by_open_id($openid))
		{
			if ($uinfo['gender'] == '男')
			{
				$uinfo['gender'] = 'm';
			}
			else if ($uinfo['gender'] == '女')
			{
				$uinfo['gender'] = 'f';
			}
			else
			{
				$uinfo['gender'] = 'n';
			}
			
			$users_qq = $this->user_add($uid, $openid, $uinfo['nickname'], $uinfo['gender']);
		}
		else if ($users_qq['uid'] != $uid)
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', 'QQ 已经被其他账号绑定'));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('QQ 已经被其他账号绑定'), '/account/setting/openid/');
			}
		}
		
		$this->update_token($openid, AWS_APP::session()->QQConnect['access_token']);
		
		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
	}
	
	function user_add($uid, $name, $nick, $gender)
	{
		return $this->insert('users_qq', array(
			'type' => 'qq',
			'uid' => intval($uid),
			'name' => $name,
			'nick' => $nick,
			'gender' => $gender,
			'add_time' => time(),
		));
	}
	
	function update_token($openid, $access_token)
	{
		$this->update('users_qq', array(
			'access_token' => $access_token
		), 'type = \'qq\' AND name = \'' . $this->quote($openid) . '\'');
	}
	
	function del_user_by_uid($uid)
	{
		return $this->delete('users_qq', "type = 'qq' AND uid = " . intval($uid));
	}
}
	