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

class openid_twitter_class extends AWS_MODEL
{
    const OAUTH_REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';

    const OAUTH_AUTH_URL = 'https://api.twitter.com/oauth/authenticate';

    const OAUTH_ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

    const OAUTH_AUTH_VALIDATION_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';

    public $error_msg;

    public $oauth_callback;

    public $request_token;

    public $redirect_url;

    public $access_token;

    public $user_info;

    public function build_auth_header($args = array(), $request_url, $request_method)
    {
        if (isset($args) AND !is_array($args))
        {
            return false;
        }

        if (isset($args['oauth_token_secret']))
        {
            $oauth_token_secret = $args['oauth_token_secret'];

            unset($args['oauth_token_secret']);
        }

        $timestamp = time();

        $args['oauth_consumer_key'] = get_setting('twitter_consumer_key');

        $args['oauth_nonce'] = base64_encode(md5($timestamp));

        $args['oauth_signature_method'] = 'HMAC-SHA1';

        $args['oauth_timestamp'] = $timestamp;

        $args['oauth_version'] = '1.0';

        ksort($args);

        $parm_str = http_build_query($args, '', '&', PHP_QUERY_RFC3986);

        $sign_base_str = $request_method . '&' . rawurlencode($request_url) . '&' . rawurlencode($parm_str);

        $sign_key = rawurlencode(get_setting('twitter_consumer_secret')) . '&' . rawurlencode($oauth_token_secret);

        $args['oauth_signature'] = base64_encode(hash_hmac('sha1', $sign_base_str, $sign_key, true));

        ksort($args);

        $auth_header = 'Authorization: OAuth ';

        foreach ($args AS $key => $value)
        {
            $auth_header .= $key . '="' . rawurlencode($value) . '", ';
        }

        return substr($auth_header, 0, strlen($auth_header) - 2);
    }

    public function oauth_redirect()
    {
        if (!$this->get_request_token() OR !$this->user_authentication())
        {
            if (!$this->error_msg)
            {
                $this->error_msg = AWS_APP::lang()->_t('Twitter 登录失败');
            }

            return false;
        }

        return true;
    }

    public function get_request_token()
    {
        $args = array(
            'oauth_callback' => get_js_url($this->oauth_callback)
        );

        $header = array($this->build_auth_header($args, self::OAUTH_REQUEST_TOKEN_URL, 'POST'));

        $result = HTTP::request(self::OAUTH_REQUEST_TOKEN_URL, 'POST', null, 10, $header);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 request token 时，与 Twitter 通信失败');

            return false;
        }

        parse_str($result, $this->request_token);

        if (!$this->request_token['oauth_token'] OR !$this->request_token['oauth_token_secret'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 request token 失败');

            return false;
        }

        if ($this->request_token['oauth_callback_confirmed'] != 'true')
        {
            $this->error_msg = AWS_APP::lang()->_t('callback url 设置错误');

            return false;
        }

        return true;
    }

    public function user_authentication()
    {
        $this->redirect_url = self::OAUTH_AUTH_URL . '?oauth_token=' . $this->request_token['oauth_token'];

        return true;
    }

    public function get_user_info()
    {
        if (!$this->get_access_token() OR !$this->verify_credentials())
        {
            if (!$this->error_msg)
            {
                $this->error_msg = AWS_APP::lang()->_t('Twitter 登录失败');
            }

            return false;
        }

        return true;
    }

    public function get_access_token()
    {
        $args = array(
            'oauth_token' => $this->request_token['oauth_token'],
            'oauth_token_secret' => $this->request_token['oauth_token_secret']
        );

        $header = array($this->build_auth_header($args, self::OAUTH_ACCESS_TOKEN_URL, 'POST'));

        $result = HTTP::request(self::OAUTH_ACCESS_TOKEN_URL, 'POST', 'oauth_verifier=' . $this->request_token['oauth_verifier'], 10, $header);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 时，与 Twitter 通信失败');

            return false;
        }

