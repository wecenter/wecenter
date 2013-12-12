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

class openid_weixin_class extends AWS_MODEL
{	
	public function get_user_info_by_openid($open_id)
	{
		return $this->fetch_row('users_weixin', "openid = '" . $this->quote($open_id) . "'");
	}
	
	public function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function bind_account($access_user, $access_token, $uid, $is_ajax = false)
	{
		if (! $access_user['nickname'])
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与微信通信出错, 请重新登录')));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('与微信通信出错, 请重新登录'));
			}
		}
		
		if ($openid_info = $this->get_user_info_by_uid($uid))
		{
			if ($openid_info['opendid'] != $access_user['openid'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信账号已经被其他账号绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('微信账号已经被其他账号绑定'));
				}
			}
		}
		
		return $this->add_user($uid, $access_user, $access_token);
	}
	
	public function add_user($uid, $access_user, $access_token)
	{
		return $this->insert('users_weixin', array(
			'uid' => intval($uid),
			'openid' => $access_token['openid'],
			'expires_in' => (time() + $access_token['expires_in']),
			'access_token' => $access_token['access_token'],
			'refresh_token' => $access_token['refresh_token'],
			'scope' => $access_token['scope'],
			'headimgurl' => $access_user['headimgurl'],
			'nickname' => $access_user['nickname'],
			'sex' => $access_user['sex'],
			'province' => $access_user['province'],
			'city' => $access_user['city'],
			'country' => $access_user['country'],
			'add_time' => time()
		));
	}
	
	public function update_token($openid, $access_token)
	{
		$this->update('users_weixin', array(
			'access_token' => $access_token
		), "openid = '" . $this->quote($openid) . "'");
	}
	
	public function weixin_unbind($uid)
	{
		return $this->delete('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function get_oauth_url($redirect_uri, $scope = 'snsapi_base', $state = 'STATE')
	{
		//return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . AWS_APP::config()->get('weixin')->app_id . '&redirect_uri=' . urlencode(get_js_url($redirect_uri)) . '&response_type=code&scope=' . urlencode($scope) . '&state=' . urlencode($state) . '#wechat_redirect';
		
		if (!AWS_APP::config()->get('weixin')->app_id)
		{
			return get_js_url($redirect_uri);
		}
		
		return get_js_url('/m/weixin/oauth_redirect/?uri=' . urlencode($redirect_uri) . '&scope=' . urlencode($scope) . '&state=' . urlencode($state));
	}
	
	public function redirect_url($redirect_uri)
	{
		return $this->get_oauth_url(get_js_url('/m/weixin/redirect/?redirect=' . base64_encode(get_js_url($redirect_uri))));
	}
	
	public function register($access_token, $access_user)
	{
		if (!$access_token OR !$access_user['nickname'])
		{
			return false;
		}
		
		$access_user['nickname'] = str_replace(array(
			'?', '/', '&', '=', '#'
		), '', $access_user['nickname']);
		
		if ($this->model('account')->check_username($access_user['nickname']))
		{
			$access_user['nickname'] .= '_' . rand(1, 999);
		}
		
		if ($uid = $this->model('account')->user_register($access_user['nickname'], md5(rand(111111, 999999999))))
		{
			if ($access_user['headimgurl'])
			{
				if ($avatar_stream = curl_get_contents($access_user['headimgurl']))
				{
					$avatar_location = get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($uid, '', 1) . $this->model('account')->get_avatar($uid, '', 2);
					
					$avatar_dir = str_replace(basename($avatar_location), '', $avatar_location);
					
					if ( ! is_dir($avatar_dir))
					{
						make_dir($avatar_dir);
					}
					
					if (@file_put_contents($avatar_location, $avatar_stream))
					{
						foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
						{			
							$thumb_file[$key] = $avatar_dir . $this->model('account')->get_avatar($uid, $key, 2);
							
							AWS_APP::image()->initialize(array(
								'quality' => 90,
								'source_image' => $avatar_location,
								'new_image' => $thumb_file[$key],
								'width' => $val['w'],
								'height' => $val['h']
							))->resize();	
						}
						
						$avatar_file = $this->model('account')->get_avatar($uid, null, 1) . basename($thumb_file['min']);
					}
				}
			}
			
			$this->model('account')->update_users_fields(array(
				'sex' => $access_user['sex'],
				'avatar_file' => $avatar_file
			), $uid);
			
			return $this->model('account')->get_user_info_by_uid($uid);
		}
	}
	
	public function process_client_login($token, $uid)
	{
		return $this->update('weixin_login', array(
			'uid' => $uid
		), "token = '" . intval($token) . "'");
	}
	
	public function request_client_login_token($session_id)
	{
		$this->delete('weixin_login', "session_id = '" . $this->quote($session_id) . "'");
		
		$token = rand(11111111, 99999999);
		
		while ($this->fetch_row('weixin_login', "token = " . $token))
		{
			$token = rand(11111111, 99999999);
		}
		
		$this->insert('weixin_login', array(
			'token' => $token,
			'session_id' => $session_id,
			'expire' => (time() + 300)
		));
		
		return $token;
	}
	
	public function weixin_login_process($session_id)
	{
		$weixin_login = $this->fetch_row('weixin_login', "session_id = '" . $this->quote($session_id) . "' AND expire >= " . time());
		
		if ($weixin_login['uid'])
		{
			$this->delete('weixin_login', "session_id = '" . $this->quote($session_id) . "'");
			
			return $this->model('account')->get_user_info_by_uid($weixin_login['uid']);
		}
	}
}
	