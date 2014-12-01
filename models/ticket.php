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

    public function save_ticket($title, $message, $uid, $topics = null, $priority = 'normal', $attach_access_key = null, $create_topic = true, $from = null, $from_id = null)
    {
        if (!in_array($priority, array('low', 'normal', 'high', 'urgent')))
        {
            $priority = 'normal';
        }

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
            'priority' => $priority,
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

            if (is_array($topics))
            {
                foreach ($topics AS $topic_title)
                {
                    $topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);

                    $this->model('topic')->save_topic_relation($uid, $topic_id, $ticket_id, 'ticket');
                }
            }

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
        if (!is_digits($id))
        {
            return false;
        }

        if (is_array($id))
        {
            $where = 'id IN (' . implode(', ', $id) . ')';
        }
        else
        {
            $where = 'id = ' . $id;
        }

        return $this->delete('ticket', $where);
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

    public function save_modify_log($ticket_id, $uid, $action, $data)
    {

    }
}
