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

class openid_facebook_class extends AWS_MODEL
{
    const OAUTH2_AUTH_URL = 'https://www.facebook.com/dialog/oauth';

    const OAUTH2_TOKEN_URL = 'https://graph.facebook.com/oauth/access_token';

    const OAUTH2_DEBUG_TOKEN_URL = 'https://graph.facebook.com/debug_token';

    const OAUTH2_USER_INFO_URL = 'https://graph.facebook.com/v2.1/me';

    public $authorization_code;

    public $user_access_token;

    public $app_access_token;

    public $redirect_url;

    public $expires_time;

    public $error_msg;

    public $user_info;

    public function get_redirect_url($redirect_url, $state = null)
    {
        $args = array(
            'client_id' => get_setting('facebook_app_id'),
            'redirect_uri' => get_js_url($redirect_url),
            'response_type' => 'code',
            'scope' => 'public_profile email'
        );

        if ($state)
        {
            $args['state'] = $state;
        }

        return self::OAUTH2_AUTH_URL . '?' . http_build_query($args);
    }

    public function oauth2_login()
    {
        if (!$this->get_user_access_token() OR !$this->get_app_access_token()
            OR !$this->validate_user_access_token() OR !$this->get_user_info())
        {
            if (!$this->error_msg)
            {
                $this->error_msg = AWS_APP::lang()->_t('Facebook 登录失败');
            }

            return false;
        }

        return true;
    }

    public function get_user_access_token()
    {
        if (!$this->authorization_code)
        {
            $this->error_msg = AWS_APP::lang()->_t('authorization code 为空');

            return false;
        }

        $args = array(
            'client_id' => get_setting('facebook_app_id'),
            'client_secret' => get_setting('facebook_app_secret'),
            'code' => $this->authorization_code,
            'redirect_uri' => get_js_url($this->redirect_url)
        );

        $result = curl_get_contents(self::OAUTH2_TOKEN_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 user access token 时，与 Facebook 通信失败');

            return false;
        }

        parse_str($result, $user_access_token);

        if (!$user_access_token['access_token'])
        {
            $result = json_decode($result, true);

            $this->error_msg = ($result['error']) ? AWS_APP::lang()->_t('获取 user access token 失败，错误为：%s', $result['error']['message'])
                : AWS_APP::lang()->_t('获取 user access token 失败');

            return false;
        }

        $this->user_access_token = $user_access_token['access_token'];

        return true;
    }

    public function get_app_access_token()
    {
        $args = array(
            'client_id' => get_setting('facebook_app_id'),
            'client_secret' => get_setting('facebook_app_secret'),
            'grant_type' => 'client_credentials'
        );

        $result = curl_get_contents(self::OAUTH2_TOKEN_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 app access token 时，与 Facebook 通信失败');

            return false;
        }

        parse_str($result, $app_access_token);

        if (!$app_access_token['access_token'])
        {
            $result = json_decode($result, true);

            $this->error_msg = ($result['error']) ? AWS_APP::lang()->_t('获取 app access token 失败，错误为：%s', $result['error']['message'])
                : AWS_APP::lang()->_t('获取 app access token 失败');

            return false;
        }

        $this->app_access_token = $app_access_token['access_token'];

        return true;
    }

    public function validate_user_access_token()
    {
        if (!$this->user_access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('user access token 为空');
        }

        if (!$this->app_access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('app access token 为空');
        }

        $args = array(
            'input_token' => $this->user_access_token,
            'access_token' => $this->app_access_token
        );

        $result = curl_get_contents(self::OAUTH2_DEBUG_TOKEN_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 user access token 时，与 Facebook 通信失败');
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 user access token 失败，错误为：%s', $result['error']['message']);

            return false;
        }

        if ($result['data']['is_valid'] != true)
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 user access token 失败，错误为：%s', $result['data']['error']['message']);

            return false;
        }

        $this->expires_time = intval($result['data']['expires_at']);

