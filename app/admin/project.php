<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
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

class project extends AWS_ADMIN_CONTROLLER
{
    public function approval_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('活动审核'), '/admin/project/approval_list/');

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(311));

        $approval_list = $this->model('project')->get_projects_list(null, 0, null, $_GET['page'], $this->per_page, 'id ASC');

        if ($approval_list)
        {
            $found_rows = $this->model('project')->found_rows();

            TPL::assign('approval_list', $approval_list);

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_setting('base_url') . '/?/admin/project/approval_list/',
                'total_rows' => $found_rows,
                'per_page' => $this->per_page
            ))->create_links());
        }

        TPL::output('admin/project/approval_list');
    }

    public function approval_batch_action()
    {
        define('IN_AJAX', TRUE);

        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
            case 'decline':
                $func = 'set_project_' . $_POST['batch_type'];

                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('project')->$func($approval_id);
                }

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function project_list_action()
    {
        if ($project_list = $this->model('project')->get_projects_list(null, 1, null, $_GET['page'], $this->per_page, 'status DESC, id DESC'))
        {
            $found_rows = $this->model('project')->found_rows();

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_setting('base_url') . '/?/admin/project/project_list/',
                'total_rows' => $found_rows,
                'per_page' => $this->per_page
            ))->create_links());
        }

        $this->crumb(AWS_APP::lang()->_t('活动管理'), '/admin/project/project_list/');

        TPL::assign('approval_list', $project_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(310));

        TPL::output('admin/project/project_list');
    }

    public function status_batch_action()
    {
        define('IN_AJAX', TRUE);

        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'ONLINE':
            case 'OFFLINE':
                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('project')->set_project_status($approval_id, $_POST['batch_type']);
                }

                break;

            case 'delete':
                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('project')->remove_project_by_project_id($approval_id);
                }

                break;
        }


        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function order_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('订单管理'), '/admin/project/order_list/');

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(312));

        $order_list = $this->model('project')->get_order_list(null, $_GET['page'], $this->per_page);

        if ($order_list)
        {
            $order_num = $this->model('project')->found_rows();

            foreach ($order_list AS $order_info)
            {
                $uids[] = $order_info['uid'];
            }

            $users_info = $this->model('account')->get_user_info_by_uids($uids);

            TPL::assign('order_list', $order_list);

            TPL::assign('users_info', $users_info);

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_setting('base_url') . '/?/admin/project/order_list/',
                'total_rows' => $order_num,
                'per_page' => $this->per_page
            ))->create_links());
        }

        TPL::output('admin/project/order_list');
    }

    public function edit_order_action()
    {
        $this->crumb(AWS_APP::lang()->_t('订单编辑'), '/admin/project/edit_order/');

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(312));

        if (!$_GET['id'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('请选择订单'), '/admin/project/order_list/');
        }

        $order_info = $this->model('project')->get_product_order_by_id($_GET['id']);

        if (!$order_info)
        {
            H::redirect_msg(AWS_APP::lang()->_t('订单不存在'), '/admin/project/order_list/');
        }

        TPL::assign('order_info', $order_info);

        TPL::assign('order_user', $this->model('account')->get_user_info_by_uid($order_info['uid']));

        TPL::output('admin/project/edit_order');
    }

    public function save_order_action()
    {
        define('IN_AJAX', TRUE);

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择订单')));
        }

        $this->model('project')->update_order($_POST['id'], $_POST);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