        parse_str($result, $this->access_token);

        if (!$this->access_token['oauth_token'] OR !$this->access_token['oauth_token_secret'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 失败');

            return false;
        }

        return true;
    }

    public function verify_credentials()
    {
        $args = array(
            'oauth_token' => $this->access_token['oauth_token'],
            'oauth_token_secret' => $this->access_token['oauth_token_secret']
        );

        $header = array($this->build_auth_header($args, self::OAUTH_AUTH_VALIDATION_URL, 'GET'));

        $result = HTTP::request(self::OAUTH_AUTH_VALIDATION_URL, 'GET', null, 10, $header);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料时，与 Twitter 通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['errors'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料失败，错误为：%s', $result['errors']['message']);

            return false;
        }

        $this->user_info = array(
            'id' => $result['id'],
            'name' => $result['name'],
            'screen_name' => $result['screen_name'],
            'location' => $result['location'],
            'time_zone' => $result['time_zone'],
            'lang' => $result['lang'],
            'profile_image_url' => str_ireplace('_normal.', '_400x400.', $result['profile_image_url']),
            'access_token' => array(
                'oauth_token' => $this->access_token['oauth_token'],
                'oauth_token_secret' => $this->access_token['oauth_token_secret']
            )
        );

        return true;
    }

    public function bind_account($twitter_user, $uid)
    {
        if ($this->get_twitter_user_by_id($twitter_user['id']) OR $this->get_twitter_user_by_uid($uid))
        {
            return false;
        }

        return $this->insert('users_twitter', array(
            'id' => intval($twitter_user['id']),
            'uid' => intval($uid),
            'name' => htmlspecialchars($twitter_user['name']),
            'screen_name' => htmlspecialchars($twitter_user['screen_name']),
            'location' => htmlspecialchars($twitter_user['location']),
            'time_zone' => htmlspecialchars($twitter_user['time_zone']),
            'lang' => htmlspecialchars($twitter_user['lang']),
            'profile_image_url' => htmlspecialchars(str_ireplace('_normal.', '_400x400.', $twitter_user['profile_image_url'])),
            'access_token' => serialize(array(
                'oauth_token' => htmlspecialchars($twitter_user['access_token']['oauth_token']),
                'oauth_token_secret' => htmlspecialchars($twitter_user['access_token']['oauth_token_secret'])
            )),
            'add_time' => time()
        ));
    }

    public function update_user_info($id, $twitter_user)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('users_twitter', array(
            'name' => htmlspecialchars($twitter_user['name']),
            'screen_name' => htmlspecialchars($twitter_user['screen_name']),
            'location' => htmlspecialchars($twitter_user['location']),
            'time_zone' => htmlspecialchars($twitter_user['time_zone']),
            'lang' => htmlspecialchars($twitter_user['lang']),
            'profile_image_url' => htmlspecialchars(str_ireplace('_normal.', '_400x400.', $twitter_user['profile_image_url'])),
            'access_token' => serialize(array(
                'oauth_token' => htmlspecialchars($twitter_user['access_token']['oauth_token']),
                'oauth_token_secret' => htmlspecialchars($twitter_user['access_token']['oauth_token_secret'])
            ))
        ), 'id = ' . $id);
    }

    public function unbind_account($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        return $this->delete('users_twitter', 'uid = ' . $uid);
    }

    public function get_twitter_user_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $twitter_user_info;

        if (!$twitter_user_info[$id])
        {
            $twitter_user_info[$id] = $this->fetch_row('users_twitter', 'id = ' . $id);
        }

        return $twitter_user_info[$id];
    }

    public function get_twitter_user_by_uid($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        static $twitter_user_info;

        if (!$twitter_user_info[$uid])
        {
            $twitter_user_info[$uid] = $this->fetch_row('users_twitter', 'uid = ' . $uid);
        }

        return $twitter_user_info[$uid];
    }
}
