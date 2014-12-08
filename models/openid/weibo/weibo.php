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
    exit();
}

class openid_weibo_weibo_class extends AWS_MODEL
{
    public function get_msg_info_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $msgs_info;

        if (!$msgs_info[$id])
        {
            $msgs_info[$id] = $this->fetch_row('weibo_msg', 'id = ' . $id);
        }

        return $msgs_info[$id];
    }

    public function del_msg_by_id($id)
    {
        $msg_info = $this->get_msg_info_by_id($id);

        if (!$msg_info)
        {
            return false;
        }

        $this->delete('weibo_msg', 'id = ' . $msg_info['id']);

        if ($msg_info['has_attach'] AND $msg_info['access_key'] AND !$msg_info['question_id'])
        {
            $attachs = $this->model('publish')->get_attach_by_access_key('weibo_msg', $msg_info['access_key']);

            if ($attachs)
            {
                foreach ($attachs AS $attach)
                {
                    $this->model('publish')->remove_attach($attach['id'], $attach['access_key']);
                }
            }
        }
    }

    public function get_services_info()
    {
        static $services_info;

        if (!$services_info)
        {
            $services_info = $this->fetch_all('users_sina', 'last_msg_id IS NOT NULL');
        }

        return $services_info;
    }

    public function save_msg_info_to_question($id)
    {
        $msg_info = $this->get_msg_info_by_id($id);

        if (!$msg_info)
        {
            return AWS_APP::lang()->_t('微博消息 ID 不存在');
        }

        $published_user = get_setting('weibo_msg_published_user');

        if (!$published_user['uid'])
        {
            return AWS_APP::lang()->_t('微博发布用户不存在');
        }

        $this->model('publish')->publish_question($msg_info['text'], null, null, $published_user['uid'], null, null, $msg_info['access_key'], $msg_info['uid'], false, 'weibo_msg', $msg_info['id']);
    }

    public function reply_answer_to_sina($question_id, $comment)
    {
        if (!get_setting('sina_akey') OR !get_setting('sina_skey'))
        {
            return false;
        }

        $msg_info = $this->fetch_row('weibo_msg', 'question_id = ' . intval($question_id));

        if (!$msg_info)
        {
            return false;
        }

        $service_info = $this->model('openid_weibo_oauth')->get_weibo_user_by_id($msg_info['weibo_uid']);

        if (!$service_info)
        {
            return false;
        }

        $comment .= ' (' . AWS_APP::lang()->_t('来自')  . ' ' . get_js_url('/question/' . $question_id) . ' )';

        $result = $this->model('openid_weibo_oauth')->create_comment($service_info['access_token'], $msg_info['id'], $comment);

        if ($result['error_code'] == 21332)
        {
            $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);
        }

        return $result;
    }

    public function get_msg_from_sina_crond()
    {
        $now = time();

        $lock_time = AWS_APP::cache()->get('weibo_msg_locker');

        if ($lock_time AND $now - $lock_time <= 600)
        {
            return false;
        }

        if (!get_setting('sina_akey') OR !get_setting('sina_skey'))
        {
            return false;
        }

        $services_info = $this->get_services_info();

        if (!$services_info)
        {
            return false;
        }

        AWS_APP::cache()->set('weibo_msg_locker', $now, 600);

        foreach ($services_info AS $service_info)
        {
            $service_user_info = $this->model('account')->get_user_info_by_uid($service_info['uid']);

            if (!$service_user_info)
            {
                continue;
            }

            if (!$service_info['access_token'] OR $service_info['expires_time'] <= time())
            {
                $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);

                continue;
            }

            $result = $this->model('openid_weibo_oauth')->get_msg_from_sina($service_info['access_token'], $service_info['last_msg_id']);

            if (!$result)
            {
                continue;
            }

            if ($result['error_code'])
            {
                if ($result['error_code'] == 21332)
                {
                    $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);
                }

                continue;
            }

            $this->notification_of_refresh_access_token($service_user_info['uid'], null);

            foreach ($result['statuses'] AS $msg)
            {
                $msg_info['created_at'] = strtotime($msg['created_at']);

                $msg_info['id'] = $msg['id'];

                if ($now - $msg_info['created_at'] > 604800 OR $this->fetch_row('weibo_msg', 'id = "' . $this->quote($msg_info['id']) . '"'))
                {
                    continue;
                }

                $msg_info['text'] = htmlspecialchars_decode(str_replace('@' . $service_info['name'], '', $msg['text']));

                $msg_info['uid'] = $service_user_info['uid'];

                $msg_info['weibo_uid'] = $service_info['id'];

                $msg_info['msg_author_uid'] = $msg['user']['id'];

                $now++;

                $msg_info['access_key'] = md5($msg_info['uid'] . $now);

                if ($msg['pic_urls'] AND get_setting('upload_enable') == 'Y')
                {
                    foreach ($msg['pic_urls'] AS $pic_url)
                    {
                        $pic_url_array = explode('/', substr($pic_url['thumbnail_pic'], 7));

                        $pic_url_array[2] = 'large';

                        $pic_url = 'http://' . implode('/', $pic_url_array);

                        $result = curl_get_contents($pic_url);

                        if (!$result)
                        {
                            continue;
                        }

                        AWS_APP::upload()->initialize(array(
                            'allowed_types' => get_setting('allowed_upload_types'),
                            'upload_path' => get_setting('upload_dir') . '/questions/' . gmdate('Ymd'),
                            'is_image' => TRUE,
                            'max_size' => get_setting('upload_size_limit')
                        ));

                        AWS_APP::upload()->do_upload($pic_url_array[3], $result);

                        if (AWS_APP::upload()->get_error())
                        {
                            continue;
                        }

                        $upload_data = AWS_APP::upload()->data();

                        if (!$upload_data)
                        {
                            continue;
                        }

                        foreach (AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
                        {
                            $thumb_file[$key] = $upload_data['file_path'] . $val['w'] . 'x' . $val['h'] . '_' . basename($upload_data['full_path']);

                            AWS_APP::image()->initialize(array(
                                'quality' => 90,
                                'source_image' => $upload_data['full_path'],
                                'new_image' => $thumb_file[$key],
                                'width' => $val['w'],
                                'height' => $val['h']
                            ))->resize();
                        }

                        $this->model('publish')->add_attach('weibo_msg', $upload_data['orig_name'], $msg_info['access_key'], $now, basename($upload_data['full_path']), true);

                        $msg_info['has_attach'] = 1;
                    }

                    $this->model('publish')->update_attach('weibo_msg', $msg_info['id'], $msg_info['access_key']);
                }
                else
                {
                    $msg_info['has_attach'] = 0;
                }

                $this->insert('weibo_msg', $msg_info);

                $this->update_service_account($msg_info['uid'], null, $msg_info['id']);
            }
        }

        AWS_APP::cache()->delete('weibo_msg_locker');

        return true;
    }

    public function notification_of_refresh_access_token($uid, $user_name)
    {
        if (!is_digits($uid))
        {
            return false;
        }

        $admin_notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$admin_notifications)
        {
            $admin_notifications = get_setting('admin_notifications');
        }

        if ($user_name === NULL)
        {
            unset($admin_notifications['sina_users'][$uid]);
        }
        else
        {
            $admin_notifications['sina_users'][$uid] = array(
                                                            'uid' => $uid,
                                                            'user_name' => $user_name
                                                        );
        }

        AWS_APP::cache()->set('admin_notifications', $admin_notifications, 1800);

        return $this->model('setting')->set_vars(array('admin_notifications' => $admin_notifications));
    }

    public function update_service_account($uid, $action = null, $last_msg_id = 0)
    {
        switch ($action)
        {
            case 'add':
                $last_msg_id = 0;

                break;

            case 'del':
                $last_msg_id = 'NULL';

                break;

            default:
                if (!is_digits($last_msg_id))
                {
                    return false;
                }

                break;
        }

        $this->query('UPDATE ' . get_table('users_sina') . ' SET last_msg_id = ' . $last_msg_id . ' WHERE uid = ' . intval($uid));

        if ($action == 'del')
        {
            $this->notification_of_refresh_access_token($uid, null);
        }
    }

    public function update_attach($weibo_msg_id, $item_type, $item_id, $attach_access_key)
    {
        if (!is_digits($weibo_msg_id) OR !is_digits($item_id) OR !$attach_access_key)
        {
            return false;
        }

        $update_result = $this->update('attach', array(
            'item_type' => $item_type,
            'item_id' => $item_id,
        ), 'item_type = "weibo_msg" AND item_id = ' . $weibo_msg_id . ' AND access_key = "' . $this->quote($attach_access_key) . '"');

        $this->shutdown_update($item_type, array(
            'has_attach' => 1
        ), $item_type . '_id = ' . $item_id);

        return $update_result;
    }
}
