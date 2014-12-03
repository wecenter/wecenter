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
    public function get_ticket_by_id($id)
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

    public function get_tickets_by_priority($priority, $date = null)
    {
        $where[] = 'priority = "' . $this->quote($priority);

        if ($date)
        {
            $where[] = 'time > '. (time() - $date * 24 * 60 * 60);
        }

        return $this->fetch_all('ticket', implode(' AND ', $where) . '"');
    }

    public function get_tickets_by_status($status, $date = null)
    {
        $where[] = 'status = "' . $this->quote($status);

        if ($date)
        {
            $where[] = 'time > '. (time() - $date * 24 * 60 * 60);
        }

        return $this->fetch_all('ticket', implode(' AND ', $where) . '"');
    }

    public function save_ticket($title, $message, $uid, $attach_access_key = null, $from = null, $from_id = null)
    {
        if (!$ip_address)
        {
            $ip_address = fetch_ip();
        }

        $to_save_ticket = array(
            'title' => htmlspecialchars($title),
            'message' => htmlspecialchars($message),
            'time' => time(),
            'uid' => intval($uid)
            'ip' => ip2long($ip_address),
            'priority' => 'normal',
            'status' => 'pending'
        );

        if ($from AND is_digits($from_id))
        {
            $to_save_ticket[$from . '_id'] = $from_id;
        }

        $ticket_id = $this->insert('question', $to_save_ticket);

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
        $ticket_info = $this->get_ticket_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        $this->delete('ticket', 'id = ' . $ticket_info['id']);

        $this->delete('ticket_log', 'ticket_id' = $ticket_info['id']);

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

    public function change_priority($id, $uid, $priority)
    {
        $ticket_info = $this->get_ticket_by_id($id);

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

        $this->save_modify_log($ticket_info['id'], $uid, 'change_priority', array(
            'old' => $ticket_info['priority'],
            'new' => $priority
        ));

        return true;
    }

    public function change_status($id, $uid, $status)
    {
        $ticket_info = $this->get_ticket_by_id($id);

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

        $this->update('ticket', array('status' => $status), 'id = ' . $ticket_info['id'], array(
            'old' => $ticket_info['status'],
            'new' => $status
        ));

        $this->save_modify_log($ticket_info['id'], $uid, 'change_status');

        return true;
    }

    public function change_rating($id, $uid, $rating)
    {
        $ticket_info = $this->get_ticket_by_id($id);

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

        $this->save_modify_log($ticket_info['id'], $uid, 'change_rating', array(
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
        $ticket_info = $this->get_ticket_by_id($id);

        if (!$ticket_info)
        {
            return false;
        }

        $log_data = $this->fetch_all('ticket_log', 'ticket_id = ' . $ticket_info['id']);

        if (!$log_data)
        {
            return false;
        }

        $log_data['data'] = unserialize($log_data['data']);

        foreach ($log_data AS $log_info)
        {
            $uids[] = $log_info['uid'];
        }

        $users_info = $this->model('account')->get_user_info_by_uids($uids);

        $ticket_log = array();

        foreach ($log_data AS $log_info)
        {
            switch ($log_info['action'])
            {
                case 'change_priority':
                    $ticket_log[] = array(
                        'text' => AWS_APP::lang()->_t('%s 把优先级从 %s 改为 %s', array(
                            '<a href="' . get_js_url('/people/' . $users_info[$log_info['uid']]['url_token']) . '" target="_blank">' . $users_info[$log_info['uid']]['user_name'] . '</a>',
                            $this->translate($log_data['data']['old']),
                            $this->translate($log_data['data']['new']))
                        ),

                        'time' => $log_data['time']
                    )

                    break;

                case 'change_status':
                    $ticket_log[] = array(
                        'text' => AWS_APP::lang()->_t('%s %s了工单', array(
                            '<a href="' . get_js_url('/people/' . $users_info[$log_info['uid']]['url_token']) . '" target="_blank">' . $users_info[$log_info['uid']]['user_name'] . '</a>',
                            $this->translate($log_data['data']['new']))
                        ),

                        'time' => $log_data['time']
                    )

                    break;

                case 'change_rating':
                    $ticket_log[] = array(
                        'text' => AWS_APP::lang()->_t('%s 把评级从 %s 改为 %s', array(
                            '<a href="' . get_js_url('/people/' . $users_info[$log_info['uid']]['url_token']) . '" target="_blank">' . $users_info[$log_info['uid']]['user_name'] . '</a>',
                            $this->translate($log_data['data']['old']),
                            $this->translate($log_data['data']['new']))
                        ),

                        'time' => $log_data['time']
                    )

                    break;

                default:
                    continue;
            }
        }

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
}
