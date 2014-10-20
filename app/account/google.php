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

class google extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array(
            'bind'
        );

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();

        if (get_setting('google_login_enabled') != 'Y' OR !get_setting('google_client_id') OR !get_setting('google_client_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未开通 Google 登录'));
        }
    }

    public function bind_action()
    {
        unset(AWS_APP::session()->google_user);

        if ($_GET['code'])
        {
            $error_msg = $this->model('openid_google')->oauth2_login($_GET['code'], '/account/google/bind/');

            if (isset($error_msg))
            {
                H::redirect_msg($error_msg);
            }

            $google_user = $this->model('openid_google')->get_google_user_by_id($this->model('openid_google')->user_info['id']);

            if ($this->user_id)
            {
                if ($google_user)
                {
                    H::redirect_msg(AWS_APP::lang()->_t('此 Google 账号已被绑定'));
                }

                $google_user = $this->model('openid_google')->get_google_user_by_uid($this->user_id);

                if ($google_user)
                {
                    H::redirect_msg(AWS_APP::lang()->_t('此账号已绑定 Google 账号'));
                }

                $this->model('openid_google')->bind_account($this->model('openid_google')->user_info, $this->user_id);

                if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
                {
                    $this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
                }

                HTTP::redirect('/account/setting/openid/');
            }
            else
            {
                switch (get_setting('register_type'))
                {
                    case 'close':
                        H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'), '/account/login/');

                        break;

                    case 'invite':
                        H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'), '/account/login/');

                        break;

                    case 'weixin':
                        H::redirect_msg(AWS_APP::lang()->_t('本站只能通过微信注册'), '/account/login/');

                        break;
                }

                if ($google_user)
                {
                    $user = $this->model('account')->get_user_info_by_uid($google_user['uid']);

                    if (!$user)
                    {
                        $this->model('openid_google')->remove_google_user($google_user['id']);

                        H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/account/login/');
                    }

                    $this->model('openid_google')->update_user_info($google_user['id'], $this->model('openid_google')->user_info);

                    HTTP::set_cookie('_user_login', get_login_cookie_hash($user['user_name'], $user['password'], $user['salt'], $user['uid'], false));

                    HTTP::redirect('/');
                }
                else
                {
                    AWS_APP::session()->google_user = $this->model('openid_google')->user_info;

                    $this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

                    TPL::assign('register_url', '/account/ajax/google/register/');

                    TPL::assign('user_name', AWS_APP::session()->google_user['name']);

                    TPL::assign('email', AWS_APP::session()->google_user['email']);

                    TPL::import_css('css/register.css');

                    TPL::output('account/openid/callback');
                }
            }
        }
        else
        {
            HTTP::redirect($this->model('openid_google')->get_redirect_url('/account/google/bind/'));
        }
    }

    public function unbind_action()
    {
        $this->model('openid_google')->unbind_account($this->user_id);

        HTTP::redirect('/account/setting/openid/');
    }
}
