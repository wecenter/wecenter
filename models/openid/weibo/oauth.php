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

class openid_weibo_oauth_class extends AWS_MODEL
{
    const OAUTH2_AUTH_URL = 'https://api.weibo.com/oauth2/authorize';

    const OAUTH2_TOKEN_URL = 'https://api.weibo.com/oauth2/access_token';

    const OAUTH2_TOKEN_INFO_URL = 'https://api.weibo.com/oauth2/get_token_info';

    const OAUTH2_USER_INFO_URL = 'https://api.weibo.com/2/users/show.json';

    const STATUSES_MENTIONS_URL = 'https://api.weibo.com/2/statuses/mentions.json';

    const COMMENTS_CREATE_URL = 'https://api.weibo.com/2/comments/create.json';

    public $authorization_code;

    public $access_token;

    public $redirect_url;

    public $uid;

    public $expires_time;

    public $error_msg;

    public $user_info;

    public function get_redirect_url($redirect_url, $state = null)
    {
        $args = array(
            'client_id' => get_setting('sina_akey'),
            'redirect_uri' => get_js_url($redirect_url)
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
                $this->error_msg = AWS_APP::lang()->_t('微博登录失败');
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
            'client_id' => get_setting('sina_akey'),
            'client_secret' => get_setting('sina_skey'),
            'grant_type' => 'authorization_code',
            'code' => $this->authorization_code,
            'redirect_uri' => get_js_url($this->redirect_url)
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_URL, 'POST', http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 时，与微博通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['error_code'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取 access token 失败，错误为：%s', $result['error']);

            return false;
        }

        $this->access_token = $result['access_token'];

        return true;
    }

    public function validate_access_token()
    {
        if (!$this->access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('access token 为空');
        }

        $args = array(
            'access_token' => $this->access_token
        );

        $result = HTTP::request(self::OAUTH2_TOKEN_INFO_URL, 'POST', http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 access token 时，与微博通信失败');
        }

        $result = json_decode($result, true);

        if ($result['error_code'])
        {
            $this->error_msg = AWS_APP::lang()->_t('验证 access token 失败，错误为：%s', $result['error']);

            return false;
        }

        $this->uid = $result['uid'];

        $this->expires_time = time() + intval($result['expire_in']);

        return true;
    }

    public function get_user_info()
    {
        if (!$this->access_token)
        {
            $this->error_msg = AWS_APP::lang()->_t('access token 为空');

            return false;
        }

        if (!$this->uid)
        {
            $this->error_msg = AWS_APP::lang()->_t('uid 为空');

            return false;
        }

        $args = array(
            'access_token' => $this->access_token,
            'uid' => $this->uid
        );

        $result = curl_get_contents(self::OAUTH2_USER_INFO_URL . '?' . http_build_query($args));

        if (!$result)
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料时，与微博通信失败');

            return false;
        }

        $result = json_decode($result, true);

        if ($result['error_code'])
        {
            $this->error_msg = AWS_APP::lang()->_t('获取个人资料失败，错误为：%s', $result['error']);

            return false;
        }

        $this->user_info = array(
            'id' => $result['id'],
            'screen_name' => $result['screen_name'],
            'location' => $result['location'],
            'description' => $result['description'],
            'profile_url' => $result['profile_url'],
            'profile_image_url' => str_replace('/50/', '/180/', $result['profile_image_url']),
            'gender' => $result['gender'],
            'authorization_code' => $this->authorization_code,
            'access_token' => $this->access_token,
            'expires_time' => $this->expires_time
        );

        return true;
    }

    public function bind_account($weibo_user, $uid)
    {
        if ($this->get_weibo_user_by_id($weibo_user['id']) OR $this->get_weibo_user_by_uid($uid))
        {
            return false;
        }

        $result = $this->insert('users_sina', array(
            'id' => htmlspecialchars($weibo_user['id']),
            'uid' => intval($uid),
            'name' => htmlspecialchars($weibo_user['screen_name']),
            'location' => htmlspecialchars($weibo_user['location']),
            'description' => htmlspecialchars($weibo_user['description']),
            'url' => htmlspecialchars($weibo_user['profile_url']),
            'profile_image_url' => htmlspecialchars($weibo_user['profile_image_url']),
            'gender' => htmlspecialchars($weibo_user['gender']),
            'access_token' => htmlspecialchars($weibo_user['access_token']),
            'expires_time' => intval($weibo_user['expires_time']),
            'add_time' => time()
        ));

        $tmp_service_account = AWS_APP::cache()->get('tmp_service_account');

        if ($tmp_service_account[$uid])
        {
            $this->model('openid_weibo_weibo')->update_service_account($uid, 'add');

            unset($tmp_service_account[$uid]);

            AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);
        }

        $this->model('openid_weibo_weibo')->notification_of_refresh_access_token($uid, null);

        return $result;
    }

    public function update_user_info($id, $weibo_user)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->update('users_sina', array(
            'name' => htmlspecialchars($weibo_user['screen_name']),
            'location' => htmlspecialchars($weibo_user['location']),
            'description' => htmlspecialchars($weibo_user['description']),
            'url' => htmlspecialchars($weibo_user['profile_url']),
            'profile_image_url' => htmlspecialchars($weibo_user['profile_image_url']),
            'gender' => htmlspecialchars($weibo_user['gender']),
            'access_token' => htmlspecialchars($weibo_user['access_token']),
            'expires_time' => intval($weibo_user['expires_time'])
        ), 'id = ' . $id);
    }

    public function unbind_account($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        return $this->delete('users_sina', 'uid = ' . $uid);
    }

    public function get_weibo_user_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $weibo_user_info;

        if (!$weibo_user_info[$id])
        {
            $weibo_user_info[$id] = $this->fetch_row('users_sina', 'id = ' . $id);
        }

        return $weibo_user_info[$id];
    }

    public function get_weibo_user_by_uid($uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        static $weibo_user_info;

        if (!$weibo_user_info[$uid])
        {
            $weibo_user_info[$uid] = $this->fetch_row('users_sina', 'uid = ' . $uid);
        }

        return $weibo_user_info[$uid];
    }

    public function get_msg_from_sina($access_token, $since_id = 0, $max_id = 0)
    {
        if (!$access_token)
        {
            return false;
        }

        $args = array(
            'access_token' => $access_token,
            'since_id' => $since_id,
            'max_id' => $max_id,
            'count' => 100
        );

        $result = curl_get_contents(self::STATUSES_MENTIONS_URL . '?' . http_build_query($args));

        if (!$result)
        {
            return false;
        }

        return json_decode($result, true);
    }

    public function create_comment($access_token, $id, $comment)
    {
        if (!$access_token)
        {
            return false;
        }

        $args = array(
            'access_token' => $access_token,
            'comment' => $comment,
            'id' => $id
        );

        $result = HTTP::request(self::COMMENTS_CREATE_URL, 'POST', http_build_query($args));

        if (!$result)
        {
            return false;
        }

        return json_decode($result, true);
    }
}
