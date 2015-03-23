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

class ticket_class extends AWS_MODEL
{
    public function get_ticket_info_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $tickets;

        if (!$tickets[$id])
        {
            $tickets[$id] = $this->fetch_row('ticket', 'id = ' . $id);
        }

        return $tickets[$id];
    }

    public function get_tickets_list($filter = array(), $page = null, $per_page = null, $count = false)
    {
        $where = array();

        if ($filter['ids'] AND is_array($filter['ids']) AND is_digits($filter['ids']))
        {
            $where[] = 'id IN (' . implode(', ', $filter['ids']) . ')';
        }

        if (is_digits($filter['uid']))
        {
            $where[] = 'uid = ' . $filter['uid'];
        }

        if (is_digits($filter['service']))
        {
            $where[] = 'service = ' . $filter['service'];
        }

        if ($filter['priority'] AND in_array($filter['priority'], array('low', 'normal', 'high', 'urgent')))
        {
            $where[] = 'priority = "' . $filter['priority'] . '"';
        }

        if ($filter['status'] AND in_array($filter['status'], array('pending', 'closed')))
        {
            $where[] = 'status = "' . $filter['status'] . '"';
        }

        if ($filter['rating'] AND in_array($filter['rating'], array('valid', 'invalid', 'undefined')))
        {
            $where[] = 'rating = "' . $filter['rating'] . '"';
        }

        if ($filter['source'] AND in_array($filter['source'], array('local', 'weibo', 'weixin', 'email')))
        {
            $where[] = 'source = "' . $filter['source'] . '"';
        }

        if (isset($filter['days']))
        {
            $where[] = '`time` > ' . (time() - $filter['days'] * 24 * 60 * 60);
        }

        if (isset($filter['reply_took_hours']['min']))
        {
            $where[] = '`reply_time` <> 0 AND `reply_time` >= `time`';

            if (isset($filter['reply_took_hours']['max']) AND $filter['reply_took_hours']['min'] < $filter['reply_took_hours']['max'])
            {
                $where[] = '`reply_time` - `time` BETWEEN ' . ($filter['reply_took_hours']['min'] * 60 * 60) . ' AND ' . ($filter['reply_took_hours']['max'] * 60 * 60);
            }
            else
            {
                $where[] = '`reply_time` - `time` > ' . ($filter['reply_took_hours']['min'] * 60 * 60);
            }
        }

        if (isset($filter['close_took_hours']['min']))
        {
            $where[] = '`close_time` <> 0 AND `close_time` >= `time`';

            if (isset($filter['close_took_hours']['max']) AND $filter['close_took_hours']['min'] < $filter['close_took_hours']['max'])
            {
                $where[] = '`close_time` - `time` BETWEEN ' . ($filter['close_took_hours']['min'] * 60 * 60) . ' AND ' . ($filter['close_took_hours']['max'] * 60 * 60);
            }
            else
            {
                $where[] = '`close_time` - `time` > ' . ($filter['close_took_hours']['min'] * 60 * 60);
            }
        }

        if ($count)
        {
            $count = ($filter['distinct']) ? 'DISTINCT `' . $this->quote($filter['distinct']) . '`' : '*';

            $query = 'SELECT COUNT(' . $count . ') AS count FROM ' . get_table('ticket');

            if ($where)
            {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $result = $this->query_row($query);

            return $result['count'];
        }

        if ($page AND $per_page)
        {
            return $this->fetch_page('ticket', implode(' AND ', $where), 'time DESC', $page, $per_page);
        }

        $tickets_list_query = $this->fetch_all('ticket', implode(' AND ', $where));

        $tickets_list = array();

        if ($tickets_list_query)
        {
            foreach ($tickets_list_query AS $ticket_info)
            {
                $tickets_list[$ticket_info['id']] = $ticket_info;
            }
        }

        return $tickets_list;
    }

    public function get_replies_list_by_ticket_id($ticket_id, $page, $per_page)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info)
        {
            return false;
        }

        return $this->fetch_page('ticket_reply', 'ticket_id = ' . $ticket_id, 'time ASC', $page, $per_page);
    }

    public function save_ticket($title, $message, $uid, $attach_access_key = null, $from = null)
    {
        $to_save_ticket = array(
            'title' => htmlspecialchars($title),
            'message' => htmlspecialchars($message),
            'time' => time(),
            'uid' => intval($uid),
            'ip' => ip2long(fetch_ip()),
            'priority' => 'normal',
            'status' => 'pending'
        );

        if ($from AND is_array($from))
        {
            foreach ($from AS $type => $from_id)
            {
                if (!is_digits($from_id))
                {
                    continue;
                }

                $to_save_ticket['source'] = $type;

                $to_save_ticket[$type . '_id'] = $from_id;
            }
        }
        else if (in_weixin())
        {
            $to_save_ticket['source'] = 'weixin';
        }

        $ticket_id = $this->insert('ticket', $to_save_ticket);

        if ($ticket_id)
        {
            set_human_valid('question_valid_hour');

            if ($attach_access_key)
            {
                $this->model('publish')->update_attach('ticket', $ticket_id, $attach_access_key);
            }

            if ($from AND is_array($from))
            {
                foreach ($from AS $type => $from_id)
                {
                    if (!is_digits($from_id))
                    {
                        continue;
                    }

                    $this->update($type, array(
                        'ticket_id' => $question_id
                    ), 'id = ' . $from_id);
                }
            }
        }

        return $ticket_id;
    }

    public function remove_ticket($id)
    {
        $ticket_info = $this->get_ticket_info_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        $this->delete('ticket', 'id = ' . $ticket_info['id']);

        $this->delete('ticket_reply', 'ticket_id = ' . $ticket_info['id']);

        $this->delete('ticket_log', 'ticket_id = ' . $ticket_info['id']);

        $this->delete('ticket_invite', 'ticket_id = ' . $ticket_info['id']);

        $attachs = $this->model('publish')->get_attach('ticket', $ticket_info['id']);

        if ($attachs)
        {
            foreach ($attachs AS $attach)
            {
                $this->model('publish')->remove_attach($attach['id'], $attach['access_key']);
            }
        }

        if ($ticket_info['weibo_msg_id'])
        {
            if ($ticket_info['question_id'])
            {
                remove_assoc('weibo_msg', 'ticket', $ticket_info['id']);
            }
            else
            {
                $this->model('openid_weibo_weibo')->del_msg_by_id($ticket_info['weibo_msg_id']);
            }
        }

        if ($ticket_info['received_email_id'])
        {
            if ($ticket_info['question_id'])
            {
                remove_assoc('received_email', 'ticket', $ticket_info['id']);
            }
            else
            {
                $this->model('edm')->remove_received_email($ticket_info['received_email_id']);
            }
        }

        if ($ticket_info['question_id'])
        {
            remove_assoc('question', 'ticket', $ticket_info['id']);
        }

        return true;
    }

    public function reply_ticket($ticket_id, $message, $uid, $attach_access_key = null)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info OR $ticket_info['status'] == 'closed')
        {
            return false;
        }

        $now = time();

        $reply_id = $this->insert('ticket_reply', array(
            'ticket_id' => $ticket_info['id'],
            'message' => htmlspecialchars($message),
            'uid' => intval($uid),
            'time' => $now,
            'uid' => intval($uid),
            'ip' => ip2long(fetch_ip())
        ));

        if (!$reply_id)
        {
            return false;
        }

        set_human_valid('answer_valid_hour');

        if ($attach_access_key)
        {
            $this->model('publish')->update_attach('ticket_reply', $reply_id, $attach_access_key);
        }

        if (!$ticket_info['reply_time'])
        {
            $this->shutdown_update('ticket', array('reply_time' => $now), 'id = ' . $ticket_info['id']);
        }

        if ($ticket_info['weibo_msg_id'])
        {
            $this->model('openid_weibo_weibo')->reply_answer_to_sina($question_info['question_id'], cjk_substr($answer_content, 0, 110, 'UTF-8', '...'));
        }

        if ($ticket_info['received_email_id'])
        {
            $this->model('edm')->reply_answer_by_email($question_info['question_id'], nl2br(FORMAT::parse_bbcode($answer_content)));
        }

        return $reply_id;
    }

    public function get_ticket_reply_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        return $this->fetch_row('ticket_reply', 'id = ' . $id);
    }

    public function remove_ticket_reply($id)
    {
        $reply_info = $this->get_ticket_reply_by_id($id);

        if (!$reply_info)
        {
            return false;
        }

        $attachs = $this->model('publish')->get_attach('ticket_reply', $reply_info['id']);

        if ($attachs)
        {
            foreach ($attachs as $attach)
            {
                $this->model('publish')->remove_attach($attach['id'], $attach['access_key']);
            }
        }

        return $this->delete('ticket_reply', 'id = ' . $reply_info['id']);
    }

    public function change_priority($id, $uid, $priority)
    {
        $ticket_info = $this->get_ticket_info_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        if (!in_array($priority, array('low', 'normal', 'high', 'urgent')))
        {
            return false;
        }

        if ($ticket_info['priority'] == $priority)
        {
            return true;
        }

        $this->update('ticket', array('priority' => $priority), 'id = ' . $ticket_info['id']);

        $this->save_ticket_log($ticket_info['id'], $uid, 'change_priority', array(
            'old' => $ticket_info['priority'],
            'new' => $priority
        ));

        return true;
    }

    public function change_status($id, $uid, $status)
    {
        $ticket_info = $this->get_ticket_info_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        if (!in_array($status, array('pending', 'closed')))
        {
            return false;
        }

        if ($ticket_info['status'] == $status)
        {
            return true;
        }

        $this->update('ticket', array('status' => $status), 'id = ' . $ticket_info['id']);

        $this->save_ticket_log($ticket_info['id'], $uid, 'change_status', array(
            'old' => $ticket_info['status'],
            'new' => $status
        ));

        if (!$ticket_info['close_time'])
        {
            $this->shutdown_update('ticket', array('close_time' => time()), 'id = ' . $ticket_info['id']);
        }

        return true;
    }

    public function change_rating($id, $uid, $rating)
    {
        $ticket_info = $this->get_ticket_info_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        if (!in_array($rating, array('valid', 'invalid', 'undefined')))
        {
            return false;
        }

        if ($ticket_info['rating'] == $rating)
        {
            return true;
        }

        $this->update('ticket', array('rating' => $rating), 'id = ' . $ticket_info['id']);

        $this->save_ticket_log($ticket_info['id'], $uid, 'change_rating', array(
            'old' => $ticket_info['rating'],
            'new' => $rating
        ));

        return true;
    }

    public function save_ticket_log($ticket_id, $uid, $action, $data)
    {
        switch ($action)
        {
            case 'change_priority':
            case 'change_status':
            case 'change_rating':
                return $this->insert('ticket_log', array(
                    'ticket_id' => $ticket_id,
                    'uid' => $uid,
                    'action' => $action,
                    'data' => serialize($data),
                    'time' => time()
                ));

            default:
                return false;
        }
    }

    public function parse_ticket_log($ticket_id)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info)
        {
            return false;
        }

        $log_data = $this->fetch_all('ticket_log', 'ticket_id = ' . $ticket_info['id'], 'time DESC');

        if (!$log_data)
        {
            return false;
        }

        $ticket_log = array();

        foreach ($log_data AS $log_info)
        {
            $log_info['data'] = unserialize($log_info['data']);

            switch ($log_info['action'])
            {
                case 'change_priority':
                    $ticket_log[] = array(
                        'uid' => $log_info['uid'],

                        'text' => AWS_APP::lang()->_t('把优先级从 %s0 改为 %s1', array(
                            $this->translate($log_info['data']['old']),
                            $this->translate($log_info['data']['new'])
                        )),

                        'time' => $log_info['time']
                    );

                    break;

                case 'change_status':
                    $ticket_log[] = array(
                        'uid' => $log_info['uid'],

                        'text' => AWS_APP::lang()->_t('%s了工单', $this->translate($log_info['data']['new'])),

                        'time' => $log_info['time']
                    );

                    break;

                case 'change_rating':
                    $ticket_log[] = array(
                        'uid' => $log_info['uid'],

                        'text' => AWS_APP::lang()->_t('把评级从 %s0 改为 %s1', array(
                            $this->translate($log_info['data']['old']),
                            $this->translate($log_info['data']['new'])
                        )),

                        'time' => $log_info['time']
                    );

                    break;

                default:
                    continue;
            }
        }

        $ticket_log[] = array(
            'uid' => $ticket_info['uid'],
            'text' => AWS_APP::lang()->_t('创建了工单'),
            'time' => $ticket_info['time']
        );

        return $ticket_log;
    }

    public function translate($string)
    {
        switch ($string)
        {
            case 'low':
                return AWS_APP::lang()->_t('低');

            case 'normal':
                return AWS_APP::lang()->_t('中');

            case 'high':
                return AWS_APP::lang()->_t('高');

            case 'urgent':
                return AWS_APP::lang()->_t('紧急');

            case 'pending':
                return AWS_APP::lang()->_t('打开');

            case 'closed':
                return AWS_APP::lang()->_t('关闭');

            case 'valid':
                return AWS_APP::lang()->_t('有效');

            case 'invalid':
                return AWS_APP::lang()->_t('无效');

            case 'undefined':
                return AWS_APP::lang()->_t('未评级');

            default:
                return AWS_APP::lang()->_t($string);
        }
    }

    public function get_invite_users($ticket_id)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info)
        {
            return false;
        }

        return $this->fetch_all('ticket_invite', 'ticket_id = ' . $ticket_info['id']);
    }

    public function invite_user($ticket_id, $sender_uid, $recipient_uid)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info OR !is_digits($sender_uid))
        {
            return false;
        }

        $recipient_info = $this->model('account')->get_user_info_by_uid($recipient_uid);

        if (!$recipient_info OR $this->has_invited($ticket_info['id'], $recipient_info['uid']))
        {
            return false;
        }

        return $this->insert('ticket_invite', array(
            'ticket_id' => $ticket_info['id'],
            'sender_uid' => $sender_uid,
            'recipient_uid' => $recipient_info['uid'],
            'time' => time()
        ));
    }

    public function has_invited($ticket_id, $recipient_uid)
    {
        if (!is_digits($ticket_id) OR !is_digits($recipient_uid))
        {
            return false;
        }

        return $this->fetch_one('ticket_invite', 'recipient_uid', 'ticket_id = ' . $ticket_id . ' AND recipient_uid = ' . $recipient_uid);
    }

    public function cancel_invite($ticket_id, $recipient_uid)
    {
        if (!is_digits($ticket_id) OR !is_digits($recipient_uid))
        {
            return false;
        }

        return $this->delete('ticket_invite', 'ticket_id = ' . $ticket_id . ' AND recipient_uid = ' . $recipient_uid);
    }

    public function assign_service($ticket_id, $service_uid)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info OR $ticket_info['status'] == 'closed' OR $ticket_info['service'] == $service_uid)
        {
            return false;
        }

        $user_info = $this->model('account')->get_user_info_by_uid($service_uid);

        if (!$user_info)
        {
            return false;
        }

        $user_group_info = $this->model('account')->get_user_group_by_id($user_info['group_id']);

        if (!$user_group_info['permission']['is_administortar'] AND !$user_group_info['permission']['is_service'])
        {
            return false;
        }

        return $this->update('ticket', array('service' => $user_info['uid']), 'id = ' . $ticket_info['id']);
    }

    public function count_ticket_replies($ticket_id = null, $uid = null, $days = null)
    {
        $where = array();

        if (is_digits($ticket_id))
        {
            $where[] = 'ticket_id = ' . $ticket_id;
        }

        if (is_digits($uid))
        {
            $where[] = 'uid = ' . $uid;
        }

        if (is_digits($days))
        {
            $where[] = 'time > '. (time() - $days * 24 * 60 * 60);
        }

        return $this->count('ticket_reply', implode(' AND ', $where));
    }

    public function ticket_statistic($filter, $days)
    {
        if (!is_digits($days))
        {
            return false;
        }

        switch ($filter)
        {
            case 'new_ticket':
                $query = 'SELECT COUNT(*) AS count, FROM_UNIXTIME(`time`, "%Y-%m-%d") AS statistic_date FROM ' . get_table('ticket') . ' WHERE `time` BETWEEN ' . strtotime('-' . $days . ' days') . ' AND ' . time() . ' GROUP BY statistic_date ASC';

                break;

            case 'closed_ticket':
                $query = 'SELECT COUNT(*) AS count, FROM_UNIXTIME(`close_time`, "%Y-%m-%d") AS statistic_date FROM ' . get_table('ticket') . ' WHERE `close_time` BETWEEN ' . strtotime('-' . $days . ' days') . ' AND ' . time() . ' GROUP BY statistic_date ASC';

                break;

            case 'pending_ticket':
                $query = 'SELECT COUNT(*) AS count, FROM_UNIXTIME(`close_time`, "%Y-%m-%d") AS close_date FROM ' . get_table('ticket') . ' WHERE `close_time` BETWEEN ' . strtotime('-' . ($days + 1) . ' days') . ' AND ' . strtotime('Today') . ' OR `close_time` = 0 GROUP BY close_date ASC';

                break;

            case 'ticket_replies':
                $query = 'SELECT COUNT(*) AS count, FROM_UNIXTIME(`time`, "%Y-%m-%d") AS statistic_date FROM ' . get_table('ticket_reply') . ' WHERE `time` BETWEEN ' . strtotime('-' . $days . ' days') . ' AND ' . time() . ' GROUP BY statistic_date ASC';

                break;

            default:
                return false;
        }

        $data = array();

        $result = $this->query_all($query);

        if ($result)
        {
            if ($filter == 'pending_ticket')
            {
                for ($i=0; $i<=$days; $i++)
                {
                    $date = gmdate('Y-m-d', strtotime('-' . ($days - $i). ' days'));

                    $data[$i] = 0;

                    foreach ($result AS $val)
                    {
                        if ($val['close_date'] == '1970-01-01' OR strtotime($val['close_date']) > strtotime($date))
                        {
                            $data[$i] += $val['count'];
                        }
                    }
                }
            }
            else
            {
                foreach ($result AS $val)
                {
                    $res[$val['statistic_date']] = $val;
                }

                for ($i=0; $i<=$days; $i++)
                {
                    $date = gmdate('Y-m-d', strtotime('-' . ($days - $i). ' days'));

                    $data[$i] = 0;

                    if ($res[$date])
                    {
                        $data[$i] += $res[$date]['count'];
                    }
                }
            }
        }

        return $data;
    }

    public function get_hot_topics($days = null, $page = null, $per_page = null, $count = false)
    {

        $time = (is_digits($days)) ? ' AND `add_time` > '. (time() - $days * 24 * 60 * 60) : '';

        if ($count)
        {
            $result = $this->query_row('SELECT count(DISTINCT `topic_id`) AS `count` FROM `' . $this->get_table('topic_relation') . '` WHERE `type` = "ticket"' . $time);

            return $result['count'];
        }

        $limit = ($page AND $per_page) ? ' LIMIT ' . calc_page_limit($page, $per_page) : '';

        $hot_topics_query = $this->query_all('SELECT `topic_id`, count(*) AS `count` FROM `' . $this->get_table('topic_relation') . '` WHERE `type` = "ticket"' . $time . ' GROUP BY `topic_id` ORDER BY `count` DESC' . $limit);

        if ($hot_topics_query)
        {
            foreach ($hot_topics_query AS $hot_topic)
            {
                $topic_ids[] = $hot_topic['topic_id'];
            }

            $tickets_query = $this->query_all('SELECT `topic_id`, `item_id` FROM `' . $this->get_table('topic_relation') . '` WHERE `topic_id` IN (' . implode(', ', $topic_ids) . ')');

            foreach ($tickets_query AS $val)
            {
                $ticket_ids[] = $val['item_id'];

                $topic_ticket[$val['topic_id']][] = $val['item_id'];
            }

            $hot_topics_list = $this->model('topic')->get_topics_by_ids($topic_ids);

            $tickets_list = $this->get_tickets_list(array('ids' => $ticket_ids));

            $hot_topics = array();

            foreach ($hot_topics_query AS $val)
            {
                $hot_topics[$val['topic_id']] = $hot_topics_list[$val['topic_id']];

                $hot_topics[$val['topic_id']]['tickets_count'] = $val['count'];

                $hot_topics[$val['topic_id']]['unassigned_tickets_count'] = 0;
                $hot_topics[$val['topic_id']]['pending_tickets_count'] = 0;
                $hot_topics[$val['topic_id']]['closed_tickets_count'] = 0;

                foreach ($tickets_list AS $ticket_info)
                {
                    if (in_array($ticket_info['id'], $topic_ticket[$val['topic_id']]))
                    {
                        if ($ticket_info['status'] == 'closed')
                        {
                            $hot_topics[$val['topic_id']]['closed_tickets_count']++;
                        }
                        else if ($ticket_info['service'])
                        {
                            $hot_topics[$val['topic_id']]['pending_tickets_count']++;
                        }
                        else
                        {
                            $hot_topics[$val['topic_id']]['unassigned_tickets_count']++;
                        }
                    }
                }
            }
        }

        return $hot_topics;
    }

    public function add_service_group($group_name)
    {
        $group_name = trim($group_name);

        if (!$group_name)
        {
            return false;
        }

        return $this->insert('users_group', array(
            'type' => 2,
            'custom' => 2,
            'group_name' => $group_name,
            'permission' => 'a:15:{s:16:"publish_question";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:13:"edit_question";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"manage_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:13:"upload_attach";s:1:"1";s:11:"publish_url";s:1:"1";s:15:"publish_article";s:1:"1";s:12:"edit_article";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:15:"publish_comment";s:1:"1";s:10:"is_service";s:1:"1";s:14:"publish_ticket";s:1:"1";}'
        ));
    }

    public function edit_service_group($group_id, $group_name)
    {
        $group_name = trim($group_name);

        if (!$group_name)
        {
            return false;
        }

        $group_info = $this->model('account')->get_user_group_by_id($group_id);

        if (!$group_info OR $group_info['type'] != 2 OR $group_info['custom'] != 2)
        {
            return false;
        }

        return $this->model('account')->update_user_group_data($group_info['group_id'], array('group_name' => $group_name));
    }

    public function remove_service_group($group_id)
    {
        $group_info = $this->model('account')->get_user_group_by_id($group_id);

        if (!$group_info OR $group_info['type'] != 2 OR $group_info['custom'] != 2)
        {
            return false;
        }

        return $this->model('account')->delete_user_group_by_id($group_info['group_id']);
    }

    public function service_group_statistic($months = null)
    {
        if (!is_digits($months))
        {
            return false;
        }

        $data = array();

        $groups_list = $this->model('account')->get_user_group_list(2, 2);

        if ($groups_list)
        {
            for ($i=0; $i<=$months; $i++)
            {
                $data_by_month[] = array(
                    'month' => gmdate('Y-m', strtotime('first day of ' . ($months - $i) . ' months ago')),
                    'count' => 0
                );
            }

            $time = ' AND time > '. strtotime('first day of ' . $months . ' months ago');

            foreach ($groups_list AS $group_info)
            {
                $data[$group_info['group_id']] = array(
                    'group_id' => $group_info['group_id'],
                    'group_name' => $group_info['group_name'],
                    'tickets_count' => $data_by_month
                );
            }

            $service_tickets_count_query = $this->query_all('SELECT `service`, COUNT(*) AS count, FROM_UNIXTIME(`close_time`, "%Y-%m") AS `statistic_month` FROM ' . get_table('ticket') . ' WHERE `service` <> 0' . $time . ' GROUP BY `service` ASC, `statistic_month` ASC');

            if ($service_tickets_count_query)
            {
                foreach ($service_tickets_count_query AS $val)
                {
                    $service_uids[] = $val['service'];

                    $service_tickets_count_by_service[$val['service']][$val['statistic_month']] = $val['count'];
                }

                $service_list = $this->model('account')->get_user_info_by_uids($service_uids);

                foreach ($service_list AS $service_info)
                {
                    if ($service_info['group_id'] AND $data[$service_info['group_id']] AND $service_tickets_count_by_service[$service_info['uid']])
                    {
                        foreach ($data[$service_info['group_id']]['tickets_count'] AS $key => $group_data)
                        {
                            if ($service_tickets_count_by_service[$service_info['uid']][$group_data['month']])
                            {
                                $data[$service_info['group_id']]['tickets_count'][$key]['count'] += $service_tickets_count_by_service[$service_info['uid']][$group_data['month']];
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function get_question_info_by_ticket_id($ticket_id)
    {
        if (!is_digits($ticket_id))
        {
            return false;
        }

        return $this->fetch_row('question', 'ticket_id = ' . $ticket_id);
    }

    public function save_ticket_to_question($ticket_id)
    {
        $ticket_info = $this->get_ticket_info_by_id($ticket_id);

        if (!$ticket_info)
        {
            return false;
        }

        $topics_query = $this->model('topic')->get_topics_by_item_id($ticket_info['id'], 'ticket');

        $topics = array();

        if ($topics_query)
        {
            foreach ($topics_query AS $topic_info)
            {
                $topics[] = $topic_info['topic_title'];
            }
        }

        $from = array(
            'ticket' => $ticket_info['id']
        );

        if ($ticket_info['weibo_msg_id'])
        {
            $from['weibo_msg'] = $ticket_info['weibo_msg_id'];
        }

        if ($ticket_info['received_email_id'])
        {
            $from['received_email'] = $ticket_info['received_email'];
        }

        $attach_access_key = $this->copy_attach('ticket', 'question', $ticket_info['id'], $ticket_info['uid']);

        $question_id = $this->model('publish')->publish_question(htmlspecialchars_decode($ticket_info['title']), htmlspecialchars_decode($ticket_info['message']), null, $ticket_info['uid'], $topics, null, $attach_access_key, null, false, $from);

        if (!$question_id)
        {
            return false;
        }

        $ticket_replies = $this->get_replies_list_by_ticket_id($ticket_info['id'], 1, null);

        if ($ticket_replies)
        {
            foreach ($ticket_replies AS $reply_info)
            {
                $attach_access_key = $this->copy_attach('ticket_reply', 'answer', $reply_info['id'], $reply_info['uid']);

                $this->model('publish')->publish_answer($question_id, htmlspecialchars_decode($reply_info['message']), $reply_info['uid'], null, $attach_access_key, true, false);
            }
        }

        return $question_id;
    }

    public function save_weibo_msg_to_ticket_crond()
    {
        $weibo_msgs_list = $this->fetch_all('weibo_msg', 'question_id IS NULL AND ticket_id IS NULL');

        if (!$weibo_msgs_list)
        {
            return true;
        }

        foreach ($weibo_msgs_list AS $weibo_msg)
        {
            $attach_access_key = $this->copy_attach('weibo_msg', 'ticket', $weibo_msg['id'], $weibo_msg['uid']);

            $this->save_ticket($weibo_msg['text'], null, $weibo_msg['uid'], $attach_access_key, array(
                'weibo_msg' => $weibo_msg['id']
            ));
        }
    }

    public function save_received_email_to_ticket_crond()
    {
        $received_email_list = $this->fetch_all('received_email', 'question_id IS NULL AND ticket_id IS NULL');

        if (!$received_email_list)
        {
            return true;
        }

        foreach ($received_email_list AS $received_email)
        {
            $this->save_ticket($received_email['subject'], $received_email['content'], $received_email['uid'], null, array(
                'received_email' => $received_email['id']
            ));
        }

        return true;
    }

    public function copy_attach($old_type, $new_type, $id, $uid)
    {
        $attachs = $this->model('publish')->get_attach($old_type, $id, null);

        if (!$attachs)
        {
            return false;
        }

        if ($now)
        {
            $now += 1;
        }
        else
        {
            static $now;

            $now = time();
        }

        $attach_access_key = md5($uid . $now);

        foreach ($attachs AS $attach)
        {
            $new_dir = get_setting('upload_dir') . '/' . $new_type . (($new_type == 'question') ? 's' : '') . '/' . gmdate('Ymd', $now) . '/';

            $file_ext = end(explode('.', $attach['file_location']));

            $new_filename = get_random_filename($new_dir, $file_ext);

            if (@make_dir($new_dir) AND @copy($attach['path'], $new_dir . $new_filename))
            {
                if ($attach['is_image'])
                {
                    $old_dir = dirname($attach['path']) . '/';

                    foreach (AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
                    {
                        $thumb_pre = $val['w'] . 'x' . $val['h'] . '_';

                        @copy($old_dir . $thumb_pre . $attach['file_location'], $new_dir . $thumb_pre . $new_filename);
                    }
                }

                $this->model('publish')->add_attach($new_type, $attach['file_name'], $attach_access_key, $now, $new_filename, $attach['is_image']);
            }
        }

        return $attach_access_key;
    }
}
