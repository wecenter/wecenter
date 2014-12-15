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
            $where[] = '`reply_time` <> 0';

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
            $where[] = '`close_time` <> 0';

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

        return $this->fetch_page('ticket', implode(' AND ', $where), 'time DESC', $page, $per_page);
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

    public function save_ticket($title, $message, $uid, $attach_access_key = null, $from = null, $from_id = null)
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

        if ($from AND is_digits($from_id))
        {
            $to_save_ticket[$from . '_id'] = $from_id;
        }

        $ticket_id = $this->insert('ticket', $to_save_ticket);

        if ($ticket_id)
        {
            set_human_valid('question_valid_hour');

            if ($attach_access_key)
            {
                if ($weibo_msg_id)
                {
                    $this->model('weibo')->update_attach($weibo_msg_id, $ticket_id, $attach_access_key);
                }
                else
                {
                    $this->model('publish')->update_attach('ticket', $ticket_id, $attach_access_key);
                }
            }

            if ($from AND is_digits($from_id))
            {
                $this->update($from, array(
                    'ticket_id' => $ticket_id
                ), 'id = ' . $from_id);
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

        $this->delete('ticket_log', 'ticket_id = ' . $ticket_info['id']);

        $this->delete('ticket_log', 'ticket_invite = ' . $ticket_info['id']);

        $attachs = $this->model('publish')->get_attach('ticket', $question_id);

        if ($attachs)
        {
            foreach ($attachs as $attach)
            {
                $this->model('publish')->remove_attach($attach['id'], $attach['access_key']);
            }
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

        if ($reply_id)
        {
            set_human_valid('answer_valid_hour');

            if ($attach_access_key)
            {
                $this->model('publish')->update_attach('ticket_reply', $reply_id, $attach_access_key);
            }

            if (!$ticket_info['reply_time'])
            {
                $this->shutdown_update('ticket', array('reply_time' => $now), 'id = ' . $ticket_info['id']);
            }
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

        if ($reply_info)
        {
            return false;
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

        $recipient_info = $this->model('account')->get_user_info_by_uid($recipient_uid);

        if (!$ticket_info OR !is_digits($sender_uid) OR !$recipient_info)
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

        $user_info = $this->model('account')->get_user_info_by_uid($service_uid);

        if (!$ticket_info OR !$user_info OR $user_info['group_id'] != 1 AND $user_info['group_id'] != 10)
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

        $result = $this->query_all($query);

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

        return $data;
    }
}
