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
    public function get_msg_info_by_id($weibo_id)
    {
        static $msgs_info;

        if (!$msgs_info[$weibo_id])
        {
            $msgs_info[$weibo_id] = $this->fetch_row('weibo_msg', 'weibo_id = ' . $this->quote($weibo_id));
        }

        return $msgs_info[$weibo_id];
    }

    public function del_msg_by_id($weibo_id)
    {
        return $this->delete('weibo_msg', 'weibo_id = ' . $this->quote($weibo_id));
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

    public function save_msg_info_to_question()
    {

    }

    public function get_msg_from_sina_crond()
    {
        if (get_setting('sina_weibo_enabled') == 'N' OR empty(get_setting('sina_akey')) OR empty(get_setting('sina_skey')))
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
            if (empty($service_info['access_token']) OR $service_info['expires_time'] <= time())
            {
                // 提示更新 access_token，添加到前台（和后台）通知里
                $this->notification_of_refresh_access_token($service_info['uid']);

                continue;
            }

            $msgs = $this->model('openid_weibo')->get_msg_from_sina($service_info['access_token'], $service_info['last_msg_id']);

            if (empty($msgs))
            {
                continue;
            }

            if ($msgs['error_code'] == 21332)
            {
                // 提示更新 access_token，添加到前台（和后台）通知里
                $this->notification_of_refresh_access_token($service_info['uid']);

                continue;
            }

            $last_msg_id = $msgs[0]['id'];

            foreach ($msgs AS $msg)
            {
                $msg_info['created_at'] = strtotime($msg['created_at']);

                $msg_info['weibo_id'] = $msg['id'];

                $msg_info['text'] = $msg['text'];

                $msg_info['msg_author_uid'] = $msg['user']['id'];

                if (!empty($msg['pic_urls']))
                {
                    foreach ($msg['pic_urls'] AS $pic_url)
                    {
                        $pic_url = str_replace('/thumbnail/', '/large/', $pic_url['thumbnail_pic']);

                        // 上传图片

                    }
                }

                $msg_info['uid'] = $service_info['uid'];

                $msg_info['weibo_uid'] = $service_info['id'];

                $this->save_msg_info($msg_info);
            }
        }

        return true;
    }

    public function notification_of_refresh_access_token($weibo_uid)
    {

    }
}
