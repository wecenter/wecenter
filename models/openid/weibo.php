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

	function refresh_access_token($id, $sina_token)
	{
		if (empty($sina_token['access_token']))
		{
			return false;
		}

		return $this->update('users_sina', array(
					'access_token' => $sina_token['access_token'],
					'expires_time' => time() + $sina_token['expires_in']
				), 'id = ' . $this->quote($id));
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

	function bind_account($sina_profile, $redirect, $uid, $sina_token, $is_ajax = false)
	{
		if ($openid_info = $this->get_users_sina_by_uid($uid))
		{
			if ($openid_info['id'] != $sina_profile['id'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('此微博已经与本站的另外一个账号绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('此微博已经与本站的另外一个账号绑定'), '/account/logout/');
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
				H::redirect_msg(AWS_APP::lang()->_t('此账号已经与另外一个微博绑定'), $redirect);
			}
		}

		$this->refresh_access_token($sina_profile['id'], $sina_token);

		$tmp_service_account = AWS_APP::cache()->get('tmp_service_account');

		if ($tmp_service_account[$uid])
		{
			$this->model('weibo')->update_service_account($uid, 'add');

			unset($tmp_service_account[$uid]);

			AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);
		}

		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('绑定成功'));
		}
	}

	public function get_msg_from_sina($access_token, $since_id = 0, $max_id = 0)
	{
		$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $access_token);

		$result = $client->mentions(1, 100, $since_id, $max_id);

		if ($result['error'])
		{
			return $result;
		}

		if (empty($new_msgs))
		{
			return false;
		}

		$msgs = $result['statuses'];

/*
		do
		{
			$result = $client->mentions(1, 200, $since_id, $max_id);

			if ($result['error'])
			{
				return $result;
			}

			$new_msgs = $result['statuses'];

			if (empty($new_msgs))
			{
				break;
			}

			if (empty($msgs))
			{
				$msgs = $new_msgs;
			}
			else
			{
				$msgs = array_merge($msgs, $new_msgs);
			}

			$new_msgs_total = count($new_msgs);

			if ($new_msgs_total < 200)
			{
				break;
			}
			else
			{
				$last_msg = end($new_msgs);

				$max_id = $last_msg['id'] - 1;
			}
		}
		while (0);
*/
		return $msgs;
	}

	public function create_comment($access_token, $id, $comment)
	{
		$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $access_token);

		return $client->send_comment($id, cjk_substr($comment, 0, 140, 'UTF-8', '...'));
	}
}
