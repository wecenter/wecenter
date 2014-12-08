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

class openid_qq_class extends AWS_MODEL
{
    const OAUTH2_AUTH_URL = 'https://graph.qq.com/oauth2.0/authorize';

    const OAUTH2_TOKEN_URL = 'https://graph.qq.com/oauth2.0/token';

    const OAUTH2_OPENID_URL = 'https://graph.qq.com/oauth2.0/me';

    const OAUTH2_USER_INFO_URL = 'https://graph.qq.com/user/get_user_info';

    public $authorization_code;

    public $access_token;

    public $refresh_token;

    public $redirect_url;

    public $expires_time;

    public $openid;

    public $error_msg;

    public $user_info;

    public function get_redirect_url($redirect_url, $state = null)
    {
        $args = array(
            'response_type' => 'code',
            'client_id' => get_setting('qq_login_app_id'),
            'redirect_uri' => get_js_url($redirect_url),
            'scope' => 'get_user_info'
        );

        if ($state)
        {
            $args['state'] = $state;
        }

        return self::OAUTH2_AUTH_URL . '?' . http_build_query($args);
    }

    public function oauth2_login()
    {
        if (!$this->get_access_token() OR !$this->get_openid() OR !$this->get_user_info())
        {
            if (!$this->error_msg)
            {
                $this->error_msg = AWS_APP::lang()->_t('QQ 登录失败');
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
            'grant_type' => 'authorization_code',
            'client_id' => get_setting('qq_login_app_id'),
            'client_secret' => get_setting('qq_login_app_key'),
            'code' => $this->authorization_code,
            'redirect_uri' => get_js_url($this->redirect_url)
        );

        $result = curl_get_contents(self::OAUTH2_TOKEN_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 时，与 QQ 通信失败');

            return false;
        }

        parse_str($result, $access_token);

        if (!$access_token['access_token'])
        {
            preg_match('/\((.+)\)/', $result, $matchs);

            $result = json_decode($matchs[1], true);

            $this->error_msg = AWS_APP::lang()->_t('获取 access token 失败，错误为：%s', $result['error_description']);

            return false;
        }

        $this->access_token = $access_token['access_token'];

        $this->refresh_token = $access_token['refresh_token'];

        $this->expires_time = time() + intval($access_token['expires_in']);

        return true;
    }

    public function get_openid()
    {
        if (!$this->access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('access token 为空');

            return false;
        }

        $args = array(
            'access_token' => $this->access_token
        );

        $result = curl_get_contents(self::OAUTH2_OPENID_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 openid 时，与 QQ 通信失败');

            return false;
        }

        preg_match('/\((.+)\)/', $result, $matchs);

        $result = json_decode($matchs[1], true);

        if ($result['error'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 openid 失败，错误为：%s', $result['error_description']);

            return false;
        }

        $this->openid = $result['openid'];

        return true;
    }

    public function get_user_info()
    {
        if (!$this->openid)
        {
            $this->error_msg = AWS_APP::lang()->_t('openid 为空');

            return false;
        }

        $args = array(
            'access_token' => $this->access_token,
            'oauth_consumer_key' => get_setting('qq_login_app_id'),
            'openid' => $this->openid
        );

        $result = curl_get_contents(self::OAUTH2_USER_INFO_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料时，与 QQ 通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['ret'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料失败，错误为：%s', $result['msg']);

            return false;
        }

        switch ($result['gender'])
        {
            case '男':
                $result['gender'] = 'male';

                break;

            case '女':
                $result['gender'] = 'female';

                break;

            default:
                $result['gender'] = 'unknown';

                break;
        }

        $this->user_info = array(
            'nickname' => $result['nickname'],
            'openid' => $this->openid,
            'gender' => $result['gender'],
            'figureurl' => $result['figureurl_2'],
            'authorization_code' => $this->authorization_code,
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_time' => $this->expires_time
        );

        return true;
    }

    public function refresh_access_token($openid)
    {
        $user_info = $this->get_qq_user_by_openid($openid);

        if (!$user_info)
        {
            $this->error_msg = AWS_APP::lang()->_t('QQ 账号未绑定');

            return false;
        }

        if (!$user_info['refresh_token'])
        {
            $this->error_msg = AWS_APP::lang()->_t('refresh token 为空');

            return false;
        }

        $args = array(
            'grant_type' => 'refresh_token',
            'client_id' => get_setting('qq_login_app_id'),
            'client_secret' => get_setting('qq_login_app_key'),
            'refresh_token' => htmlspecialchars_decode($user_info['refresh_token'])
        );

        $result = curl_get_contents(OAUTH2_TOKEN_URL, $args);

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('更新 access token 时，与 QQ 通信失败');

            return false;
        }

        parse_str($result, $access_token);

        if (!$access_token['access_token'])
        {
            preg_match('/\((.+)\)/', $result, $matchs);

            $result = json_decode($matchs[1], true);

            $this->error_msg = AWS_APP::lang()->_t('更新 access token 失败，错误为：%s', $result['error_description']);

            return false;
        }

        $this->update('users_qq',  array(
            'access_token' => htmlspecialchars($result['access_token']),
            'refresh_token' => htmlspecialchars($result['refresh_token']),
            'expires_time' => time() + intval($result['expires_in'])
        ), 'id = ' . $user_info['id']);

        return true;
    }

    public function bind_account($qq_user, $uid)
    {
        if ($this->get_qq_user_by_openid($qq_user['openid']) OR $this->get_qq_user_by_uid($uid))
        {
            return false;
        }

        return $this->insert('users_qq', array(
            'uid' => intval($uid),
            'nickname' => htmlspecialchars($qq_user['nickname']),
            'openid' => htmlspecialchars($qq_user['openid']),
            'gender' => htmlspecialchars($qq_user['gender']),
            'figureurl' => htmlspecialchars($qq_user['figureurl']),
            'access_token' => htmlspecialchars($qq_user['access_token']),
            'refresh_token' => htmlspecialchars($qq_user['refresh_token']),
            'expires_time' => intval($qq_user['expires_time']),
            'add_time' => time()
        ));
    }

    public function update_user_info($id, $qq_user)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('users_qq', array(
            'nickname' => htmlspecialchars($qq_user['nickname']),
            'openid' => htmlspecialchars($qq_user['openid']),
            'gender' => htmlspecialchars($qq_user['gender']),
            'figureurl' => htmlspecialchars($qq_user['figureurl']),
            'access_token' => htmlspecialchars($qq_user['access_token']),
            'refresh_token' => htmlspecialchars($qq_user['refresh_token']),
            'expires_time' => intval($qq_user['expires_time']),
        ), 'id = ' . $id);
    }

    public function unbind_account($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        return $this->delete('users_qq', 'uid = ' . $uid);
    }

    public function get_qq_user_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $qq_user_info;

        if (!$qq_user_info[$id])
        {
            $qq_user_info[$id] = $this->fetch_row('users_qq', 'id = ' . $id);
        }

        return $qq_user_info[$id];
    }

    public function get_qq_user_by_openid($openid)
    {
        static $qq_user_info;

        if (!$qq_user_info[$openid])
        {
            $qq_user_info[$openid] = $this->fetch_row('users_qq', 'openid = "' . $this->quote($openid) . '"');
        }

        return $qq_user_info[$openid];
    }

    public function get_qq_user_by_uid($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        static $qq_user_info;

        if (!$qq_user_info[$uid])
        {
            $qq_user_info[$uid] = $this->fetch_row('users_qq', 'uid = ' . $uid);
        }

        return $qq_user_info[$uid];
    }
}
