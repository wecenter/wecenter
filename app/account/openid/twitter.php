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

class openid_twitter extends AWS_CONTROLLER
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

        if (get_setting('twitter_login_enabled') != 'Y' OR !get_setting('twitter_consumer_key') OR !get_setting('twitter_consumer_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未开通 Twitter 登录'), '/');
        }
    }

    public function bind_action()
    {
        if (AWS_APP::session()->twitter_request_token)
        {
            $twitter_request_token = AWS_APP::session()->twitter_request_token;

            unset(AWS_APP::session()->twitter_request_token);
        }

        if (AWS_APP::session()->twitter_user)
        {
            $twitter_user_info = AWS_APP::session()->twitter_user;

            unset(AWS_APP::session()->twitter_user);
        }

        if ($_GET['denied'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('授权失败'), '/account/login/');
        }

        if ($this->user_id)
        {
            $twitter_user = $this->model('openid_twitter')->get_twitter_user_by_uid($this->user_id);

            if ($twitter_user)
            {
                H::redirect_msg(AWS_APP::lang()->_t('此账号已绑定 Twitter 账号'), '/account/login/');
            }
        }

        if ($_GET['oauth_token'])
        {
            if (!$twitter_user_info)
            {
                if ($_GET['oauth_token'] != $twitter_request_token['oauth_token'])
                {
                    H::redirect_msg(AWS_APP::lang()->_t('oauth token 不一致'), '/account/login/');
                }

                if (!$_GET['oauth_verifier'])
                {
                    H::redirect_msg(AWS_APP::lang()->_t('oauth verifier 为空'), '/account/login/');
                }

                $this->model('openid_twitter')->request_token = $twitter_request_token;

                $this->model('openid_twitter')->request_token['oauth_verifier'] = $_GET['oauth_verifier'];

                if (!$this->model('openid_twitter')->get_user_info())
                {
                    H::redirect_msg($this->model('openid_twitter')->error_msg, '/account/login/');
                }

                $twitter_user_info = $this->model('openid_twitter')->user_info;
            }

            if (!$twitter_user_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('Twitter 登录失败，用户信息不存在'), '/account/login/');
            }

            $twitter_user = $this->model('openid_twitter')->get_twitter_user_by_id($twitter_user_info['id']);

            if ($this->user_id)
            {
                if ($twitter_user)
                {
                    H::redirect_msg(AWS_APP::lang()->_t('此 Twitter 账号已被绑定'), '/account/login/');
                }

                $this->model('openid_twitter')->bind_account($twitter_user_info, $this->user_id);

                if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
                {
                    $this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
                }

                HTTP::redirect('/account/setting/openid/');
            }
            else
            {
                if ($twitter_user)
                {
                    $user = $this->model('account')->get_user_info_by_uid($twitter_user['uid']);

                    if (!$user)
                    {
                        $this->model('openid_twitter')->unbind_account($twitter_user['uid']);

                        H::redirect_msg(AWS_APP::lang()->_t('本地用户不存在'), '/account/login/');
                    }

                    $this->model('openid_twitter')->update_user_info($twitter_user['id'], $twitter_user_info);

                    if (get_setting('register_valid_type') == 'approval' AND $user['group_id'] == 3)
                    {
                        $redirect_url = '/account/valid_approval/';
                    }
                    else
                    {
                        if (get_setting('ucenter_enabled') == 'Y')
                        {
                            $redirect_url = '/account/sync_login/';

                            if ($_GET['return_url'])
                            {
                                $redirect_url .= 'url-' . $_GET['return_url'];
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

                    AWS_APP::session()->twitter_user = $twitter_user_info;

                    $this->crumb(AWS_APP::lang()->_t('完善资料'), '/account/login/');

                    TPL::assign('register_url', '/account/ajax/twitter/register/');

                    TPL::assign('user_name', AWS_APP::session()->twitter_user['name']);

                    TPL::import_css('css/register.css');

                    TPL::output('account/openid/callback');
                }
            }
        }
        else
        {
            $this->model('openid_twitter')->oauth_callback = '/account/openid/twitter/bind/';

            if ($_GET['return_url'])
            {
                $this->model('openid_twitter')->oauth_callback .= 'return_url-' . $_GET['return_url'];
            }

            if (!$this->model('openid_twitter')->oauth_redirect())
            {
                H::redirect_msg($this->model('openid_twitter')->error_msg, '/account/login/');
            }

            AWS_APP::session()->twitter_request_token = $this->model('openid_twitter')->request_token;

            HTTP::redirect($this->model('openid_twitter')->redirect_url);
        }
    }

    public function unbind_action()
    {
        $this->model('openid_twitter')->unbind_account($this->user_id);

        HTTP::redirect('/account/setting/openid/');
    }
}
