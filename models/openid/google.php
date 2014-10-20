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

    const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';

    const OAUTH2_TOKEN_VALIDATION_URL = 'https://www.googleapis.com/oauth2/v2/tokeninfo';

    const OAUTH2_USER_INFO_URL 'https://www.googleapis.com/oauth2/v2/userinfo';

    public $user_info;

    public function get_redirect_url($redirect_uri)
    {
        $args = array(
            'client_id' => get_setting('google_client_id'),
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'profile email'
        );

        return self::OAUTH2_AUTH_URL . '?' .http_build_query($args);
    }

    public function get_access_token($code, $redirect_uri)
    {
        if (!$code)
        {
            return 'authorization code 为空';
        }

        $args = array(
            'client_id' => get_setting('google_client_id'),
            'client_secret' => get_setting('google_client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URI, 'POST', $args);

        if (!$result)
        {
            return '获取 access token 时，与 Google 通信失败';
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            return '获取 access token 失败，错误为：' . ($result['error_description']) ? $result['error_description'] : $result['error'];
        }

        return $this->validate_access_token($result['access_token'], $result['refresh_token']);
    }

    public function validate_access_token($access_token, $refresh_token)
    {
        if (!$access_token)
        {
            return 'access token 为空';
        }

        $result = curl_get_contents(self::OAUTH2_TOKEN_VALIDATION_URL . '?access_token=' . $access_token);

        if (!$result)
        {
            return '验证 access token 时，与 Google 通信失败';
        }

        $result = json_decode($result, true);

        if ($result['error_description'])
        {
            return '验证 access token 失败，错误为：' . $result['error_description'];
        }

        return $this->get_user_info($access_token, $refresh_token, time() + intval($result['expires_in']));
    }

    public function get_user_info($access_token, $refresh_token, $expires_time)
    {
        if (!$access_token)
        {
            return 'access token 为空';
        }

        $header = array('Authorization: Bearer ' . $access_token);

        $result = HTTP::request(self::OAUTH2_USER_INFO_URL, 'GET', null, 10, $header);

        if (!$result)
        {
            return '获取个人资料时，与 Google 通信失败';
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            return '获取个人资料失败，错误为：' . $result['error']['message'];
        }

        $this->user_info = array(
            'id' => $result['id'],
            'name' => $result['name'],
            'locale' => $result['locale'],
            'picture' => $result['picture'],
            'gender' => $result['gender'],
            'email' => $result['email'],
            'link' => $result['link'],
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires_time' => $expires_time,
            'verified_email' => $result['verified_email']
        );
    }

    public function refresh_access_token($id)
    {
        $user_info = $this->get_google_user_by_id($id);

        if (!$user_info)
        {
            return 'Google 账号未绑定';
        }

        if (!$user_info['refresh_token'])
        {
            return 'refresh token 为空';
        }

        $args = array(
            'client_id' => get_setting('google_client_id'),
            'client_secret' => get_setting('google_client_secret'),
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URI, 'POST', $args);

        if (!$result)
        {
            return '更新 access token 时，与 Google 通信失败';
        }

        $result = json_decode($result, true);

        if ($result['error'])
        {
            return '更新 access token 失败，错误为：' . ($result['error_description']) ? $result['error_description'] : $result['error'];
        }

        $this->update('users_google',  array(
            'access_token' => htmlspecialchars($access_token),
            'expires_time' => time() + intval($result['expires_in'])
        ), 'id = ' . $id);
    }

    public function bind_account($google_user, $uid)
    {
        if ($this->get_google_user_by_id($google_user['id']) OR $this->get_google_user_by_uid($uid))
        {
            return false;
        }

        $this->insert('users_google', array(
            'id' => htmlspecialchars($google_user['id']),
            'uid' => intval($uid),
            'name' => htmlspecialchars($google_user['name']),
            'locale' => htmlspecialchars($google_user['locale']),
            'picture' => urlencode($google_user['picture']),
            'gender' => htmlspecialchars($google_user['gender']),
            'email' => htmlspecialchars($google_user['email']),
            'link' => urlencode($google_user['link']),
            'access_token' => htmlspecialchars($google_user['access_token']),
            'refresh_token' => htmlspecialchars($google_user['refresh_token']),
            'expires_time' => intval($google_user['expires_time'])
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
            'picture' => urlencode($google_user['picture']),
            'gender' => htmlspecialchars($google_user['gender']),
            'email' => htmlspecialchars($google_user['email']),
            'link' => urlencode($google_user['link']),
            'access_token' => htmlspecialchars($google_user['access_token']),
            'refresh_token' => htmlspecialchars($google_user['refresh_token']),
            'expires_time' => intval($google_user['expires_time'])
        ), 'id = ' . $id);
    }

    public function remove_google_user($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->delete('users_google', 'id = ' . $id);
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
