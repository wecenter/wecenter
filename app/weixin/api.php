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

class api extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function index_action()
    {
        if (!isset($_GET['id']))
        {
            $_GET['id'] = 0;
        }

        $account_info = $this->model('weixin')->get_account_info_by_id($_GET['id']);

        if (!$account_info OR !$this->model('weixin')->check_signature($account_info['weixin_mp_token'], $_GET['signature'], $_GET['timestamp'], $_GET['nonce']))
        {
            exit();
        }

        if ($_GET['echostr'])
        {
            exit(htmlspecialchars($_GET['echostr']));
        }

        if ($account_info['weixin_account_role'] == 'base' OR !$account_info['weixin_app_id'] OR !$account_info['weixin_app_secret'])
        {
            $account_info['weixin_mp_menu'] = null;
        }

        $this->model('weixin')->account_info = $account_info;

        $input_message = $this->model('weixin')->fetch_message();

        $this->model('weixin')->response_message($input_message);
    }
}
