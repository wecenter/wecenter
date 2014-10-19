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

class main extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';

        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();

        if (get_setting('google_enabled') != 'Y' OR !get_setting('google_client_id') OR !get_setting('google_client_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未开通 Google 登录'), '/account/login/');
        }
    }

    public function login_action()
    {
        if ($this->user_id)
        {

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

            $login_url = get_js_url('/account/google/login/');

            if ($_GET['code'])
            {
                $error_msg = $this->model('openid_google')->oauth2_login($_GET['code']);

                if (isset($error_msg))
                {
                    H::redirect_msg(AWS_APP::lang()->_t($error_msg), '/account/login/');
                }

                $google_user_info = $this->model('openid_google')->get_google_user_by_id($this->model('openid_google')->user_info['id']);

                if ($google_user_info)
                {

                }
                else
                {
                    AWS_APP::session()->google_user = $this->model('openid_google')->user_info;

                    $this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

                    TPL::assign('register_url', get_js_url('/account/ajax/google/register/'));

                    TPL::assign('user_name', AWS_APP::session()->google_user['name']);

                    TPL::assign('email', AWS_APP::session()->google_user['email']);

                    TPL::import_css('css/register.css');

                    TPL::output('account/openid/callback');
                }
            }
            else
            {
                $redirect_url = $this->model('openid_google')->get_redirect_url($login_url);

                HTTP::redirect($redirect_url);
            }
        }
    }
}
