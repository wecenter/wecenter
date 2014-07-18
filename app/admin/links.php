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

class links extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(101));
    }

    public function index_action()
    {
        $this->crumb(AWS_APP::lang()->_t('友情链接'), 'admin/links/');

        $links_setting = get_setting('links_setting');

        TPL::assign('links_setting', $links_setting);

        TPL::output('admin/links/setting');
    }

    public function list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('链接列表'), 'admin/links/list/');

        $links_list = $this->model('admin')->fetch_page('links', null, 'id ASC', $_GET['page'], $this->per_page);

        $links_total = $this->model('admin')->found_rows();

        TPL::assign('links_list', $links_list);

        TPL::assign('links_total', $links_total);

        TPL::output('admin/links/list');
    }

    public function edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('修改链接'), 'admin/links/edit/');

        if ($_GET['id'])
        {
            $link_info = $this->model('admin')->fetch_row('links', 'id = ' . intval($_GET['id']));

            if (empty($link_info))
            {
                H::redirect_msg(AWS_APP::lang()->_t('链接不存在'), '/admin/links/');
            }

            TPL::assign('link_info', $link_info);
        }

        TPL::output('admin/links/edit');
    }
}