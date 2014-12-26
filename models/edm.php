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

class edm_class extends AWS_MODEL
{
    public function fetch_groups($page = null, $limit = null)
    {
        return $this->fetch_page('edm_usergroup', null, 'id DESC', $page, $limit);
    }

    public function fetch_tasks($page, $limit)
    {
        return $this->fetch_page('edm_task', null, 'id DESC', $page, $limit);
    }

    public function add_task($title, $subject, $message, $from_name)
    {
        return $this->insert('edm_task', array(
            'title' => htmlspecialchars($title),
            'subject' => htmlspecialchars($subject),
            'message' => $message,
            'from_name' => htmlspecialchars($from_name),
            'time' => time()
        ));
    }

    public function get_task_info($task_id)
    {
        return $this->fetch_row('edm_task', 'id = ' . intval($task_id));
    }

    public function calc_task_users($task_id)
    {
        return $this->count('edm_taskdata', 'taskid = ' . intval($task_id));
    }

    public function calc_task_views($task_id)
    {
        return $this->count('edm_taskdata', 'view_time > 0 AND taskid = ' . intval($task_id));
    }

    public function calc_task_sent($task_id)
    {
        return $this->count('edm_taskdata', 'sent_time > 0 AND taskid = ' . intval($task_id));
    }

    public function calc_group_users($group_id)
    {
        return $this->count('edm_userdata', 'usergroup = ' . intval($group_id));
    }

    public function remove_group($group_id)
    {
        $this->delete('edm_userdata', 'usergroup = ' . intval($group_id));
        $this->delete('edm_usergroup', 'id = ' . intval($group_id));

        return true;
    }

    public function remove_task($task_id)
    {
        $this->delete('edm_taskdata', 'taskid = ' . intval($task_id));
        $this->delete('edm_task', 'id = ' . intval($task_id));

        return true;
    }

    public function fetch_task_active_emails($task_id)
    {
        return $this->fetch_all('edm_taskdata', "view_time > 0 AND taskid = " . intval($task_id));
    }

    public function add_group($title)
    {
        return $this->insert('edm_usergroup', array(
            'title' => htmlspecialchars($title),
            'time' => time()
        ));
    }

    public function set_task_view($task_id, $email)
    {
        return $this->update('edm_taskdata', array(
            'view_time' => time(),
        ), "email = '" . $this->quote($email) . "'");
    }

    public function add_user_data($group_id, $email)
    {
        if (!H::valid_email($email) OR $this->is_unsubscription($email))
        {
            return false;
        }

        if ($this->fetch_row('edm_userdata', 'usergroup = ' . intval($group_id) . " AND email = '" . $this->quote(strtolower($email)) . "'"))
        {
            return false;
        }

        return $this->insert('edm_userdata', array(
            'usergroup' => $group_id,
            'email' => strtolower($email)
        ));
    }

    public function run_task()
    {
        if (!$user_list = $this->fetch_all('edm_taskdata', "`sent_time` = 0", "id ASC", 30))
        {
            return false;
        }

        foreach ($user_list AS $key => $item)
        {
            if (!$task_data[$item['taskid']]['id'])
            {
                $task_data[$item['taskid']] = $this->get_task_info($item['taskid']);
            }

            if ($task_data[$item['taskid']]['from_name'])
            {
                $from_name = $task_data[$item['taskid']]['from_name'];
            }

            $task_data[$item['taskid']]['message'] = str_replace('[#UNSUBSCRIPTION_LINK#]', get_js_url('/account/edm/unsubscription/' . urlencode(base64_encode($item['email'])) . ',' . md5($item['email'] . G_SECUKEY)), $task_data[$item['taskid']]['message']);

            $message = $task_data[$item['taskid']]['message'] . '<p><center>为确保我们的邮件不被当做垃圾邮件处理，请把 ' . get_setting('from_email') . ' 添加为你的联系人。</center></p><p><center>如果内容显示不正确, 请<a href="' . get_js_url('/account/edm/mail/' . $item['taskid']) . '">点此查看在线版</a>。<img src="' . get_js_url('/account/edm/ping/' . urlencode(base64_encode($item['email'])) . '|' . md5($item['email'] . G_SECUKEY)) . '|' . $item['taskid'] . '" alt="" width="1" height="1" /></center></p>';

            $this->update('edm_taskdata', array(
                'sent_time' => time()
            ), 'id = ' . $item['id']);

            AWS_APP::mail()->send($item['email'], $task_data[$item['taskid']]['subject'], $message, $from_name, null, 'slave');
        }

        return true;
    }

    public function import_system_email_by_reputation_group($group_id, $user_group_id)
    {
        return $this->query("INSERT INTO `" . get_table('edm_userdata') . "` (`usergroup`, `email`) SELECT '" . intval($group_id) . "' ,  `email` FROM `" . get_table('users') . "` WHERE email != '' AND reputation_group = " . intval($user_group_id));
    }

