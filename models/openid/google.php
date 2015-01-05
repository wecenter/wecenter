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

class openid_google_class extends AWS_MODEL
{
    const OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';

    const OAUTH2_TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';

    const OAUTH2_TOKEN_VALIDATION_URL = 'https://www.googleapis.com/oauth2/v2/tokeninfo';

    const OAUTH2_USER_INFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public $authorization_code;

    public $access_token;

    public $redirect_url;

    public $refresh_token;

    public $expires_time;

    public $error_msg;

    public $user_info;

    public function get_redirect_url($redirect_url, $state = null)
    {
        $args = array(
            'client_id' => get_setting('google_client_id'),
            'redirect_uri' => get_js_url($redirect_url),
            'response_type' => 'code',
            'scope' => 'profile email'
        );

        if ($state)
        {
            $args['state'] = $state;
        }

        return self::OAUTH2_AUTH_URL . '?' . http_build_query($args);
    }

    public function oauth2_login()
    {
        if (!$this->get_access_token() OR !$this->validate_access_token() OR !$this->get_user_info())
        {
            if (!$this->error_msg)
            {
                $this->error_msg = AWS_APP::lang()->_t('Google 登录失败');
            }

            return false;
        }

        return true;
    }

    public function get_access_token()
    {
        if (!$this->authorization_code)
        {
            $this->error_msg = AWS_APP::lang()->_t('authorization code 为空');

            return false;
        }

        $args = array(
            'client_id' => get_setting('google_client_id'),
            'client_secret' => get_setting('google_client_secret'),
            'code' => $this->authorization_code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => get_js_url($this->redirect_url)
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URL, 'POST', $args);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 时，与 Google 通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            if (!$result['error_description'])
            {
                $result['error_description'] = $result['error'];
            }

            $this->error_msg = AWS_APP::lang()->_t('获取 access token 失败，错误为：%s', $result['error_description']);

            return false;
        }

        $this->access_token = $result['access_token'];

        $this->refresh_token = $result['refresh_token'];

        return true;
    }

    public function validate_access_token()
    {
        if (!$this->access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('access token 为空');
        }

        $result = curl_get_contents(self::OAUTH2_TOKEN_VALIDATION_URL . '?access_token=' . $this->access_token);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 access token 时，与 Google 通信失败');
        }

        $result = json_decode($result, true);

        if ($result['error_description'])
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 access token 失败，错误为：%s', $result['error_description']);
        }

        $this->expires_time = time() + intval($result['expires_in']);

        return true;
    }

    public function get_user_info()
    {
        if (!$this->access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('access token 为空');

            return false;
        }

        $header = array('Authorization: Bearer ' . $this->access_token);

        $result = HTTP::request(self::OAUTH2_USER_INFO_URL, 'GET', null, 10, $header);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料时，与 Google 通信失败');

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
            'locale' => $result['locale'],
            'picture' => $result['picture'],
            'gender' => $result['gender'],
            'email' => $result['email'],
            'link' => $result['link'],
            'verified_email' => $result['verified_email'],
            'authorization_code' => $this->authorization_code,
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_time' => $this->expires_time
        );

        return true;
    }

    public function refresh_access_token($uid)
    {
        $user_info = $this->get_google_user_by_uid($uid);

        if (!$user_info)
        {
            $this->error_msg = AWS_APP::lang()->_t('Google 账号未绑定');

            return false;
        }

        if (!$user_info['refresh_token'])
        {
            $this->error_msg = AWS_APP::lang()->_t('refresh token 为空');

            return false;
        }

        $args = array(
            'client_id' => get_setting('google_client_id'),
            'client_secret' => get_setting('google_client_secret'),
            'refresh_token' => htmlspecialchars_decode($user_info['refresh_token']),
            'grant_type' => 'refresh_token'
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URL, 'POST', $args);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('更新 access token 时，与 Google 通信失败');

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

        $this->update('users_google',  array(
            'access_token' => htmlspecialchars($result['access_token']),
            'expires_time' => time() + intval($result['expires_in'])
        ), 'id = ' . $user_info['id']);

        return true;
    }

    public function bind_account($google_user, $uid)
    {
        if ($this->get_google_user_by_id($google_user['id']) OR $this->get_google_user_by_uid($uid))
        {
            return false;
        }

        return $this->insert('users_google', array(
            'id' => htmlspecialchars($google_user['id']),
            'uid' => intval($uid),
            'name' => htmlspecialchars($google_user['name']),
            'locale' => htmlspecialchars($google_user['locale']),
            'picture' => htmlspecialchars($google_user['picture']),
            'gender' => htmlspecialchars($google_user['gender']),
            'email' => htmlspecialchars($google_user['email']),
            'link' => htmlspecialchars($google_user['link']),
            'access_token' => htmlspecialchars($google_user['access_token']),
            'refresh_token' => htmlspecialchars($google_user['refresh_token']),
            'expires_time' => intval($google_user['expires_time']),
            'add_time' => time()
        ));
    }

    public function update_user_info($id, $google_user)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('users_google', array(
            'name' => htmlspecialchars($google_user['name']),
            'locale' => htmlspecialchars($google_user['locale']),
            'picture' => htmlspecialchars($google_user['picture']),
            'gender' => htmlspecialchars($google_user['gender']),
            'email' => htmlspecialchars($google_user['email']),
            'link' => htmlspecialchars($google_user['link']),
            'access_token' => htmlspecialchars($google_user['access_token']),
            'refresh_token' => htmlspecialchars($google_user['refresh_token']),
            'expires_time' => intval($google_user['expires_time'])
        ), 'id = ' . $id);
    }

    public function unbind_account($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        return $this->delete('users_google', 'uid = ' . $uid);
    }

    public function get_google_user_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $google_user_info;

        if (!$google_user_info[$id])
        {
            $google_user_info[$id] = $this->fetch_row('users_google', 'id = ' . $id);
        }

        return $google_user_info[$id];
    }

    public function get_google_user_by_uid($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        static $google_user_info;

        if (!$google_user_info[$uid])
        {
            $google_user_info[$uid] = $this->fetch_row('users_google', 'uid = ' . $uid);
        }

        return $google_user_info[$uid];
    }
}
