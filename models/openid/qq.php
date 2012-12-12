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

class openid_qq_class extends AWS_MODEL
{
	var $qq_auth_host = 'https://graph.qq.com';
	
	function get_code_url($callback)
	{
		return $this->qq_auth_host . '/oauth2.0/authorize?response_type=code&client_id=' . get_setting('qq_login_app_id') . '&redirect_uri=' . urlencode($callback) . '&scope=';
	}
	
	function request_access_token($code, $callback)
	{
		$token_url = $this->qq_auth_host . '/oauth2.0/token?grant_type=authorization_code&client_id=' . get_setting('qq_login_app_id') . '&client_secret=' . get_setting('qq_login_app_key') . '&code=' . $code . '&state=1&redirect_uri=' . urlencode($callback);
		
		$response = HTTP::request($token_url, 'GET');
		
		if ($response)
		{
			parse_str($response, $query);
			
			return $query['access_token'];
		}
		else
		{
			return false;
		}
	}
	
	function request_user_info_by_token($access_token)
	{
		$response = HTTP::request($this->qq_auth_host . '/oauth2.0/me?access_token=' . $access_token, 'GET');
		
		if (strpos($response, "callback") !== false)
		{
			$lpos = strpos($response, "(");
			$rpos = strrpos($response, ")");
			$response = substr($response, $lpos + 1, $rpos - $lpos - 1);
			$msg = json_decode($response);
			
			if (isset($msg->error))
			{
				echo "<h3>error:</h3>" . $msg->error;
				echo "<h3>msg  :</h3>" . $msg->error_description;
				exit();
			}
			
			$response = HTTP::request($this->qq_auth_host . '/user/get_user_info?access_token=' . $access_token . '&oauth_consumer_key=' . get_setting('qq_login_app_id') . '&openid=' . $msg->openid, 'GET');
			
			$data = (array)json_decode($response);
			
			$data['openid'] = $msg->openid;
			
			return $data;
		}
		else
		{
			return false;
		}
	}
	
	function get_user_info_by_open_id($open_id)
	{
		return $this->fetch_row('users_qq', 'type = \'qq\' AND name = \'' . $this->quote($open_id) . '\'');
	}
	
	function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_qq', 'type = \'qq\' AND uid = ' . intval($uid));
	}
	
	function add_share($access_token, $openid, $title, $url, $comment = '', $summary = '', $images = '', $source = 1, $type = 4, $playurl = '')
	{
		$data = array(
			'access_token' => $access_token,
			'oauth_consumer_key' => get_setting('qq_login_app_id'),
			'openid' => $openid,
			'format' => 'json',
			'title' => $title,
			'url' => $url,
			'comment' => $comment,
			'summary' => $summary,
			'source' => $source,
			'type' => $type,
			'site' => get_setting('base_url'),
		);
		
		$r = HTTP::request($this->qq_auth_host . '/share/add_share', 'POST', $data);
		
		return (array)json_decode($r);
	}

	function bind_account($uinfo, $redirect, $uid, $is_ajax = false)
	{
		if ($openid_info = $this->get_user_info_by_uid($uid))
		{
			if ($openid_info['name'] != $uinfo['openid'])
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
		
		if (! $users_qq = $this->get_user_info_by_open_id($uinfo['openid']))
		{
			if($uinfo['gender'] == '男')
			{
				$uinfo['gender'] = 'm';
			}
			else if($uinfo['gender'] == '女')
			{
				$uinfo['gender'] = 'f';
			}
			else
			{
				$uinfo['gender'] = 'n';
			}
			
			$users_qq = $this->user_add($uid, $uinfo['openid'], $uinfo['nickname'], $uinfo['gender']);
			
			//第一次绑定，将头像迁移过来
		}
		else if ($users_qq['uid'] != $uid)
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", 'QQ 已经被其他账号绑定'));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('QQ 已经被其他账号绑定'), '/account/setting/openid/');
			}
		}
		
		$this->update_token($uinfo['openid'], $_SESSION['qq_access_token']);
		
		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
	}
	
	function user_add($uid, $name, $nick, $gender)
	{
		$data = array(
			'type' => 'qq',
			'uid' => intval($uid),
			'name' => $name,
			'nick' => $nick,
			'gender' => $gender,
			'add_time' => time(),
		);
		
		return $this->insert('users_qq', $data);
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
	