        return true;
    }

    public function get_user_info()
    {
        if (!$this->user_access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('user access token 为空');

            return false;
        }

        $args = array(
            'access_token' => $this->user_access_token
        );

        $result = curl_get_contents(self::OAUTH2_USER_INFO_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料时，与 Facebook 通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料失败，错误为：%s', $result['error']['message']);

            return false;
        }

        $this->user_info = array(
            'id' => $result['id'],
            'name' => $result['name'],
            'email' => $result['email'],
            'link' => $result['link'],
            'gender' => $result['gender'],
            'locale' => $result['locale'],
            'timezone' => $result['timezone'],
            'verified' => $result['verified'],
            'picture' => 'https://graph.facebook.com/' . $result['id'] . '/picture?type=large',
            'authorization_code' => $this->authorization_code,
            'access_token' => $this->user_access_token,
            'expires_time' => $this->expires_time
        );

        return true;
    }

    public function refresh_access_token($uid)
    {
        $user_info = $this->get_facebook_user_by_uid($uid);

        if (!$user_info)
        {
            $this->error_msg = AWS_APP::lang()->_t('Facebook 账号未绑定');

            return false;
        }

        if (!$user_info['access_token'])
        {
            $this->error_msg = AWS_APP::lang()->_t('user access token 为空');

            return false;
        }

        $args = array(
            'client_id' => get_setting('facebook_app_id'),
            'client_secret' => get_setting('facebook_app_secret'),
            'refresh_token' => htmlspecialchars_decode($user_info['refresh_token']),
            'grant_type' => 'refresh_token'
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URL, 'POST', $args);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('更新 access token 时，与 Facebook 通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            if (!$result['error_description'])
            {
                $result['error_description'] = $result['error'];
            }

            $this->error_msg = AWS_APP::lang()->_t('更新 access token 失败，错误为：%s', $result['error_description']);

            return false;
        }

        $this->update('users_facebook',  array(
            'access_token' => htmlspecialchars($result['access_token']),
            'expires_time' => time() + intval($result['expires_in'])
        ), 'id = ' . $user_info['id']);

        return true;
    }

    public function bind_account($facebook_user, $uid)
    {
        if ($this->get_facebook_user_by_id($facebook_user['id']) OR $this->get_facebook_user_by_uid($uid))
        {
            return false;
        }

        return $this->insert('users_facebook', array(
            'id' => htmlspecialchars($facebook_user['id']),
            'uid' => intval($uid),
            'name' => htmlspecialchars($facebook_user['name']),
            'email' => htmlspecialchars($facebook_user['email']),
            'link' => htmlspecialchars($facebook_user['link']),
            'gender' => htmlspecialchars($facebook_user['gender']),
            'locale' => htmlspecialchars($facebook_user['locale']),
            'picture' => htmlspecialchars($facebook_user['picture']),
            'timezone' => intval($facebook_user['timezone']),
            'access_token' => htmlspecialchars($facebook_user['access_token']),
            'expires_time' => intval($facebook_user['expires_time']),
            'add_time' => time()
        ));
    }

    public function update_user_info($id, $facebook_user)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('users_facebook', array(
            'name' => htmlspecialchars($facebook_user['name']),
            'email' => htmlspecialchars($facebook_user['email']),
            'link' => htmlspecialchars($facebook_user['link']),
            'gender' => htmlspecialchars($facebook_user['gender']),
            'locale' => htmlspecialchars($facebook_user['locale']),
            'picture' => htmlspecialchars($facebook_user['picture']),
            'timezone' => intval($facebook_user['timezone']),
            'access_token' => htmlspecialchars($facebook_user['access_token']),
            'expires_time' => intval($facebook_user['expires_time'])
        ), 'id = ' . $id);
    }

    public function unbind_account($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        return $this->delete('users_facebook', 'uid = ' . $uid);
    }

    public function get_facebook_user_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $facebook_user_info;

        if (!$facebook_user_info[$id])
        {
            $facebook_user_info[$id] = $this->fetch_row('users_facebook', 'id = ' . $id);
        }

        return $facebook_user_info[$id];
    }

    public function get_facebook_user_by_uid($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        static $facebook_user_info;

        if (!$facebook_user_info[$uid])
        {
            $facebook_user_info[$uid] = $this->fetch_row('users_facebook', 'uid = ' . $uid);
        }

        return $facebook_user_info[$uid];
    }
}
