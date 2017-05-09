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

        $this->crumb(AWS_APP::lang()->_t('工单'), '/ticket/');

        $this->per_page = get_setting('contents_per_page');

        TPL::import_css('css/ticket.css');
    }

    public function index_action()
    {
        $this->pre_page = 100;

        if ($_GET['notification_id'])
        {
            $this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_GET['id']);

        if (!$ticket_info)
        {
            H::redirect_msg(AWS_APP::lang()->_t('工单不存在或已被删除'), '/');
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service']
            AND $ticket_info['uid'] != $this->user_id AND !$this->model('ticket')->has_invited($this->user_id))
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有权限查看该工单'));
        }

        $this->crumb($ticket_info['title'], '/ticket/' . $ticket_info['id']);

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

            if ($_GET['reply_id'])
            {
                $replies_list = array($this->model('ticket')->get_ticket_reply_by_id($_GET['reply_id']));
            }
            else
            {
                $replies_list = $this->model('ticket')->get_replies_list_by_ticket_id($ticket_info['id'], $_GET['page'], $this->pre_page);
            }

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

            TPL::assign('attach_access_key', md5($this->user_id . time()));

            TPL::assign('human_valid', human_valid('answer_valid_hour'));

            if (get_setting('advanced_editor_enable') == 'Y')
            {
                import_editor_static_files();
            }

            if (get_setting('upload_enable') == 'Y')
            {
                // fileupload
                TPL::import_js('js/fileupload.js');
            }
        }

        $ticket_topics = $this->model('topic')->get_topics_by_item_id($ticket_info['id'], 'ticket');

        if ($ticket_topics)
        {
            TPL::assign('ticket_topics', $ticket_topics);
        }

        $invite_users = $this->model('ticket')->get_invite_users($ticket_info['id']);

        if ($invite_users)
        {
            foreach ($invite_users AS $invite_info)
            {
                $uids[] = $invite_info['recipient_uid'];
            }
        }

        $users_list = $this->model('account')->get_user_info_by_uids($uids);

        $ticket_info['user_info'] = $users_list[$ticket_info['uid']];

        $ticket_info['service_info'] = $users_list[$ticket_info['service']];

        $ticket_info['message'] = nl2br(FORMAT::parse_markdown($ticket_info['message']));

        if ($ticket_info['has_attach'])
        {
            $ticket_info['attachs'] = $this->model('publish')->get_attach('ticket', $ticket_info['id'], 'min');

            $ticket_info['insert_attach_ids'] = FORMAT::parse_attachs($ticket_info['message'], true);

            $ticket_info['message'] = FORMAT::parse_attachs($ticket_info['message']);
        }

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

                $replies_list[$key]['message'] = nl2br(FORMAT::parse_markdown($reply_info['message']));

                if ($reply_info['has_attach'])
                {
                    $has_attach_reply_ids[] = $reply_info['id'];
                }
            }

            if ($has_attach_reply_ids)
            {
                $reply_attachs = $this->model('publish')->get_attachs('ticket_reply', $has_attach_reply_ids, 'min');

                foreach ($replies_list AS $key => $reply_info)
                {
                    if ($reply_info['has_attach'])
                    {
                        $replies_list[$key]['attachs'] = $reply_attachs[$reply_info['id']];

                        $replies_list[$key]['insert_attach_ids'] = FORMAT::parse_attachs($reply_info['message'], true);

                        $replies_list[$key]['message'] = FORMAT::parse_attachs($reply_info['message']);
                    }
                }
            }

            TPL::assign('replies_list', $replies_list);

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/ticket/id-' . $ticket_info['id']),
                'total_rows' => $replies_count,
                'per_page' => $this->pre_page
            ))->create_links());
        }

        if ($invite_users)
        {
            foreach ($invite_users AS $key => $invite_info)
            {
                $invite_users[$key]['recipient_info'] = $users_list[$invite_info['recipient_uid']];
            }

            TPL::assign('invite_users', $invite_users);
        }

        TPL::assign('ticket_info', $ticket_info);

        TPL::output('ticket/index');
    }

    public function index_square_action()
    {
        if ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_service'])
        {
            $this->crumb(AWS_APP::lang()->_t('工单列表'), '/ticket/');

            if ($_GET['uid'] == 'me')
            {
                $filter['uid'] = $this->user_id;
            }

            switch ($_GET['service']) {
                case 'me':
                    $filter['service'] = $this->user_id;

                    break;

                case 'none':
                    $filter['service'] = 0;

                    break;
            }


            if ($_GET['status'])
            {
                $filter['status'] = $_GET['status'];
            }

            if (!$_GET['page'])
            {
                $_GET['page'] = 1;
            }

            $tickets_list = $this->model('ticket')->get_tickets_list($filter, $_GET['page'], $this->per_page);

            $tickets_count = $this->model('ticket')->found_rows();

            if ($tickets_list)
            {
                foreach ($tickets_list AS $ticket_info)
                {
                    $uids[] = $ticket_info['uid'];

                    if ($ticket_info['service'])
                    {
                        $uids[] = $ticket_info['service'];
                    }
                }

                $users_list = $this->model('account')->get_user_info_by_uids($uids);

                foreach ($tickets_list AS $key => $ticket_info)
                {
                    $tickets_list[$key]['user_info'] = $users_list[$ticket_info['uid']];

                    if ($ticket_info['service'])
                    {
                        $tickets_list[$key]['service_info'] = $users_list[$ticket_info['service']];
                    }
                }
            }

            TPL::assign('tickets_list', $tickets_list);

            TPL::assign('tickets_count', $tickets_count);

            if (!$_GET['service'] AND !$_GET['status'])
            {
                TPL::assign('valid_tickets_count',
                    $this->model('ticket')->get_tickets_list(array(
                        'rating' => 'valid',
                        'days' => 7
                ), null, null, true));

                TPL::assign('invalid_tickets_count',
                    $this->model('ticket')->get_tickets_list(array(
                        'rating' => 'invalid',
                        'days' => 7
                ), null, null, true));

                TPL::assign('closed_tickets_count',
                    $this->model('ticket')->get_tickets_list(array(
                        'status' => 'closed',
                        'days' => 7
                ), null, null, true));
            }

            if ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_service'])
            {
                TPL::assign('my_pending_tickets',
                    $this->model('ticket')->get_tickets_list(array(
                        'service' => $this->user_id,
                        'status' => 'pending'
                ), null, null, true));
            }

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/ticket/' . 'uid-' . $_GET['uid'] . '__service-' . $_GET['service'] . '__status-' . $_GET['status']),
                'total_rows' => $tickets_count,
                'per_page' => $this->pre_page
            ))->create_links());

            TPL::output('ticket/square');
        }
        else if ($this->user_info['permission']['publish_ticket'])
        {
            $this->crumb(AWS_APP::lang()->_t('我的工单'), '/ticket/');

            $tickets_list = $this->model('ticket')->get_tickets_list(array(
                'uid' => $this->user_id
            ), $_GET['page'], $this->per_page);

            $tickets_count = $this->model('ticket')->found_rows();

            if ($tickets_list)
            {
                foreach ($tickets_list AS $ticket_info)
                {
                    if ($ticket_info['service'])
                    {
                        $uids[] = $ticket_info['service'];
                    }
                }

                $users_list = $this->model('account')->get_user_info_by_uids($uids);

                foreach ($tickets_list AS $key => $ticket_info)
                {
                    if ($ticket_info['service'])
                    {
                        $tickets_list[$key]['service_info'] = $users_list[$ticket_info['service']];
                    }
                }
            }

            TPL::assign('tickets_list', $tickets_list);

            TPL::assign('tickets_count', $tickets_count);

            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/ticket/'),
                'total_rows' => $tickets_count,
                'per_page' => $this->pre_page
            ))->create_links());

            TPL::output('ticket/my');
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在的用户组没有权限查看工单'));
        }
    }

    public function data_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限查看工单统计'));
        }

        $this->crumb(AWS_APP::lang()->_t('综合数据表'), '/ticket/data/');

        TPL::assign('pending_tickets_count',
            $this->model('ticket')->get_tickets_list(array(
                'status' => 'pending'
        ), null, null, true));

        TPL::assign('closed_tickets_count',
            $this->model('ticket')->get_tickets_list(array(
                'status' => 'closed'
        ), null, null, true));

        TPL::assign('tickets_count',
            $this->model('ticket')->get_tickets_list(null, null, null, true));

        TPL::assign('users_count',
            $this->model('ticket')->get_tickets_list(array(
                'distinct' => 'uid'
        ), null, null, true));

        if ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_service'])
        {
            TPL::assign('my_pending_tickets',
                $this->model('ticket')->get_tickets_list(array(
                    'service' => $this->user_id,
                    'status' => 'pending'
            ), null, null, true));
        }

        TPL::output('ticket/data');
    }

    public function topic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限查看工单话题'));
        }

        $this->crumb(AWS_APP::lang()->_t('热门话题'), '/ticket/topic/');

        $topics_list = $this->model('ticket')->get_hot_topics($_GET['days'], $_GET['page'], $this->per_page);

        TPL::assign('topics_list', $topics_list);

        if ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_service'])
        {
            TPL::assign('my_pending_tickets',
                $this->model('ticket')->get_tickets_list(array(
                    'service' => $this->user_id,
                    'status' => 'pending'
            ), null, null, true));
        }

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/ticket/topic/' . 'days-' . $_GET['days']),
            'total_rows' => $this->model('ticket')->get_hot_topics($_GET['days'], null, null, true),
            'per_page' => $this->pre_page
        ))->create_links());

        TPL::output('ticket/topic');
    }

    public function publish_action()
    {
        if (!$this->user_info['permission']['publish_ticket'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布工单'));
        }

        $this->crumb(AWS_APP::lang()->_t('发布工单'), '/ticket/publish/');

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
