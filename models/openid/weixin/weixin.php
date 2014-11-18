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

class openid_weixin_weixin_class extends AWS_MODEL
{
    const WEIXIN_API = 'https://api.weixin.qq.com/cgi-bin/';

    const WEIXIN_FILE_API = 'http://file.api.weixin.qq.com/cgi-bin/';

    const WEIXIN_OAUTH_API = 'https://api.weixin.qq.com/sns/';

    public function access_request($app_id, $app_secret, $url, $method, $contents = NULL)
    {
        $url = self::WEIXIN_API . $url . '?access_token=' . $this->get_access_token($app_id, $app_secret);

        $result = HTTP::request($url, $method, $contents);

        if (!$result)
        {
            return false;
        }

        $result = json_decode($result, true);

        if ($result['errcode'] == 40001)
        {
            $this->refresh_access_token($app_id, $app_secret);
        }

        return $result;
    }

    public function refresh_access_token($app_id, $app_secret)
    {
        if (!$app_id OR !$app_secret)
        {
            return false;
        }

        $cached_token = 'weixin_access_token_' . md5($app_id . $app_secret);

        AWS_APP::cache()->delete($cached_token);

        return $this->get_access_token($app_id, $app_secret);
    }

    public function get_access_token($app_id, $app_secret)
    {
        if (!$app_id OR !$app_secret)
        {
            return false;
        }

        $cached_token = 'weixin_access_token_' . md5($app_id . $app_secret);

        $access_token = AWS_APP::cache()->get($cached_token);

        if ($access_token)
        {
            return $access_token;
        }

        $result = curl_get_contents(self::WEIXIN_API . 'token?grant_type=client_credential&appid=' . $app_id . '&secret=' . $app_secret);

        if (!$result)
        {
            return false;
        }

        $result = json_decode($result, true);

        if (!$result['access_token'])
        {
            return false;
        }

        AWS_APP::cache()->set($cached_token, $result['access_token'], 60);

        return $result['access_token'];
    }

    public function get_user_info_by_openid_from_weixin($openid)
    {
        $result = curl_get_contents(self::WEIXIN_API . 'user/info?access_token=' . $this->get_access_token(get_setting('weixin_app_id'), get_setting('weixin_app_secret')) . '&openid=' . $openid);

        if (!$result)
        {
            return false;
        }

        return json_decode($result, true);
    }

    public function get_user_info_by_oauth_openid($access_token, $openid)
    {
        $result = curl_get_contents(self::WEIXIN_OAUTH_API . 'userinfo?access_token=' . $access_token . '&openid=' . $openid);

        if (!$result)
        {
            return false;
        }

        return json_decode($result, true);
    }

    public function get_sns_access_token_by_authorization_code($code)
    {
        $cache_process_key = 'processing_weixin_sns_access_token_' . md5($code);
        $cache_key = 'weixin_sns_access_token_' . md5($code);

        // 防止页面被二次访问导致 Code 失效，最多等待 60 秒
        if (AWS_APP::cache()->get($cache_process_key))
        {
            return $this->get_sns_access_token_by_authorization_code($code);
        }

        $sns_access_token = AWS_APP::cache()->get($cache_key);

        if ($sns_access_token)
        {
            return $sns_access_token;
        }

        AWS_APP::cache()->set($cache_process_key, time(), 60);

        $result = curl_get_contents(self::WEIXIN_OAUTH_API . 'oauth2/access_token?appid=' . get_setting('weixin_app_id') . '&secret=' . get_setting('weixin_app_secret') . '&code=' . $code . '&grant_type=authorization_code');

        if (!$result)
        {
            return false;
        }

        $result = json_decode($result, true);

        if (!$sns_access_token['errcode'])
        {
            AWS_APP::cache()->set($cache_key, $sns_access_token, 60);
        }

        AWS_APP::cache()->delete($cache_process_key);

        return $result;
    }

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

