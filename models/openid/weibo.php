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

class openid_weibo_class extends AWS_MODEL
{
	function check_sina_id($sina_id)
	{
		return $this->count('users_sina', "id = '" . $this->quote($sina_id) . "'");
	}

	function get_users_sina_by_id($sina_id)
	{
		return $this->fetch_row('users_sina', "id = '" . $this->quote($sina_id) . "'");
	}

	function get_users_sina_by_uid($uid)
	{
		return $this->fetch_row('users_sina', 'uid = ' . intval($uid));
	}

	//function update_token($id, $access_token, $oauth_token_secret)
	function update_token($id, $access_token)
	{
		return $this->update('users_sina', array(
			'access_token' => $this->quote($access_token),
			//'oauth_token' => $this->quote($access_token), 
			//'oauth_token_secret' => $this->quote($oauth_token_secret)
		), "id = '" . $this->quote($id) . "'");
	}

	function del_users_by_uid($uid)
	{
		return $this->delete('users_sina', 'uid = ' . intval($uid));
	}

	function users_sina_add($id, $uid, $name, $location, $description, $url, $profile_image_url, $gender)
	{
		$uid = intval($uid);
		
		if (! $uid or ! $id)
		{
			return false;
		}
		
		$data['id'] = $id;
		$data['uid'] = intval($uid);
		$data['name'] = htmlspecialchars($name);
		$data['location'] = htmlspecialchars($location);
		$data['description'] = htmlspecialchars($description);
		$data['url'] = htmlspecialchars($url);
		$data['profile_image_url'] = htmlspecialchars($profile_image_url);
		$data['gender'] = htmlspecialchars($gender);
		$data['add_time'] = time();
		
		return $this->insert('users_sina', $data);
	
	}

	function bind_account($sina_profile, $redirect, $uid, $is_ajax = false)
	{		
		if ($openid_info = $this->get_users_sina_by_uid($uid))
		{
			if ($openid_info['id'] != $sina_profile['id'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此账号已经与另外一个微博绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('此账号已经与另外一个微博绑定'), '/account/logout/');
				}
			
			}
		}
		
		if (! $user_sina = $this->get_users_sina_by_id($sina_profile['id']))
		{
			$this->users_sina_add($sina_profile['id'], $uid, $sina_profile['screen_name'], $sina_profile['location'], $sina_profile['description'], $sina_profile['profile_url'], $sina_profile['profile_image_url'], $sina_profile['gender']);
		
		}
		else if ($user_sina['uid'] != $uid)
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此账号已经与另外一个微博绑定')));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('此账号已经与另外一个微博绑定'), '/account/setting/openid/');
			}
		}
		
		if (AWS_APP::session()->sina_token['access_token'])
		{
			$this->update_token($sina_profile['id'], AWS_APP::session()->sina_token['access_token']);
		}
		
		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
	}
}
	