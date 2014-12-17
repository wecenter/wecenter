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

class ticket extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }
    }

    public function service_group_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('客服组管理'), 'admin/ticket/service_group_list/');


        TPL::assign('groups_list', $this->model('account')->get_user_group_list(2, 2));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));

        TPL::output('admin/ticket/service_group_list');
    }

    public function service_group_edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('修改客服组'), "admin/ticket/service_group_list/");

        $group_info = $this->model('account')->get_user_group_by_id($_GET['group_id']);

        if (!$group_info)
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户组不存在'), '/admin/user/group_list/');
        }

        TPL::assign('group', $group);
        TPL::assign('group_pms', $group['permission']);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_edit');
    }
}