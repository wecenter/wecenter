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

    public function get_msg_from_sina($access_token, $since_id = 0, $max_id = 0)
    {
        $client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $access_token);

        do
        {
            $result = json_decode($client->mentions(1, 200, $since_id, $max_id), true);

            if ($result['error'])
            {
                return $result;
            }

            $new_msgs = $result['statuses'];

            $new_msgs_total = count($new_msgs);

            if ($new_msgs_total == 0)
            {
                return false;
            }

            if (empty($msgs))
            {
                $msgs = $new_msgs;
            }
            else
            {
                $msgs = array_merge($msgs, $new_msgs);
            }

            $max_id = $msgs[200]['weibo_id'] - 1;
        }
        while ($new_msgs_total < 200);

        return $msgs;
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

        $service_uids = get_setting('service_weibo_uids');

        if (empty($service_uids))
        {
            return false;
        }

        foreach ($service_uids AS $service_info)
        {
            $uid = $service_info['uid'];

            $user_info = $this->model('openid_weibo')->get_users_sina_by_id($uid);

            $last_msg_id = get_setting('last_weibo_msg_ids_from_sina')[$uid] ?: 0;

            $access_token = $user_info['access_token'];

            $msgs = $this->get_msg_from_sina($access_token, $last_msg_id);

            if (empty($msgs))
            {
                continue;
            }

            if ($msgs['error_code'] == 21332)
            {
                // 提示更新 access_token，添加到前台（和后台）通知里

                continue;
            }

            foreach ($msgs AS $msg)
            {
                $msg_info['created_at'] = strtotime($msg['created_at']);

                $msg_info['weibo_id'] = $msg['id'];

                $msg_info['text'] = $msg['text'];

                $msg_info['weibo_uid'] = $msg['user']['id'];

                if (!empty($msg['pic_urls']))
                {
                    foreach ($msg['pic_urls'] AS $pic_url)
                    {
                        $pic_urls[] = $pic_url['thumbnail_pic'];
                    }

                    $msg_info['pic_urls'] = implode("\n", $pic_urls);
                }

                $msg_info['uid'] = $user_info['uid'];

                $this->save_msg_info($msg_info);
            }

            $last_msg_id = $msgs[0]['weibo_id'];

        }

        return true;
    }
}
