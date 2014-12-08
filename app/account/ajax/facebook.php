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

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
    die;
}

class ajax_facebook extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

        $rule_action['actions'] = array(
            'register'
        );

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();

        if (get_setting('facebook_login_enabled') != 'Y' OR !get_setting('facebook_app_id') OR !get_setting('facebook_app_secret'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站未开通 Facebook 登录')));
        }
    }

    public function register_action()
    {
        if ($this->user_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('您已登录')));
        }

        switch (get_setting('register_type'))
        {
            case 'close':
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));

                break;

            case 'invite':
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));

                break;

            case 'weixin':
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过微信注册')));

                break;
        }

        if (!AWS_APP::session()->facebook_user)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('Facebook 账号信息不存在')));
        }

        if ($this->model('openid_facebook')->get_facebook_user_by_id(AWS_APP::session()->facebook_user['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('此 Facebook 账号已被绑定')));
        }

        if ($this->model('account')->check_email($_POST['email']))
        {
            H::ajax_json_output(AWS_APP::RSM(array(
                'input' => 'email'
            ), -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
        }

        if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
        {
            H::ajax_json_output(AWS_APP::RSM(array(
                'input' => 'userPassword'
            ), -1, AWS_APP::lang()->_t('密码长度不符合规则')));
        }

        if (!$_POST['agreement_chk'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
        }

        if (get_setting('ucenter_enabled') == 'Y')
        {
            $result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email']);

            if (!is_array($result))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('UCenter 同步失败，错误为：%s', $result)));
            }

            $uid = $result['user_info']['uid'];

            $redirect_url = '/account/sync_login/';
        }
        else
        {
            $uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

            if (get_setting('register_valid_type') != 'approval')
            {
                $this->model('active')->active_user_by_uid($uid);
            }

            if (AWS_APP::session()->facebook_user['email'] == $_POST['email'] AND AWS_APP::session()->facebook_user['verified'] == true)
            {
                $this->model('active')->set_user_email_valid_by_uid($uid);
            }
            else if (get_setting('register_valid_type') == 'email')
            {
                $this->model('active')->new_valid_email($uid);
            }

            $redirect_url = '/';
        }

        if (!$uid)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('注册失败')));
        }

        $this->model('openid_facebook')->bind_account(AWS_APP::session()->facebook_user, $uid);

        if (AWS_APP::session()->facebook_user['picture'])
        {
            $this->model('account')->associate_remote_avatar($uid, AWS_APP::session()->facebook_user['picture']);
        }

        if (get_setting('register_valid_type') == 'approval')
        {
            $redirect_url = '/account/valid_approval/';
        }
        else
        {
            $user_info = $this->model('account')->get_user_info_by_uid($uid);

            HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));

            if (get_setting('register_valid_type') == 'email')
            {
                AWS_APP::session()->valid_email = $user_info['email'];
            }
        }

        unset(AWS_APP::session()->facebook_user);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url($redirect_url)
        ), 1, null));
    }
}
