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

        if (get_setting('twitter_login_enabled') != 'Y' OR !get_setting('twitter_consumer_key') OR !get_setting('twitter_consumer_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未开通 Google 登录'));
        }
    }

    public function bind_action()
    {
        $this->model('openid_twitter')->get_request_token('/account/google/bind/');
    }

    public function unbind_action()
    {
        $this->model('openid_twitter')->unbind_account($this->user_id);

        HTTP::redirect('/account/setting/openid/');
    }
}
