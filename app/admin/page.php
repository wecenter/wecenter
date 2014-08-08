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

class page extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('页面管理'), 'admin/page/');

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(308));
    }

    public function index_action()
    {
        TPL::assign('page_list', $this->model('page')->fetch_page_list($_GET['page'], 20));

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/page/'),
            'total_rows' => $this->model('page')->found_rows(),
            'per_page' => 20
        ))->create_links());

        TPL::output('admin/page/list');
    }

    public function add_action()
    {
        $this->crumb(AWS_APP::lang()->_t('添加页面'), "admin/page/add/");

        TPL::output('admin/page/publish');
    }

    public function edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('编辑页面'), "admin/page/edit/");

        if (!$page_info = $this->model('page')->get_page_by_url_id($_GET['id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('页面不存在'), '/admin/page/');
        }

        TPL::assign('page_info', $page_info);

        TPL::output('admin/page/publish');
    }
}