            return true;
        }

        $this->associate_avatar($uid, $access_user['headimgurl']);

        $this->insert('users_weixin', array(
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

        $this->model('account')->associate_remote_avatar($uid, $access_user['headimgurl']);

        return true;
    }

    public function weixin_unbind($uid)
    {
        return $this->delete('users_weixin', 'uid = ' . intval($uid));
    }

    public function get_oauth_url($redirect_uri, $scope = 'snsapi_base', $state = 'STATE')
    {
        return get_js_url('/m/weixin/oauth_redirect/?uri=' . urlencode($redirect_uri) . '&scope=' . urlencode($scope) . '&state=' . urlencode($state));
    }

    public function redirect_url($redirect_uri)
    {
        if (!get_setting('weixin_app_id') OR !get_setting('weixin_app_secret') OR get_setting('weixin_account_role') != 'service')
        {
            return get_js_url($redirect_uri);
        }

        return $this->get_oauth_url(get_js_url('/m/weixin/redirect/?redirect=' . base64_encode(get_js_url($redirect_uri))));
    }

    public function associate_avatar($uid, $headimgurl)
    {
        if ($headimgurl)
        {
            if (!$user_info = $this->model('account')->get_user_info_by_uid($uid))
            {
                return false;
            }

            if ($user_info['avatar_file'])
            {
                return false;
            }

            if ($avatar_stream = curl_get_contents($headimgurl, 1))
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

        if ($avatar_file)
        {
            return $this->model('account')->update('users', array(
                'avatar_file' => $avatar_file
            ), 'uid = ' . intval($uid));
        }
    }

    public function register_user($access_token, $access_user)
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
            $this->associate_avatar($uid, $access_user['headimgurl']);

            $this->model('account')->associate_remote_avatar($uid, $access_user['headimgurl']);

            $this->model('account')->update('users', array(
                'sex' => intval($access_user['sex'])
            ), 'uid = ' . intval($uid));

            return $this->model('account')->get_user_info_by_uid($uid);
        }
    }

    public function weixin_auto_register($access_token, $access_user)
    {
        if ($user_info = $this->register_user($access_token, $access_user))
        {
            $this->bind_account($access_user, $access_token, $user_info['uid']);

            HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], null, false));

            return true;
        }

        return false;
    }

    public function process_client_login($token, $uid)
    {
        if ($this->fetch_row('weixin_login', 'uid = ' . intval($uid) . " AND token = '" . intval($token) . "'"))
        {
            return true;
        }

        return $this->update('weixin_login', array(
            'uid' => intval($uid)
        ), "token = '" . intval($token) . "'");
    }

    public function request_client_login_token($session_id)
    {
        $this->delete('weixin_login', "session_id = '" . $this->quote($session_id) . "'");
        $this->delete('weixin_login', 'expire <' . time());

        $token = rand(11111111, 99999999);

        if ($this->fetch_row('weixin_login', "token = " . $token))
        {
            return $this->request_client_login_token($session_id);
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

    public function upload_file($file, $type)
    {
        $app_id = get_setting('weixin_app_id');

        $app_secret = get_setting('weixin_app_secret');

        $file = realpath($file);

        if (!is_readable($file))
        {
            return false;
        }

        $file_md5 = md5_file($file);

        $cached_result = AWS_APP::cache()->get('weixin_upload_file_' . $file_md5);

        if ($cached_result)
        {
            return $cached_result;
        }

        $post_data = array(
            'media' => '@' . $file
        );

        $result = HTTP::request(self::WEIXIN_FILE_API . 'media/upload?access_token=' . $this->get_access_token($app_id, $app_secret) . '&type=' . $type, 'POST', $post_data);

        if (!$result)
        {
            return false;
        }

        $result = json_decode($result, true);

        if ($result['errcode'])
        {
            if ($result['errcode'] == 40001)
            {
                $this->refresh_access_token($app_id, $app_secret);

                return $this->upload_file($file, $type);
            }
        }
        else
        {
            AWS_APP::cache()->set('weixin_upload_file_' . $file_md5, $result, 259200);
        }

        return $result;
    }

    public function get_file($media_id)
    {
        $app_id = get_setting('weixin_app_id');

        $app_secret = get_setting('weixin_app_secret');

        $media_id_md5 = md5($media_id);

        $cached_file = AWS_APP::cache()->get('weixin_get_file_' . $media_id_md5);

        if ($cached_file)
        {
            return $cached_file;
        }

        $file = curl_get_contents(self::WEIXIN_FILE_API . 'media/get?access_token=' . $this->get_access_token($app_id, $app_secret) . '&media_id=' . $media_id);

        if ($file)
        {
            $result = json_decode($file, true);

            if ($result)
            {
                if ($result['errcode'] == 40001)
                {
                    $this->refresh_access_token($app_id, $app_secret);
                }

                return $result;
            }

            AWS_APP::cache()->set('weixin_get_file_' . $media_id_md5, $file, 259200);

            return $file;
        }
    }
}
