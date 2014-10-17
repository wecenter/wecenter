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

class help extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('帮助中心'), "admin/help/list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(305));
    }

    public function list_action()
    {
        TPL::assign('chapter_list', $this->model('help')->get_chapter_list());

        TPL::output('admin/help/list');
    }

    public function edit_action()
    {
        if ($_GET['id'])
        {
            $chapter_info = $this->model('help')->get_chapter_by_id($_GET['id']);

            if (!$chapter_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定章节不存在'), '/admin/help/list/');
            }

            TPL::assign('chapter_info', $chapter_info);

            $data_list = $this->model('help')->get_data_list($chapter_info['id']);

            if ($data_list)
            {
                TPL::assign('data_list', $data_list);
            }
        }

        TPL::output('admin/help/edit');
    }
}
