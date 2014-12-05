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

class openid_qq extends AWS_CONTROLLER
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

        if (get_setting('qq_login_enabled') != 'Y' OR !get_setting('qq_login_app_id') OR !get_setting('qq_login_app_key'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未开通 QQ 登录'), '/');
        }
    }

    public function bind_action()
    {
        if (AWS_APP::session()->qq_user)
        {
            $qq_user_info = AWS_APP::session()->qq_user;

            unset(AWS_APP::session()->qq_user);
        }

        if ($_GET['usercancel'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('授权失败'), '/account/login/');
        }

        if ($this->user_id)
        {
            $qq_user = $this->model('openid_qq')->get_qq_user_by_uid($this->user_id);

            if ($qq_user)
            {
                H::redirect_msg(AWS_APP::lang()->_t('此账号已绑定 QQ 账号'), '/account/login/');
            }
        }

        $callback_url = '/account/openid/qq/bind/';

        if ($_GET['return_url'])
        {
            $callback_url .= 'return_url-' . $_GET['return_url'];
        }

        if ($_GET['code'])
        {
            if ($_GET['code'] != $qq_user_info['authorization_code'])
            {
                $this->model('openid_qq')->authorization_code = $_GET['code'];

                $this->model('openid_qq')->redirect_url = $callback_url;

                if (!$this->model('openid_qq')->oauth2_login())
                {
                    H::redirect_msg($this->model('openid_qq')->error_msg, '/account/login/');
                }

                $qq_user_info = $this->model('openid_qq')->user_info;
            }

            if (!$qq_user_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('QQ 登录失败，用户信息不存在'), '/account/login/');
            }

            $qq_user = $this->model('openid_qq')->get_qq_user_by_openid($qq_user_info['openid']);

            if ($this->user_id)
            {
                if ($qq_user)
                {
                    H::redirect_msg(AWS_APP::lang()->_t('此 QQ 账号已被绑定'), '/account/login/');
                }

                $this->model('openid_qq')->bind_account($qq_user_info, $this->user_id);

                if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
                {
                    $this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
                }

                HTTP::redirect('/account/setting/openid/');
            }
            else
            {
                if ($qq_user)
                {
                    $user = $this->model('account')->get_user_info_by_uid($qq_user['uid']);

                    if (!$user)
                    {
                        $this->model('openid_qq')->unbind_account($qq_user['uid']);

                        H::redirect_msg(AWS_APP::lang()->_t('本地用户不存在'), '/account/login/');
                    }

                    $this->model('openid_qq')->update_user_info($qq_user['id'], $qq_user_info);

                    if (get_setting('register_valid_type') == 'approval' AND $user['group_id'] == 3)
                    {
                        $redirect_url = '/account/valid_approval/';
                    }
                    else
                    {
                        if ($_GET['state'])
                        {
                            $state = base64_url_decode($_GET['state']);
                        }

                        if (get_setting('ucenter_enabled') == 'Y')
                        {
                            $redirect_url = '/account/sync_login/';

                            if ($state['return_url'])
                            {
                                $redirect_url .= 'url-' . base64_encode($state['return_url']);
                            }
                        }
                        else if ($state['return_url'])
                        {
                            $redirect_url = $state['return_url'];
                        }
                        else
                        {
                            $redirect_url = '/';
                        }

                        HTTP::set_cookie('_user_login', get_login_cookie_hash($user['user_name'], $user['password'], $user['salt'], $user['uid'], false));

                        if (get_setting('register_valid_type') == 'email' AND !$user['valid_email'])
                        {
                            AWS_APP::session()->valid_email = $user['email'];
                        }
                    }

                    HTTP::redirect($redirect_url);
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

                    AWS_APP::session()->qq_user = $qq_user_info;

                    $this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

                    TPL::assign('register_url', '/account/ajax/qq/register/');

                    TPL::assign('user_name', AWS_APP::session()->qq_user['nickname']);

                    TPL::import_css('css/register.css');

                    TPL::output('account/openid/callback');
                }
            }
        }
        else
        {
            $state = ($_GET['return_url']) ? base64_url_encode(array('return_url' => base64_decode($_GET['return_url']))) : null;

            HTTP::redirect($this->model('openid_qq')->get_redirect_url('/account/openid/qq/bind/', $state));
        }
    }

    public function unbind_action()
    {
        $this->model('openid_qq')->unbind_account($this->user_id);

        HTTP::redirect('/account/setting/openid/');
    }
}
