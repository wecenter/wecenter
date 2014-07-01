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
        static $msgs_info;

        if (!$msgs_info[$id])
        {
            $msgs_info[$id] = $this->fetch_row('weibo_msg', 'id = ' . $this->quote($id));
        }

        return $msgs_info[$id];
    }

    public function get_msg_info_by_question_id($question_id)
    {
        return $this->fetch_row('weibo_msg', 'question_id = ' . intval($question_id));
    }

    public function del_msg_by_id($id)
    {
        if (is_array($id))
        {
            $id = implode(',', $id);

            return $this->delete('weibo_msg', 'id IN (' . $this->quote($id)) . ')';
        }

        return $this->delete('weibo_msg', 'id = ' . $this->quote($id));
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

    public function save_msg_info($msg_info)
    {
        return $this->insert('weixin_msg', $msg_info);
    }

    public function save_msg_info_to_question($id)
    {
        $msg_info = $this->get_msg_info_by_id($id);

        if (empty($msg_info))
        {
            return AWS_APP::lang()->_t('微博消息 ID 不存在');
        }

        $published_uid = get_setting('weibo_msg_published_user')['uid'];

        if (empty($published_uid))
        {
            return AWS_APP::lang()->_t('微博发布用户不存在');
        }

        $question_id = $this->model('question')->save_question($msg_info['text'], null, $published_uid, 0, null, $id);

        $this->update('weibo_msg', array(
            'question_id' => $question_id
        ), 'id = ' . $this->quote($id));

        $this->shutdown_update('attach', array(
            'item_type' => 'question',
            'item_id' => $question_id,
        ), 'item_type = "weibo_msg" AND item_id = ' . $this->quote($id));

        $this->shutdown_update('question', array(
            'has_attach' => 1
        ), 'question_id = ' . $question_id);
    }

    public function reply_answer_to_sina($question_id, $comment)
    {
        if (empty(get_setting('sina_akey')) OR empty(get_setting('sina_skey')))
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

        $this->model('openid_weibo')->create_comment($service_info['access_token'], $msg_info['id'], $comment);
    }

    public function get_msg_from_sina_crond()
    {
        if (empty(get_setting('sina_akey')) OR empty(get_setting('sina_skey')))
        {
            return false;
        }

        $services_info = $this->get_services_info();

        if (empty($services_info))
        {
            return false;
        }

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

            if ($msgs['error_code'] == 21332)
            {
                $this->notification_of_refresh_access_token($service_user_info['uid'], $service_user_info['user_name']);

                continue;
            }

            $last_msg_id = $msgs[0]['id'];

            foreach ($msgs AS $msg)
            {
                $msg_info['created_at'] = strtotime($msg['created_at']);

                $msg_info['id'] = $msg['id'];

                $msg_info['text'] = str_replace('@' . $service_info['name'], '@' . $service_user_info['user_name'], $msg['text']);

                $msg_info['msg_author_uid'] = $msg['user']['id'];

                if (!empty($msg['pic_urls']) AND get_setting('upload_enable') == 'Y')
                {
                    foreach ($msg['pic_urls'] AS $pic_url)
                    {
                        $pic_url_array = explode('/', substr($pic_url['thumbnail_pic'], 7));

                        $pic_url_array[2] = 'large';

                        $pic_url = 'http://' . implode('/', $pic_url_array);

                        $result = curl_get_contents($pic_url);

                        $upload_dir = get_setting('upload_dir') . '/' . 'weibo' . '/' . gmdate('Ymd') . '/';

                        $ori_image = $upload_dir . $pic_url_array[3];

                        $handle = fopen($file_location, 'w');

                        fwrite($handle, $result);

                        fclose($handle);

                        foreach (AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
                        {
                            $thumb_file[$key] = $upload_dir . $val['w'] . 'x' . $val['h'] . '_' . $pic_url_array[3];

                            AWS_APP::image()->initialize(array(
                                'quality' => 90,
                                'source_image' => $ori_image,
                                'new_image' => $thumb_file[$key],
                                'width' => $val['w'],
                                'height' => $val['h']
                            ))->resize();
                        }

                        $now = time();

                        $attach_access_key = md5($service_info['uid'], $now);

                        $this->model('publish')->add_attach('weibo_msg', $pic_url_array[3], $attach_access_key, $now, $ori_image, true);
                    }

                    $this->model('publish')->update_attach('weibo_msg', $msg_info['id'], $attach_access_key);
                }

                $msg_info['uid'] = $service_info['uid'];

                $msg_info['weibo_uid'] = $service_info['id'];

                $this->save_msg_info($msg_info);
            }
        }

        return true;
    }

    public function notification_of_refresh_access_token($uid, $user_name)
    {
        $admin_notifications = get_setting('admin_notifications');

        $admin_notifications['sina_users'][$uid] = array(
                                                                'uid' => $uid,
                                                                'user_name' => $user_name
                                                            );

        $this->model('setting')->set_vars(array('admin_notifications' => $notifications));
    }

    public function update_service_account($id, $action)
    {
        switch ($action)
        {
            case 'add':
                $last_msg_id = 0;

                break;

            case 'del':
                $last_msg_id = null;

                break;
        }

        $this->update('users_sina', array('last_msg_id' => $last_msg_id), 'id = ' . intval($id));
    }
}
