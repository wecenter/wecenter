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
		return $this->count('users_sina', 'id = ' . $this->quote($sina_id));
	}

	function get_users_sina_by_id($sina_id)
	{
		return $this->fetch_row('users_sina', 'id = ' . $this->quote($sina_id));
	}

	function get_users_sina_by_uid($uid)
	{
		return $this->fetch_row('users_sina', 'uid = ' . intval($uid));
	}

	function update_user_info($id, $user_info)
	{
		return $this->update('users_sina', $user_info, 'id = ' . $this->quote($id));
	}

	function del_users_by_uid($uid)
	{
		return $this->delete('users_sina', 'uid = ' . intval($uid));
	}

	function users_sina_add($id, $uid, $name, $location, $description, $url, $profile_image_url, $gender)
	{
		if (empty($uid) OR empty($id))
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

		$user_sina = $this->get_users_sina_by_id($sina_profile['id']);

		if (empty($user_sina))
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
			$this->update_user_info($sina_profile['id'], array(
				'access_token' => AWS_APP::session()->sina_token['access_token'],
				'expires_time' => time() + AWS_APP::session()->sina_token['expires_in']
			));
		}

		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
	}

	public function get_msg_from_sina($access_token, $since_id = 0, $max_id = 0)
	{
	    $client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $access_token);

	    do
	    {
	        $result = json_decode($client->mentions(1, 200, $since_id, $max_id), true);

	        if ($result['error'])
	        {
	            return $result;
	        }

	        $new_msgs = $result['statuses'];

	        $new_msgs_total = count($new_msgs);

	        if ($new_msgs_total == 0)
	        {
	            return false;
	        }

	        $msgs = array_merge($msgs, $new_msgs);

	        $max_id = $msgs[200]['id'] - 1;
	    }
	    while ($new_msgs_total < 200);

	    return $msgs;
	}

	public function create_comment($access_token, $id, $comment)
	{
		$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $access_token);

		$result = $client->send_comment($id, cjk_substr($comment, 0, 140, 'UTF-8', '...'));

		return $result;
	}
}
