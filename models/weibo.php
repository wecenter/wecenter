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

class weibo_class extends AWS_MODEL
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

    public function get_msg_info_by_question_id($question_id)
    {
        return $this->fetch_row('weibo_msg', 'question_id = ' . intval($question_id));
    }

    public function del_msg_by_id($id)
    {
        $msg_info = $this->get_msg_info_by_id($id);

        if (empty($msg_info))
        {
            return false;
        }

        $this->delete('weibo_msg', 'id = ' . $this->quote($id));

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

        if (empty($msg_info))
        {
            return AWS_APP::lang()->_t('微博消息 ID 不存在');
        }

        $published_user = get_setting('weibo_msg_published_user');

        $published_uid = $published_user['uid'];

        if (empty($published_uid))
        {
            return AWS_APP::lang()->_t('微博发布用户不存在');
        }

        $this->model('publish')->publish_question($msg_info['text'], null, null, $published_uid, null, null, $msg_info['access_key'], $msg_info['uid'], false, $msg_info['id']);
    }

    public function reply_answer_to_sina($question_id, $comment)
    {
        if (!get_setting('sina_akey') OR !get_setting('sina_skey'))
        {
            return false;
        }

        $msg_info = $this->get_msg_info_by_question_id($question_id);

        if (empty($msg_info))
        {
            return false;
        }

        $service_info = $this->model('openid_weibo')->get_users_sina_by_id($msg_info['weibo_uid']);

        if (empty($service_info))
        {
            return false;
        }

        return $this->model('openid_weibo')->create_comment($service_info['access_token'], $msg_info['id'], $comment);
    }

    public function get_msg_from_sina_crond()
    {
        $now = time();

        $locker = TEMP_PATH . 'weibo_msg.lock';

        if (is_file($locker))
        {
            $handle = @fopen($locker, 'r');

            $time = @fread($handle, @filesize($locker));

            @fclose($handle);

            if (empty($time) OR $now - $time > 600)
            {
                @unlink($locker);
            }
            else
            {
                return false;
            }
        }

        if (!get_setting('sina_akey') OR !get_setting('sina_skey'))
        {
            return false;
        }

        @set_time_limit(0);

        $services_info = $this->get_services_info();

        if (empty($services_info))
        {
            return false;
        }

        $handle = @fopen($locker, 'w');

        @fwrite($handle, $now);

        @fclose($handle);

        foreach ($services_info AS $service_info)
        {
            $service_user_info = $this->model('account')->get_user_info_by_uid($service_info['uid']);

            if (empty($service_user_info))
            {
                continue;
            }

            if (empty($service_info['access_token']) OR $service_info['expires_time'] <= time())
            {
                $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);

                continue;
            }

            $msgs = $this->model('openid_weibo')->get_msg_from_sina($service_info['access_token'], $service_info['last_msg_id']);

            if (empty($msgs))
            {
                continue;
            }

            if ($msgs['error'])
            {
                if ($msgs['error_code'] == 21332)
                {
                    $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);
                }

                continue;
            }

            foreach ($msgs AS $msg)
            {
                $now++;

                $msg_info['created_at'] = strtotime($msg['created_at']);

                $msg_info['id'] = $msg['id'];

                if ($now - $msg_info['created_at'] > 604800 OR $this->fetch_row('weibo_msg', $this->quote($msg_info['id'])))
                {
                    continue;
                }

                $msg_info['text'] = str_replace('@' . $service_info['name'], '', $msg['text']);

                $msg_info['uid'] = $service_user_info['uid'];

                $msg_info['weibo_uid'] = $service_info['id'];

                $msg_info['msg_author_uid'] = $msg['user']['id'];

                $msg_info['access_key'] = md5($msg_info['uid'] . $now);

                if ($msg['pic_urls'] AND get_setting('upload_enable') == 'Y')
                {
                    foreach ($msg['pic_urls'] AS $pic_url)
                    {
                        $pic_url_array = explode('/', substr($pic_url['thumbnail_pic'], 7));

                        $pic_url_array[2] = 'large';

                        $pic_url = 'http://' . implode('/', $pic_url_array);

                        $result = curl_get_contents($pic_url);

                        if (empty($result))
                        {
                            continue;
                        }

                        AWS_APP::upload()->initialize(array(
                            'allowed_types' => get_setting('allowed_upload_types'),
                            'upload_path' => get_setting('upload_dir') . '/questions/' . gmdate('Ymd'),
                            'is_image' => TRUE,
                            'max_size' => get_setting('upload_size_limit')
                        ));

                        AWS_APP::upload()->do_upload($pic_url_array[3], $file);

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

        @unlink($locker);

        return true;
    }

    public function notification_of_refresh_access_token($uid, $user_name)
    {
        $admin_notifications = get_setting('admin_notifications');

        $admin_notifications['sina_users'][$uid] = array(
                                                        'uid' => $uid,
                                                        'user_name' => $user_name
                                                    );

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

        return $this->query('UPDATE ' . get_table('users_sina') . ' SET last_msg_id = ' . $last_msg_id . ' WHERE uid = ' . intval($uid));
    }

    public function update_attach($weibo_msg_id, $question_id, $attach_access_key)
    {
        if (empty($weibo_msg_id) OR !is_digits($weibo_msg_id) OR empty($question_id) OR empty($attach_access_key))
        {
            return false;
        }

        $update_result = $this->update('attach', array(
            'item_type' => 'question',
            'item_id' => intval($question_id),
        ), 'item_type = "weibo_msg" AND item_id = ' . $weibo_msg_id . ' AND access_key = "' . $this->quote($attach_access_key) . '"');

        $this->shutdown_update('question', array(
            'has_attach' => 1
        ), 'question_id = ' . intval($question_id));

        return $update_result;
    }
}
