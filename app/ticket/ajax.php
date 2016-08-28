<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
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

class ajax extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();

        if (get_setting('ticket_enabled') != 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单系统未启用')));
        }
    }

    public function publish_action()
    {
        if (!$this->user_info['permission']['publish_ticket'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限发布工单')));
        }

        $_POST['title'] = trim($_POST['title']);

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入工单标题')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['message'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $ticket_id = $this->model('ticket')->save_ticket($_POST['title'], $_POST['message'], $this->user_id, $_POST['attach_access_key']);

        if (!$ticket_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('发布失败')));
        }

        $this->model('draft')->delete_draft(1, 'ticket', $this->user_id);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/ticket/' . $ticket_id)
        ), 1, null));
    }

    public function reply_action()
    {
        $_POST['message'] = trim($_POST['message']);

        if (!$_POST['message'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入回复内容')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要回复的工单')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        if ($ticket_info['status'] == 'closed')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单已关闭')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service']
            AND $ticket_info['uid'] != $this->user_id AND !$this->model('ticket')->has_invited($this->user_id))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限回复该工单')));
        }

        $reply_id = $this->model('ticket')->reply_ticket($ticket_info['id'], $_POST['message'], $this->user_id, $_POST['attach_access_key']);

        if (!$reply_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复失败')));
        }

        $this->model('draft')->delete_draft(1, 'ticket_reply', $this->user_id);

        $reply_info = $this->model('ticket')->get_ticket_reply_by_id($reply_id);

        if ($ticket_info['uid'] != $this->user_id)
        {
            $this->model('notify')->send($reply_info['uid'], $ticket_info['uid'], notify_class::TYPE_TICKET_REPLIED, notify_class::CATEGORY_TICKET, 0, array(
                'from_uid' => $reply_info['uid'],
                'ticket_id' => $ticket_info['id'],
                'reply_id' => $reply_info['id']
            ));
        }

        $reply_info['user_info'] = $this->user_info;

        $reply_info['message'] = nl2br(FORMAT::parse_markdown($reply_info['message']));

        if ($reply_info['has_attach'])
        {
            $reply_info['attachs'] = $this->model('publish')->get_attach('ticket', $reply_info['id'], 'min');

            $reply_info['insert_attach_ids'] = FORMAT::parse_attachs($reply_info['message'], true);

            $reply_info['message'] = FORMAT::parse_attachs($reply_info['message']);
        }

        if (!$ticket_info['service'] AND $ticket_info['uid'] != $this->user_id AND
            ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_service']))
        {
            $this->model('ticket')->assign_service($ticket_info['id'], $this->user_id);
        }

        TPL::assign('reply_info', $reply_info);

        H::ajax_json_output(AWS_APP::RSM(array(
            'ajax_html' => TPL::output('ticket/ajax/reply', false)
        ), 1, null));
    }

    public function change_priority_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['priority'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单优先级')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        $this->model('ticket')->change_priority($ticket_info['id'], $this->user_id, $_POST['priority']);

        H::ajax_json_output(AWS_APP::RSM(null, -1));
    }

    public function change_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'] AND $_POST['status'] != 'closed')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['status'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单状态')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        if ($ticket_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        $this->model('ticket')->change_status($ticket_info['id'], $this->user_id, $_POST['status']);

        if ($ticket_info['uid'] != $this->user_id AND $_POST['status'] == 'closed')
        {
            $this->model('notify')->send($this->user_id, $ticket_info['uid'], notify_class::TYPE_TICKET_CLOSED, notify_class::CATEGORY_TICKET, 0, array(
                'from_uid' => $this->user_id,
                'ticket_id' => $ticket_info['id']
            ));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function change_rating_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限更改工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['rating'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单评级')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单不存在')));
        }

        $this->model('ticket')->change_rating($ticket_info['id'], $this->user_id, $_POST['rating']);

        H::ajax_json_output(AWS_APP::RSM(null, -1));
    }

    public function remove_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限删除工单')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        $this->model('ticket')->remove_ticket($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/ticket/')
        ), 1, null));
    }

    public function save_topic_relation_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限为工单添加话题')));
        }

        $_POST['topic_title'] = trim($_POST['topic_title']);

        if (!$_POST['topic_title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入话题标题')));
        }

        if (!$_POST['item_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (strstr($_POST['topic_title'], '/') OR strstr($_POST['topic_title'], '-'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题标题不能包含 / 与 -')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['item_id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该工单不存在')));
        }

        if (!$this->model('topic')->get_topic_id_by_title($_POST['topic_title']) AND get_setting('topic_title_limit') AND cjk_strlen($_POST['topic_title']) > get_setting('topic_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题标题字数不得超过 %s 字节', get_setting('topic_title_limit'))));
        }

        if (count($this->model('topic')->get_topics_by_item_id($ticket_info['id'], 'ticket')) >= get_setting('question_topics_limit') AND get_setting('question_topics_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('单个工单话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
        }

        $topic_id = $this->model('topic')->save_topic($_POST['topic_title'], $this->user_id, $this->user_info['permission']['create_topic']);

        if (!$topic_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题已锁定或没有创建话题权限, 不能添加话题')));
        }

        $this->model('topic')->save_topic_relation($this->user_id, $topic_id, $ticket_info['id'], 'ticket');

        H::ajax_json_output(AWS_APP::RSM(array(
            'topic_id' => $topic_id,
            'topic_url' => get_js_url('topic/' . $topic_id)
        ), 1, null));
    }

    public function remove_topic_relation_action()
    {
        if (!$_POST['topic_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择话题')));
        }

        if (!$_POST['item_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['item_id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该工单不存在')));
        }

        $this->model('topic')->remove_topic_relation($this->user_id, $_POST['topic_id'], $ticket_info['id'], 'ticket');

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function remove_reply_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限删除回复')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择回复')));
        }

        $reply_info = $this->model('ticket')->get_ticket_reply_by_id($_POST['id']);

        if (!$reply_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复不存在')));
        }

        $this->model('ticket')->remove_ticket_reply($reply_info['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function invite_user_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限邀请用户')));
        }

        if (!$_POST['ticket_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择受邀用户')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['ticket_id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该工单不存在')));
        }

        if ($ticket_info['status'] == 'closed')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单已关闭')));
        }

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('受邀用户不存在')));
        }

        if ($user_info['uid'] == $this->user_id OR $user_info['uid'] == $ticket_info['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能邀请自己或工单发起人')));
        }

        if ($this->model('ticket')->has_invited($ticket_info['id'], $user_info['uid']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已被邀请')));
        }

        $this->model('ticket')->invite_user($ticket_info['id'], $this->user_id, $user_info['uid']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function cancel_invite_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限取消邀请')));
        }

        if (!$_POST['ticket_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择受邀用户')));
        }

        if (!$this->model('ticket')->has_invited($_POST['ticket_id'], $_POST['uid']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户未被邀请')));
        }

        $this->model('ticket')->cancel_invite($_POST['ticket_id'], $_POST['uid']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function assign_service_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限分配工单')));
        }

        if (!$_POST['ticket_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择工单')));
        }

        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择客服')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['ticket_id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该工单不存在')));
        }

        if ($ticket_info['status'] == 'closed')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('工单已关闭')));
        }

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
        }

        $user_group_info = $this->model('account')->get_user_group_by_id($user_info['group_id']);

        if (!$user_group_info['permission']['is_administortar'] AND !$user_group_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不属于客服组')));
        }

        $this->model('ticket')->assign_service($ticket_info['id'], $user_info['uid']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, null));
    }

    public function save_to_question_action()
    {
        if (!$this->user_info['permission']['is_administortar'] OR !$this->user_info['permission']['is_service'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限删除回复')));
        }

        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择回复')));
        }

        $ticket_info = $this->model('ticket')->get_ticket_info_by_id($_POST['id']);

        if (!$ticket_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复不存在')));
        }

        if ($this->model('ticket')->get_question_info_by_ticket_id($ticket_info['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('此工单已发布到问题')));
        }

        $question_id = $this->model('ticket')->save_ticket_to_question($ticket_info['id']);

        if (!$question_id)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('发布失败')));
        }

        H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/question/' . $question_id)), 1, null));
    }

    public function ticket_statistic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            exit();
        }

        if (!is_digits($_GET['days']) OR $_GET['days'] === '0')
        {
            $_GET['days'] = 7;
        }

        $new_tickets_count = $this->model('ticket')->ticket_statistic('new_ticket', $_GET['days']);

        $closed_tickets_count = $this->model('ticket')->ticket_statistic('closed_ticket', $_GET['days']);

        $pending_tickets_count = $this->model('ticket')->ticket_statistic('pending_ticket', $_GET['days']);

        $ticket_replies_count = $this->model('ticket')->ticket_statistic('ticket_replies', $_GET['days']);

        for ($i=0; $i<=$_GET['days']; $i++)
        {
            $date[] = gmdate('m月d日', strtotime('-' . ($_GET['days'] - $i) . ' days'));
        }

        exit(json_encode(array(
            'labels' => $date,

            'data' => array(
                $new_tickets_count,
                $closed_tickets_count,
                $pending_tickets_count,
                $ticket_replies_count
            )
        )));
    }

    public function ticket_source_statistic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            exit();
        }

        if (!$_GET['days'] OR $_GET['days'] === '0')
        {
            $_GET['days'] = 7;
        }

        $filter['days'] = $_GET['days'];

        $filter['source'] = 'local';

        $from_local_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['source'] = 'weibo';

        $from_weibo_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['source'] = 'weixin';

        $from_weixin_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['source'] = 'email';

        $from_email_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        exit(json_encode(array(
            'data' => array(
                $from_local_count,
                $from_weibo_count,
                $from_weixin_count,
                $from_email_count
            )
        )));
    }

    public function first_reply_statistic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            exit();
        }

        if (!$_GET['days'] OR $_GET['days'] === '0')
        {
            $_GET['days'] = 7;
        }

        $filter['days'] = $_GET['days'];

        $filter['reply_took_hours'] = array(
            'min' => 0,
            'max' => 1
        );

        $zero_to_one_hour_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['reply_took_hours'] = array(
            'min' => 1,
            'max' => 8
        );

        $one_to_eight_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['reply_took_hours'] = array(
            'min' => 8,
            'max' => 24
        );

        $eight_to_twenty_four_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['reply_took_hours'] = array(
            'min' => 24
        );

        $more_than_twenty_four_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        exit(json_encode(array(
            'data' => array(
                $zero_to_one_hour_count,
                $one_to_eight_hours_count,
                $eight_to_twenty_four_hours_count,
                $more_than_twenty_four_hours_count
            )
        )));
    }

    public function end_ticket_statistic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            exit();
        }

        if (!$_GET['days'] OR $_GET['days'] === '0')
        {
            $_GET['days'] = 7;
        }

        $filter['days'] = $_GET['days'];

        $filter['close_took_hours'] = array(
            'min' => 0,
            'max' => 6
        );

        $zero_to_six_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['close_took_hours'] = array(
            'min' => 6,
            'max' => 24
        );

        $six_to_twenty_four_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['close_took_hours'] = array(
            'min' => 24,
            'max' => 48
        );

        $twenty_four_to_forty_eight_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        $filter['close_took_hours'] = array(
            'min' => 48
        );

        $more_than_forty_eight_hours_count = $this->model('ticket')->get_tickets_list($filter, null, null, true);

        exit(json_encode(array(
            'data' => array(
                $zero_to_six_hours_count,
                $six_to_twenty_four_hours_count,
                $twenty_four_to_forty_eight_hours_count,
                $more_than_forty_eight_hours_count
            )
        )));
    }

    public function service_group_statistic_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            exit();
        }

        if (!is_digits($_GET['months']) OR $_GET['months'] === '0')
        {
            $_GET['months'] = 5;
        }

        $statistic = $this->model('ticket')->service_group_statistic($_GET['months']);

        $data['legend'] = array();

        $data['data'] = array();

        if ($statistic)
        {
            $i = 0;

            foreach ($statistic AS $statistic_by_group)
            {
                $data['legend'][] = $statistic_by_group['group_name'];

                foreach ($statistic_by_group['tickets_count'] AS $val)
                {
                    $data['data'][$i][] = $val['count'];
                }

                $i++;
            }
        }

        for ($i=0; $i<=$_GET['months']; $i++)
        {
            $data['labels'][] = gmdate('m月', strtotime('first day of ' . ($_GET['months'] - $i) . ' months ago'));
        }

        exit(json_encode($data));
    }
}