    public function import_system_email_by_user_group($group_id, $user_group_id)
    {
        return $this->query("INSERT INTO `" . get_table('edm_userdata') . "` (`usergroup`, `email`) SELECT '" . intval($group_id) . "' ,  `email` FROM `" . get_table('users') . "` WHERE email != '' AND group_id = " . intval($user_group_id));
    }

    public function import_system_email_by_last_active($group_id, $last_active)
    {
        return $this->query("INSERT INTO `" . get_table('edm_userdata') . "` (`usergroup`, `email`) SELECT '" . intval($group_id) . "' ,  `email` FROM `" . get_table('users') . "` WHERE email != '' AND last_active > " . (time() - intval($last_active)));
    }

    public function import_system_email_by_last_login($group_id, $last_active)
    {
        return $this->query("INSERT INTO `" . get_table('edm_userdata') . "` (`usergroup`, `email`) SELECT '" . intval($group_id) . "' ,  `email` FROM `" . get_table('users') . "` WHERE email != '' AND last_login < " . (time() - intval($last_active)));
    }

    public function import_group_data_to_task($task_id, $user_group_id)
    {
        return $this->query("INSERT INTO `" . get_table('edm_taskdata') . "` (`taskid`, `email`) SELECT '" . intval($task_id) . "' ,  `email` FROM `" . get_table('edm_userdata') . "` WHERE usergroup = " . intval($user_group_id));
    }

    public function get_received_email_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $received_email;

        if (!$received_email[$id])
        {
            $received_email[$id] = $this->fetch_row('received_email', 'id = ' . $id);
        }

