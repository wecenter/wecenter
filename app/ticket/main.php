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

class main extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        if (get_setting('ticket_enabled') != 'Y')
        {
            H::redirect_msg(AWS_APP::lang()->_t('工单系统未启用'), '/');
        }

        $this->pre_page = get_setting('contents_per_page');
    }

    public function index_action()
    {
        $ticket_info = $this->model('ticket')->get_ticket_by_id($_GET['id']);

        if (!$ticket_info)
        {
            H::redirect_msg(AWS_APP::lang()->_t('工单不存在或已被删除'), '/');
        }

        $uids[] = $ticket_info['uid'];

        if ($ticket_info['service'])
        {
            $uids[] = $ticket_info['service'];
        }

        if ($_GET['column'])
        {
            if ($_GET['column'] == 'log')
            {
                $ticket_log = $this->model('ticket')->parse_ticket_log($ticket_info['id']);

                if ($ticket_log)
                {
                    foreach ($ticket_log AS $log_info)
                    {
                        $uids[] = $log_info['uid'];
                    }
                }
            }
        }
        else
        {
            if (!$_GET['page'])
            {
                $_GET['page'] = 1;
            }

            $replies_list = $this->model('ticket')->get_replies_list_by_ticket_id($ticket_info['id'], $_GET['page'], $this->pre_page);

            if ($replies_list)
            {
                foreach ($replies_list AS $reply_info)
                {
                    $uids[] = $reply_info['uid'];
                }
            }

            $replies_count = $this->model('ticket')->found_rows();

            TPL::assign('replies_count', $replies_count);

            TPL::assign('draft_content', $this->model('draft')->get_data(1, 'ticket_reply', $this->user_id));
        }

        $users_list = $this->model('account')->get_user_info_by_uids($uids);

        $ticket_info['user_info'] = $users_list[$ticket_info['uid']];

        $ticket_info['service_info'] = $users_list[$ticket_info['service']];

        if ($ticket_log)
        {
            foreach ($ticket_log AS $key => $log_info)
            {
                $ticket_log[$key]['user_info'] = $users_list[$log_info['uid']];
            }

            TPL::assign('ticket_log', $ticket_log);
        }

        if ($replies_list)
        {
            foreach ($replies_list AS $key => $reply_info)
            {
                $replies_list[$key]['user_info'] = $users_list[$reply_info['uid']];
            }

            TPL::assign('replies_list', $replies_list);

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/ticket/' . $ticket_info['id']),
                'total_rows' => $replies_count,
                'per_page' => $this->pre_page
            ))->create_links());
        }

        TPL::assign('ticket_info', $ticket_info);

        TPL::output('ticket/index');
    }

    public function index_square_action()
    {
        $this->crumb(AWS_APP::lang()->_t('工单'), '/ticket/');

        $ticket_list = $this->model('ticket')->get_ticket_list();

        TPL::assign('ticket_list', $ticket_list);

        TPL::output('ticket/square');
    }

    public function data_action()
    {
        TPL::output('ticket/data');
    }

    public function topic_action()
    {
        TPL::output('ticket/topic');
    }

    public function publish_action()
    {
        if (!$this->user_info['permission']['publish_ticket'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布工单'));
        }

        TPL::assign('draft_content', $this->model('draft')->get_data(1, 'ticket', $this->user_id));

        TPL::assign('attach_access_key', md5($this->user_id . time()));

        TPL::assign('human_valid', human_valid('question_valid_hour'));

        TPL::import_js('js/app/publish.js');

        if (get_setting('advanced_editor_enable') == 'Y')
        {
            import_editor_static_files();
        }

        if (get_setting('upload_enable') == 'Y')
        {
            // fileupload
            TPL::import_js('js/fileupload.js');
        }

        TPL::output('ticket/publish');
    }
}
