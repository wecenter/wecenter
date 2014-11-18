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

class ajax_prject extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function approval_batch_action()
    {
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

    public function status_batch_action()
    {
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


    public function save_order_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择订单')));
        }

        $this->model('project')->update_order($_POST['id'], $_POST);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