        return $received_email[$id];
    }

    public function remove_received_email($id)
    {
        $received_email = $this->get_received_email_by_id($id);

        if (!$received_email)
        {
            return false;
        }

        $this->delete('received_email', 'id = ' . $received_email['id']);

        $this->notification_of_receive_email_error($received_email['id'], null);
    }

    public function get_receiving_email_config_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $receiving_email_config;

        if (!$receiving_email_config[$id])
        {
            $receiving_email_config[$id] = $this->fetch_row('receiving_email_config', 'id = ' . $id);
        }

        return $receiving_email_config[$id];
    }

    public function get_receiving_email_config()
    {
        static $receiving_email_config;

        if (!$receiving_email_config)
        {
            $receiving_email_config = $this->fetch_all('receiving_email_config');
        }

        return $receiving_email_config;
    }

    public function receive_email_crond()
    {
        $now = time();

        $lock_time = AWS_APP::cache()->get('receive_email_locker');

        if ($lock_time AND $now - $lock_time <= 600)
        {
            return false;
        }

        $receiving_email_accounts = $this->get_receiving_email_config();

        if (!$receiving_email_accounts)
        {
            return false;
        }

        AWS_APP::cache()->set('receive_email_locker', $now, 600);

        foreach ($receiving_email_accounts AS $receiving_email_config)
        {
            if (!$receiving_email_config['server'] OR !$receiving_email_config['protocol'] OR !$receiving_email_config['username'] OR !$receiving_email_config['password'] OR !$receiving_email_config['uid'])
            {
                continue;
            }

            $mail_config = array(
                                'host' => $receiving_email_config['server'],
                                'user' => $receiving_email_config['username'],
                                'password' => $receiving_email_config['password']
                            );

            if ($receiving_email_config['ssl'] == 1)
            {
                $mail_config['ssl'] = 'SSL';
            }

            if ($receiving_email_config['port'])
            {
                $mail_config['port'] = $receiving_email_config['port'];
            }

            try
            {
                switch ($receiving_email_config['protocol'])
                {
                    case 'pop3':
                        $mail = new Zend_Mail_Storage_Pop3($mail_config);

                        break;

                    case 'imap':
                        $mail = new Zend_Mail_Storage_Imap($mail_config);

                        break;

                    default:
                        continue 2;
                }
            }
            catch (Exception $e)
            {
                $this->notification_of_receive_email_error($receiving_email_config['id'], $e->getMessage());

                continue;
            }

            $this->notification_of_receive_email_error($receiving_email_config['id'], null);

            $received_email['config_id'] = $receiving_email_config['id'];

            $received_email['uid'] = $receiving_email_config['uid'];

            foreach ($mail AS $num => $message)
            {
                try
                {
                    if ($receiving_email_config['protocol'] == 'imap' AND $message->hasFlag(Zend_Mail_Storage::FLAG_SEEN))
                    {
                        continue;
                    }

                    $received_email['message_id'] = substr($message->messageID, 1, -1);

                    $received_email['date'] = intval(strtotime($message->Date));

                    if ($now - $received_email['date'] > 604800 OR $this->fetch_row('received_email', 'message_id = "' . $this->quote($received_email['message_id']) . '" AND date = ' . $received_email['date']))
                    {
                        continue;
                    }

                    if ($message->isMultipart())
                    {
                        for ($i=1; $i<=$message->countParts(); $i++)
                        {
                            $part = $message->getPart($i);

                            if (substr($part->contentType, 0, 5) == 'text/')
                            {
                                $encoding = $part->contentTransferEncoding;

                                $type = $part->contentType;

                                $received_email['content'] = $part->getContent();

                                break;
                            }
                            else
                            {
                                continue;
                            }
                        }
                    }
                    else
                    {
                        $encoding = $message->contentTransferEncoding;

                        $type = $message->contentType;

                        $received_email['content'] = $message->getContent();
                    }

                    if (!$encoding OR !$type)
                    {
                        continue;
                    }

                    preg_match('/charset\s?=\s?"?([a-zA-Z0-9-]+)"?$/i', $type, $matches);

                    $charset = strtolower($matches[1]);

                    $received_email['subject'] = decode_eml($message->Subject);

                    preg_match('/<?([^<]+@.+(\.[^>]+)+)>?$/i', $message->From, $matches);

                    $received_email['from'] = strtolower($matches[1]);

                    switch ($encoding)
                    {
                        case 'base64':
                            $received_email['content'] = base64_decode($received_email['content']);

                            break;

                        case 'quoted-printable':
                            $received_email['content'] = quoted_printable_decode($received_email['content']);

                            break;
                    }

                    if ($charset AND $charset != 'utf-8')
                    {
                        $received_email['subject'] = mb_convert_encoding($received_email['subject'], 'utf-8', $charset);

                        $received_email['content'] = mb_convert_encoding($received_email['content'], 'utf-8', $charset);
                    }

                    $received_email['subject'] = strip_tags($received_email['subject']);

                    $received_email['content'] = strip_tags(preg_replace(array('/<p(\s+[^>]*)?>/i', '/<\/p>/i', '/<br\s*\/?>/i'), "\n", $received_email['content']));

                    $now++;

                    $received_email['access_key'] = md5($received_email['uid'] . $now);

                    $this->insert('received_email', $received_email);

                    if ($receiving_email_config['protocol'] == 'pop3')
                    {
                        $mail->removeMessage($num);
                    }

                    if ($receiving_email_config['protocol'] == 'imap')
                    {
                        $mail->setFlags($num, array(Zend_Mail_Storage::FLAG_SEEN));
                    }
                }
                catch (Exception $e)
                {
                    continue;
                }
            }
        }

        AWS_APP::cache()->delete('receive_email_locker');

        return true;
    }

    public function save_received_email_to_question($id, $uid)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        $received_email = $this->get_received_email_by_id($id);

        if (!$received_email OR $received_email['question_id'] OR $received_email['ticket_id'])
        {
            return false;
        }

        $this->model('publish')->publish_question($received_email['subject'], $received_email['content'], null, $uid, null, null, $received_email['access_key'], $received_email['uid'], false, array('received_email' => $received_email['id']));
    }

    public function reply_answer_by_email($question_id, $comment)
    {
        $received_email = $this->fetch_row('received_email', 'question_id = ' . intval($question_id));

        if (!$received_email OR !$received_email['from'])
        {
            return false;
        }

        $comment .= ' <br /> <a href="' . get_js_url('/question/' . $question_id) . ' target="_blank">' . AWS_APP::lang()->_t('查看问题详情') . '</a>';

        return AWS_APP::mail()->send($received_email['from'], 'RE: ' . $received_email['subject'], $comment, get_setting('site_name'));
    }

    public function notification_of_receive_email_error($id, $msg)
    {
        $admin_notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$admin_notifications)
        {
            $admin_notifications = get_setting('admin_notifications');
        }

        if ($msg === NULL)
        {
            unset($admin_notifications['receive_email_error'][$id]);
        }
        else
        {
            $admin_notifications['receive_email_error'][$id] = array(
                'id' => $id,
                'msg' => $msg
            );
        }

        AWS_APP::cache()->set('admin_notifications', $admin_notifications, 1800);

        return $this->model('setting')->set_vars(array('admin_notifications' => $admin_notifications));
    }

    public function update_receiving_email_config($id = null, $action, $receiving_email_config = array())
    {
        if ($action == 'update' AND !is_digits($id))
        {
            return false;
        }

        switch ($action)
        {
            case 'add':
                return $this->insert('receiving_email_config', $receiving_email_config);

                break;

            case 'update':
                return $this->update('receiving_email_config', $receiving_email_config, 'id = ' . $id);

                break;

            default:
                return false;

                break;
        }
    }

	public function is_unsubscription($email)
	{
		return $this->fetch_one('edm_unsubscription', 'id', "`email` = '" . $this->quote($email) . "'");
	}

	public function unsubscription_user($email)
	{
		$this->delete('edm_taskdata', "`email` = '" . $this->quote($email) . "'");
		$this->delete('edm_userdata', "`email` = '" . $this->quote($email) . "'");

		$this->insert('edm_unsubscription', array(
			'email' => $email,
			'time' => time()
		));
	}
}