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

class category extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('分类管理'), "admin/category/list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(302));
    }

    public function list_action()
    {
        TPL::assign('list', json_decode($this->model('system')->build_category_json('question'), true));

        TPL::assign('category_option', $this->model('system')->build_category_html('question', 0, 0, null, false));

        TPL::assign('target_category', $this->model('system')->build_category_html('question', 0, null));

        TPL::output('admin/category/list');
    }

    public function edit_action()
    {
        if (!$category_info = $this->model('system')->get_category_info($_GET['category_id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('指定分类不存在'), '/admin/category/list/');
        }

        TPL::assign('category', $category_info);
        TPL::assign('category_option', $this->model('system')->build_category_html($category_info['type'], 0, $category['parent_id'], null, false));

        TPL::output('admin/category/edit');
    }
}