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

class ajax_ticket extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
    }

    public function save_service_group_action()
    {
        if (!$_POST['edit_group'] AND !$_POST['remove_group'] AND !$_POST['new_group'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要新增或删除的客服组')));
        }

        if ($_POST['edit_group'] AND is_array($_POST['edit_group']))
        {
            foreach ($_POST['edit_group'] AS $group_id => $group_name)
            {
                $this->model('ticket')->edit_service_group($group_id, $group_name);
            }
        }

        if ($_POST['remove_group'] AND is_array($_POST['remove_group']))
        {
            foreach ($_POST['remove_group'] AS $remove_group)
            {
                $this->model('ticket')->remove_service_group($remove_group);
            }
        }

        if ($_POST['new_group'] AND is_array($_POST['new_group']))
        {
            foreach ($_POST['new_group'] AS $new_group)
            {
                $this->model('ticket')->add_service_group($new_group);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
