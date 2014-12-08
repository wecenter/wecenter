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
+--------------------------------------------------------------------------
*/

if (! defined('IN_ANWSION'))
{
    die;
}

class weixin_class extends AWS_MODEL
{
    private $xml_template_text = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>';

    private $xml_template_image = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles></xml>';

    private $xml_template_article = '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>';

    private $bind_message;

    private $user_id;

    private $mpnews;

    private $media_id;

    private $msg_id;

    private $to_save_main_msg;

    private $to_save_articles;

    private $to_save_questions;

    private $post_data;

    public $account_info;

    public function replace_post($subject)
    {
        $subject = $this->array_urlencode($subject);

        if (!$subject)
        {
            return false;
        }

        return urldecode(json_encode($subject));
    }

    public function array_urlencode($array)
    {
        if (!$array OR !is_array($array))
        {
            return false;
        }

        $new_array = array();

        foreach ($array as $key => $value)
        {
            $new_array[urlencode($key)] = is_array($value) ? $this->array_urlencode($value) : urlencode($value);
        }

        return $new_array;
    }

    public function get_master_account_info()
    {
        return array(
            'id' => 0,
            'weixin_mp_token' => get_setting('weixin_mp_token'),
            'weixin_account_role' => get_setting('weixin_account_role'),
            'weixin_app_id' => get_setting('weixin_app_id'),
            'weixin_app_secret' => get_setting('weixin_app_secret'),
            'weixin_mp_menu' => get_setting('weixin_mp_menu'),
            'weixin_subscribe_message_key' => get_setting('weixin_subscribe_message_key'),
            'weixin_no_result_message_key' => get_setting('weixin_no_result_message_key'),
            'weixin_encoding_aes_key' => get_setting('weixin_encoding_aes_key')
        );
    }

    public function get_account_info_by_id($account_id)
    {
        $account_id = intval($account_id);

        if ($account_id == 0)
        {
            return $this->get_master_account_info();
        }

        static $account_info;

        if (!$account_info[$account_id])
        {
            $account_info[$account_id] = $this->fetch_row('weixin_accounts', 'id = ' . $account_id);

            if (!$account_info[$account_id])
            {
                return false;
            }

            $account_info[$account_id]['weixin_mp_menu'] = json_decode($account_info[$account_id]['weixin_mp_menu'], true);
        }

        return $account_info[$account_id];
    }

    public function get_accounts_info()
    {
        static $accounts_info;

        if (!$accounts_info)
        {
            $accounts_info[0] = $this->get_master_account_info();

            $accounts_list = $this->model('setting')->fetch_all('weixin_accounts');

            if ($accounts_list)
            {
                foreach ($accounts_list AS $account_info)
                {
                    $accounts_info[$account_info['id']] = $account_info;

                    if ($account_info['weixin_mp_menu'])
                    {
                        $accounts_info[$account_info['id']]['weixin_mp_menu'] = unserialize($account_info['weixin_mp_menu']);
                    }
                }
            }
        }

        return $accounts_info;
    }

    public function update_setting_or_account($account_id, $account_info)
    {
        $account_id = intval($account_id);

        if (!$account_info)
        {
            return false;
        }

        if ($account_id == 0)
        {
            return $this->model('setting')->set_vars($account_info);
        }
        else
        {
            if ($account_info['weixin_mp_menu'])
            {
                $account_info['weixin_mp_menu'] = serialize($account_info['weixin_mp_menu']);
            }

            return $this->update('weixin_accounts', $account_info, 'id = ' . $account_id);
        }
    }

    public function fetch_message()
    {
        if ($this->post_data = file_get_contents('php://input'))
        {
            $post_object = (array)simplexml_load_string($this->post_data, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($_GET['encrypt_type'] == 'aes')
            {
                $post_object = $this->decrypt_msg($post_object['Encrypt']);
            }

            $input_message = array(
                'fromUsername' => $post_object['FromUserName'],
                'toUsername' => $post_object['ToUserName'],
                'content' => trim($post_object['Content']),
                'time' => time(),
                'msgType' => $post_object['MsgType'],
                'event' => $post_object['Event'],
                'eventKey' => $post_object['EventKey'],
                'mediaID' => $post_object['MediaId'],
                'format' => $post_object['Format'],
                'recognition' => $post_object['Recognition'],
                'msgID' => $post_object['MsgID'],
                'latitude' => $post_object['Latitude'],
                'longitude' => $post_object['Longitude'],
                'precision' => $post_object['Precision'],
                'location_X' => $post_object['Location_X'],
                'location_Y' => $post_object['Location_y'],
                'label' => $post_object['Label'],
                'ticket' => $post_object['Ticket'],
                'createTime' => $post_object['CreateTime'],
                'status' => $post_object['Status'],
                'filterCount' => $post_object['FilterCount'],
                'picUrl' => $post_object['PicUrl'],
                'encryption' => ($_GET['encrypt_type'] == 'aes') ? true : false
            );

            $weixin_info = $this->model('openid_weixin_weixin')->get_user_info_by_openid($input_message['fromUsername']);

            if ($weixin_info)
            {
                $this->user_id = $weixin_info['uid'];
            }

            if (get_setting('weixin_account_role') == 'service')
            {
                $this->bind_message = '你的微信帐号没有绑定 ' . get_setting('site_name') . ' 的帐号, 请<a href="' . $this->model('openid_weixin_weixin')->get_oauth_url(get_js_url('/m/weixin/authorization/')) . '">点此绑定</a>';
            }

            return $input_message;
        }
    }

    public function response_message($input_message)
    {
        switch ($input_message['msgType'])
        {
            case 'event':
                if (substr($input_message['eventKey'], 0, 8) == 'COMMAND_')
                {
                    if ($input_message['eventKey'] == 'COMMAND_MORE')
                    {
                        $input_message['content'] = 'yes';
                        $input_message['msgType'] = 'text';

                        $response = $this->response_message($input_message);
                    }
                    else
                    {
                        if (strstr($input_message['eventKey'], '__'))
                        {
                            $event_key = explode('__', substr($input_message['eventKey'], 8));

                            $content = $event_key[0];
                            $param = $event_key[1];
                        }
                        else
                        {
                            $content = substr($input_message['eventKey'], 8);
                        }

                        $response = $this->message_parser(array(
                            'content' => $content,
                            'fromUsername' => $input_message['fromUsername'],
                            'param' => $param
                        ));
                    }

                    $response_message = $response['message'];
                    $action = $response['action'];
                }
                else if (substr($input_message['eventKey'], 0, 11) == 'REPLY_RULE_')
                {
                    if ($reply_rule = $this->get_reply_rule_by_id(substr($input_message['eventKey'], 11)))
                    {
                        $response_message = $this->create_response_by_reply_rule_keyword($reply_rule['keyword']);
                    }
                    else
                    {
                        $response_message = '菜单指令错误';
                    }
                }
                else
                {
                    switch (strtolower($input_message['event']))
                    {
                        case 'subscribe':
                            if (substr($input_message['eventKey'], 0, 8) == 'qrscene_')
                            {
                                $this->query('UPDATE ' . get_table('weixin_qr_code') . ' SET subscribe_num = subscribe_num + 1 WHERE scene_id = ' . intval(substr($input_message['eventKey'], 8)));
                            }

                            if ($this->account_info['weixin_subscribe_message_key'])
                            {
                                $response_message = $this->create_response_by_reply_rule_keyword($this->account_info['weixin_subscribe_message_key']);
                            }

                            break;

                        case 'location':
                            if ($this->user_id)
                            {
                                $this->update('users_weixin', array(
                                    'latitude' => $input_message['latitude'],
                                    'longitude' => $input_message['longitude'],
                                    'location_update' => time()
                                ), 'uid = ' . $this->user_id);
                            }

                            break;

                        case 'masssendjobfinish':
                            $msg_id = $input_message['msgID'];

                            $msg_details = array(
                                                'create_time' => intval($input_message['createTime']),
                                                'filter_count' => intval($input_message['filterCount'])
                                            );

                            if ($input_message['status'] == 'send success' OR $input_message['status'] == 'sendsuccess')
                            {
                                $msg_details['status'] = 'success';
                            }
                            else if ($input_message['status'] == 'send fail' OR $input_message['status'] == 'sendfail')
                            {
                                $msg_details['status'] = 'fail';
                            }
                            else if (substr($input_message['status'], 0, 3) == 'err')
                            {
                                $msg_details['status'] = 'wrong';
                                $msg_details['error_num'] = intval(substr($input_message['status'], 4, 9));
                            }

                            if (is_digits($msg_id))
                            {
                                $this->update('weixin_msg', $msg_details, 'msg_id = ' . $msg_id);
                            }

                            break;
                    }
                }

                break;

            case 'location':
                if ($near_by_questions = $this->model('people')->get_near_by_users($input_message['location_X'], $input_message['location_y'], $this->user_id, 5))
                {
                    foreach ($near_by_questions AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                        }
                        else
                        {
                            $image_file = get_avatar_url($val['published_uid'], 'max');
                        }

                        $response_message[] = array(
                            'title' => $val['question_content'],
                            'link' => get_js_url('/m/question/' . $val['question_id']),
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '你的附近暂时没有问题';
                }

                break;

            case 'voice':
                if ($input_message['recognition'])
                {
                    $input_message['content'] = $input_message['recognition'];
                    $input_message['msgType'] = 'text';

                    $response_message = $this->response_message($input_message);
                }
                else
                {
                    $response_message = '无法识别语音或相关功能未启用';
                }

                break;

            case 'image':
                if (get_setting('weixin_account_role') != 'subscription' AND get_setting('weixin_account_role') != 'service')
                {
                    break;
                }

                if ($input_message['mediaID'] AND $input_message['picUrl'])
                {
                    AWS_APP::cache()->set('weixin_pic_url_' . md5($input_message['mediaID']), $input_message['picUrl'], 259200);

                    $response_message = '您想提交图片到社区么？<a href="' . $this->model('openid_weixin_weixin')->redirect_url('/m/publish/weixin_media_id-' . base64_encode($input_message['mediaID'])) . '">点击进入提交页面</a>';
                }
                else
                {
                    $response_message = '无法识别图片';
                }

                break;

            default:
                if ($response_message = $this->create_response_by_reply_rule_keyword($input_message['content']))
                {
                    // response by reply rule keyword...
                }
                else if ($response = $this->model('openid_weixin_third')->send_message_to_third_party($this->account_info['id'], $this->post_data))
                {
                    exit($response);
                }
                else if ($response = $this->message_parser($input_message))
                {
                    // Success...
                    $response_message = $response['message'];
                    $action = $response['action'];
                }
                else if ($this->is_language($input_message['content'], 'ok'))
                {
                    $response = $this->process_last_action($input_message['fromUsername']);

                    $response_message = $response['message'];
                    $action = $response['action'];
                }
                else if ($this->is_language($input_message['content'], 'cancel'))
                {
                    $this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");

                    $response_message = '好的, 还有什么可以帮您的吗?';
                }
                else
                {
                    if (!$response_message = $this->create_response_by_reply_rule_keyword($this->account_info['weixin_no_result_message_key']))
                    {
                        $response_message = '您的问题: ' . $input_message['content'] . ', 目前没有人提到过, <a href="' . $this->model('openid_weixin_weixin')->redirect_url('/m/publish/') . '">点此提问</a>';
                    }
                }

                break;
        }

        $response = (is_array($response_message)) ? $this->create_image_response($input_message, $response_message, $action) : $this->create_txt_response($input_message, $response_message, $action);

        if ($input_message['encryption'])
        {
            $response = $this->encrypt_msg($response);
        }

        exit($response);
    }

    public function create_txt_response($input_message, $response_message, $action = null)
    {
        if (!$response_message)
        {
            return false;
        }

        if ($action)
        {
            $this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");

            $this->insert('weixin_message', array(
                'weixin_id' => $input_message['fromUsername'],
                'content' => $input_message['content'],
                'action' => $action,
                'time' => time()
            ));
        }

        return sprintf($this->xml_template_text, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'text', $response_message);
    }

    public function create_image_response($input_message, $article_data = array(), $action = null)
    {
        if ($action)
        {
            $this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");
            $this->insert('weixin_message', array(
                'weixin_id' => $input_message['fromUsername'],
                'content' => $input_message['content'],
                'action' => $action,
                'time' => time()
            ));
        }

        $rule_image_size = '';

        foreach ($article_data AS $key => $val)
        {
            if (substr($val['image_file'], 0, 4) != 'http')
            {
                $article_data[$key]['image_file'] = $this->get_weixin_rule_image($val['image_file'], $rule_image_size);
            }

            $rule_image_size = 'square';
        }

        if (!is_array($article_data))
        {
            return false;
        }

        foreach ($article_data AS $key => $val)
        {
            $article_xml .= sprintf($this->xml_template_article, $val['title'], $val['description'], $val['image_file'], $val['link']);
        }

        return sprintf($this->xml_template_image, $input_message['fromUsername'], $input_message['toUsername'], $input_message['time'], 'news', sizeof($article_data), $article_xml);
    }

    public function message_parser($input_message, $param = null)
    {
        $message_code = strtoupper(trim($input_message['content']));

        if (cjk_strlen($message_code) < 2)
        {
            return false;
        }

        if (!$param)
        {
            $param = 1;
        }

        switch ($message_code)
        {
            default:
                if (cjk_strlen($input_message['content']) > 1 AND substr($input_message['content'], 0, 1) == '@')
                {
                    if ($user_info = $this->model('account')->get_user_info_by_username(substr($input_message['content'], 1), true))
                    {
                        $response_message[] = array(
                            'title' => $user_info['signature'],
                            'link' => get_js_url('/m/people/' . $user_info['url_token']),
                            'image_file' => get_avatar_url($user_info['uid'], '')
                        );

                        if ($user_actions = $this->model('actions')->get_user_actions($user_info['uid'], calc_page_limit($param, 4), 101))
                        {
                            foreach ($user_actions AS $key => $val)
                            {
                                $response_message[] = array(
                                    'title' => $val['question_info']['question_content'],
                                    'link' => get_js_url('/m/question/' . $val['question_info']['question_id']),
                                    'image_file' => get_avatar_url($val['question_info']['published_uid'], 'max')
                                );
                            }
                        }
                    }
                }
                else if ($topic_info = $this->model('topic')->get_topic_by_title($input_message['content']))
                {
                    $response_message[] = array(
                        'title' => $topic_info['topic_title'],
                        'link' => get_js_url('/m/topic/' . $topic_info['url_token']),
                        'image_file' => get_topic_pic_url('', $topic_info['topic_pic'])
                    );

                    if ($topic_posts = $this->model('posts')->get_posts_list(null, $param, 4, 'new', array($topic_info['topic_id'])))
                    {
                        foreach ($topic_posts AS $key => $val)
                        {
                            if ($val['uid'])
                            {
                                $image_file = get_avatar_url($val['uid'], 'max');

                                $title = $val['title'];
                                $link = get_js_url('/m/article/' . $val['id']);
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['published_uid'], 'max');

                                $title = $val['question_content'];
                                $link = get_js_url('/m/question/' . $val['question_id']);
                            }

                            $response_message[] = array(
                                'title' => $title,
                                'link' => $link,
                                'image_file' => $image_file
                            );
                        }
                    }
                }
                else if ($search_result = $this->model('search')->search_questions($input_message['content'], null, $param, 5))
                {
                    foreach ($search_result AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                        }
                        else
                        {
                            $image_file = get_avatar_url($val['published_uid'], 'max');
                        }

                        $response_message[] = array(
                            'title' => $val['question_content'],
                            'link' => get_js_url('/m/question/' . $val['question_id']),
                            'image_file' => $image_file
                        );
                    }
                }

                break;

            case 'NEW_ARTICLE':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($topic_ids)
                {
                    $article_list = $this->model('article')->get_articles_list_by_topic_ids($param, 5, 'add_time DESC', $topic_ids);
                }
                else
                {
                    $article_list = $this->model('article')->get_articles_list(null, $param, 5, 'add_time DESC');
                }

                foreach ($article_list AS $key => $val)
                {
                    if (!$response_message)
                    {
                        if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                        {
                            $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                        }
                    }
                    else
                    {
                        $image_file = get_avatar_url($val['uid'], 'max');
                    }

                    $response_message[] = array(
                        'title' => $val['title'],
                        'link' => get_js_url('/m/article/' . $val['id']),
                        'image_file' => $image_file
                    );
                }

                break;

            case 'HOT_QUESTION':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'CATEGORY':
                            $category_id = intval($child_param[1]);
                        break;

                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($question_list = $this->model('posts')->get_hot_posts('question', $category_id, $topic_ids, 7, $param, 5))
                {
                    foreach ($question_list AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                            {
                                $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                            }
                        }
                        else
                        {
                            $image_file = get_avatar_url($val['published_uid'], 'max');
                        }

                        $response_message[] = array(
                            'title' => $val['question_content'],
                            'link' => get_js_url('/m/question/' . $val['question_id']),
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '暂无问题';
                }

                break;

            case 'NEW_QUESTION':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'CATEGORY':
                            $category_id = intval($child_param[1]);
                        break;

                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($question_list = $this->model('posts')->get_posts_list('question', $param, 5, 'new', $topic_ids, $category_id))
                {
                    foreach ($question_list AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                            {
                                $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                            }
                        }
                        else
                        {
                            if ($val['uid'])
                            {
                                $image_file = get_avatar_url($val['uid'], 'max');
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['published_uid'], 'max');
                            }
                        }


                        if ($val['uid'])
                        {
                            $title = $val['title'];
                            $link = get_js_url('/m/article/' . $val['id']);
                        }
                        else
                        {
                            $title = $val['question_content'];
                            $link = get_js_url('/m/question/' . $val['question_id']);
                        }

                        $response_message[] = array(
                            'title' => $title,
                            'link' => $link,
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '暂无问题';
                }


                break;

            case 'NEW_POSTS':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'CATEGORY':
                            $category_id = intval($child_param[1]);
                        break;

                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($question_list = $this->model('posts')->get_posts_list(null, $param, 5, 'new', $topic_ids, $category_id))
                {
                    foreach ($question_list AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                            {
                                $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                            }
                        }
                        else
                        {
                            if ($val['uid'])
                            {
                                $image_file = get_avatar_url($val['uid'], 'max');
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['published_uid'], 'max');
                            }
                        }


                        if ($val['uid'])
                        {
                            $title = $val['title'];
                            $link = get_js_url('/m/article/' . $val['id']);
                        }
                        else
                        {
                            $title = $val['question_content'];
                            $link = get_js_url('/m/question/' . $val['question_id']);
                        }

                        $response_message[] = array(
                            'title' => $title,
                            'link' => $link,
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '暂无内容';
                }

                break;

            case 'NO_ANSWER_QUESTION':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'CATEGORY':
                            $category_id = intval($child_param[1]);
                        break;

                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($question_list = $this->model('posts')->get_posts_list('question', $param, 5, 'unresponsive', $topic_ids, $category_id))
                {
                    foreach ($question_list AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                            {
                                $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                            }
                        }
                        else
                        {
                            if ($val['uid'])
                            {
                                $image_file = get_avatar_url($val['uid'], 'max');
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['published_uid'], 'max');
                            }
                        }


                        if ($val['uid'])
                        {
                            $title = $val['title'];
                            $link = get_js_url('/m/article/' . $val['id']);
                        }
                        else
                        {
                            $title = $val['question_content'];
                            $link = get_js_url('/m/question/' . $val['question_id']);
                        }

                        $response_message[] = array(
                            'title' => $title,
                            'link' => $link,
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '暂无问题';
                }

                break;

            case 'RECOMMEND_QUESTION':
                if ($input_message['param'])
                {
                    $child_param = explode('_', $input_message['param']);

                    switch ($child_param[0])
                    {
                        case 'CATEGORY':
                            $category_id = intval($child_param[1]);
                        break;

                        case 'FEATURE':
                            $topic_ids = $this->model('feature')->get_topics_by_feature_id($child_param[1]);
                        break;
                    }
                }

                if ($question_list = $this->model('posts')->get_posts_list('question', $param, 5, null, $topic_ids, $category_id, null, null, true))
                {
                    foreach ($question_list AS $key => $val)
                    {
                        if (!$response_message)
                        {
                            if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                            {
                                $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                            }
                        }
                        else
                        {
                            if ($val['uid'])
                            {
                                $image_file = get_avatar_url($val['uid'], 'max');
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['published_uid'], 'max');
                            }
                        }


                        if ($val['uid'])
                        {
                            $title = $val['title'];
                            $link = get_js_url('/m/article/' . $val['id']);
                        }
                        else
                        {
                            $title = $val['question_content'];
                            $link = get_js_url('/m/question/' . $val['question_id']);
                        }

                        $response_message[] = array(
                            'title' => $title,
                            'link' => $link,
                            'image_file' => $image_file
                        );
                    }
                }
                else
                {
                    $response_message = '暂无问题';
                }

                break;

            case 'HOME_ACTIONS':
                if ($this->user_id)
                {
                    if ($home_actions = $this->model('actions')->home_activity($this->user_id, calc_page_limit($param, 5)))
                    {
                        foreach ($home_actions AS $key => $val)
                        {
                            if (!$response_message)
                            {
                                if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                                {
                                    $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                                }
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['user_info']['uid'], 'max');
                            }

                            if ($val['associate_action'] == ACTION_LOG::ANSWER_QUESTION OR $val['associate_action'] == ACTION_LOG::ADD_AGREE)
                            {
                                $link = get_js_url('/m/question/' . $val['question_info']['question_id'] . '?answer_id=' . $val['answer_info']['answer_id'] . '&single=TRUE');
                            }
                            else
                            {
                                $link = $val['link'];
                            }

                            $response_message[] = array(
                                'title' => $val['title'],
                                'link' => $link,
                                'image_file' => $image_file
                            );
                        }
                    }
                    else
                    {
                        $response_message = '暂时没有最新动态';
                    }
                }
                else
                {
                    $response_message = $this->bind_message;
                }

                break;

            case 'NOTIFICATIONS':
                if ($this->user_id)
                {
                    if ($notifications = $this->model('notify')->list_notification($this->user_id, 0, calc_page_limit($param, 5)))
                    {
                        $response_message = '最新通知:';

                        foreach($notifications AS $key => $val)
                        {
                            $response_message .= "\n\n• " . $val['message'];
                        }

                        $response_message .= "\n\n请输入 '更多' 显示其他相关内容";
                    }
                    else
                    {
                        $this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");

                        if ($param > 1)
                        {
                            $response_message = '没有更多新通知了';
                        }
                        else
                        {
                            $response_message = '暂时没有新通知';
                        }
                    }
                }
                else
                {
                    $response_message = $this->bind_message;
                }

                break;

            case 'MY_QUESTION':
                if ($this->user_id)
                {
                    if ($user_actions = $this->model('actions')->get_user_actions($this->user_id, calc_page_limit($param, 5), 101))
                    {
                        foreach ($user_actions AS $key => $val)
                        {
                            if (!$response_message)
                            {
                                if (!$image_file = $this->get_client_list_image_by_command('COMMAND_' . $message_code))
                                {
                                    $image_file = AWS_APP::config()->get('weixin')->default_list_image;
                                }
                            }
                            else
                            {
                                $image_file = get_avatar_url($val['question_info']['published_uid'], 'max');
                            }

                            $response_message[] = array(
                                'title' => $val['question_info']['question_content'],
                                'link' => get_js_url('/m/question/' . $val['question_info']['question_id']),
                                'image_file' => $image_file
                            );
                        }
                    }
                    else
                    {
                        $this->delete('weixin_message', "weixin_id = '" . $this->quote($input_message['fromUsername']) . "'");

                        if ($param > 1)
                        {
                            $response_message = '没有更多提问了';
                        }
                        else
                        {
                            $response_message = '你还没有进行提问';
                        }
                    }
                }
                else
                {
                    $response_message = $this->bind_message;
                }

                break;
        }

        if (!$response_message)
        {
            return false;
        }

        return array(
            'message' => $response_message,
            'action' => $message_code . '-' . ($param + 1)
        );
    }

    public function check_signature($mp_token, $signature, $timestamp, $nonce)
    {
        $tmp_signature = $this->generate_signature($mp_token, $timestamp, $nonce);

        if (!$tmp_signature OR $tmp_signature != $signature)
        {
            return false;
        }

        return true;
    }

    public function generate_signature($token, $timestamp, $nonce)
    {
        $token = trim($token);

        if (!$token OR !$timestamp OR !$nonce)
        {
            return false;
        }

        $tmp_arr = array(
            $token,
            $timestamp,
            $nonce
        );

        sort($tmp_arr, SORT_STRING);

        return sha1(implode('', $tmp_arr));
    }

    public function is_language($string, $type)
    {
        if (!$characteristic = AWS_APP::config()->get('weixin')->language_characteristic[$type])
        {
            return false;
        }

        $string = trim(strtolower($string));

        foreach ($characteristic AS $key => $text)
        {
            if ($string == $text)
            {
                return true;
            }
        }
    }

    public function process_last_action($weixin_id)
    {
        if (!$last_action = $this->get_last_message($weixin_id))
        {
            return '您好, 请问需要什么帮助?';
        }

        if (!$last_action['action'])
        {
            return false;
        }

        $this->delete('weixin_message', "weixin_id = '" . $this->quote($weixin_id) . "'");

        if (strstr($last_action['action'], '-'))
        {
            $last_actions = explode('-', $last_action['action']);

            $last_action['action'] = $last_actions[0];
            $last_action_param = $last_actions[1];
        }

        if ($response = $this->message_parser(array(
            'content' => $last_action['action'],
            'fromUsername' => $weixin_id
        ), $last_action_param))
        {
            $response_message = $response['message'];
            $action = $response['action'];
        }
        else
        {
            $response_message = '您好, 请问需要什么帮助?';
        }

        return array(
            'message' => $response_message,
            'action' => $action
        );
    }

    public function get_last_message($weixin_id)
    {
        return $this->fetch_row('weixin_message', "weixin_id = '" . $this->quote($weixin_id) . "' AND `time` > " . (time() - 3600));
    }

    public function fetch_reply_rule_list($account_id = 0)
    {
        return $this->fetch_all('weixin_reply_rule', 'account_id = ' . intval($account_id), 'keyword ASC');
    }

    public function fetch_unique_reply_rule_list($account_id = 0)
    {
        return $this->query_all("SELECT * FROM `" . get_table('weixin_reply_rule') . "`", null, null, 'account_id = ' . intval($account_id), 'keyword');
    }

    public function add_reply_rule($account_id = 0, $keyword, $title, $description = '', $link = '', $image_file = '')
    {
        return $this->insert('weixin_reply_rule', array(
            'account_id' => intval($account_id),
            'keyword' => trim($keyword),
            'title' => $title,
            'description' => $description,
            'image_file' => $image_file,
            'link' => $link,
            'enabled' => 1
        ));
    }

    public function update_reply_rule_enabled($id, $status)
    {
        return $this->update('weixin_reply_rule', array(
            'enabled' => intval($status)
        ), 'id = ' . intval($id));
    }

    public function update_reply_rule_sort($id, $status)
    {
        return $this->update('weixin_reply_rule', array(
            'sort_status' => intval($status)
        ), 'id = ' . intval($id));
    }

    public function update_reply_rule($id, $title, $description = '', $link = '', $image_file = '')
    {
        return $this->update('weixin_reply_rule', array(
            'title' => $title,
            'description' => $description,
            'image_file' => $image_file,
            'link' => $link
        ), 'id = ' . intval($id));
    }

    public function get_reply_rule_by_id($id)
    {
        return $this->fetch_row('weixin_reply_rule', 'id = ' . intval($id));
    }

    public function get_reply_rule_by_keyword($account_id, $keyword)
    {
        return $this->fetch_row('weixin_reply_rule', 'account_id = ' . intval($account_id) . ' AND keyword = "' . trim($this->quote($keyword)) . '"');
    }

    public function create_response_by_reply_rule_keyword($keyword)
    {
        if (!$keyword)
        {
            return false;
        }

        // is text message
        if ($reply_rule = $this->fetch_row('weixin_reply_rule', 'account_id = ' . intval($this->account_info['id']) . " AND keyword = '" . trim($this->quote($keyword)) . "' AND (image_file = '' OR image_file IS NULL) AND enabled = 1"))
        {
            return $reply_rule['title'];
        }

        if ($reply_rule = $this->fetch_all('weixin_reply_rule', 'account_id = ' . intval($this->account_info['id']) . " AND keyword = '" . trim($this->quote($keyword)) . "' AND image_file <> '' AND enabled = 1", 'sort_status ASC', 5))
        {
            return $reply_rule;
        }
    }

    public function remove_reply_rule($id)
    {
        $reply_rule_info = $this->get_reply_rule_by_id($id);

        if (!$reply_rule_info)
        {
            return false;
        }

        $this->delete('weixin_reply_rule', 'id = ' . $reply_rule_info['id']);

        if ($reply_rule_info['image_file'])
        {
            unlink(get_setting('upload_dir') . '/weixin/' . $reply_rule_info['image_file']);
            unlink(get_setting('upload_dir') . '/weixin/square_' . $reply_rule_info['image_file']);
        }
    }

    public function remove_weixin_account($account_id)
    {
        $account_id = intval($account_id);

        if ($account_id == 0)
        {
            return false;
        }

        $this->delete('weixin_accounts', 'id = ' . $account_id);

        $reply_rules_info = $this->fetch_all('weixin_reply_rule', 'account_id = ' . $account_id);

        if ($reply_rules_info)
        {
            $this->delete('weixin_reply_rule', 'account_id = ' . $account_id);

            foreach ($reply_rules_info AS $reply_rule_info)
            {
                if ($reply_rule_info['image_file'])
                {
                    unlink(get_setting('upload_dir') . '/weixin/' . $reply_rule_info['image_file']);
                    unlink(get_setting('upload_dir') . '/weixin/square_' . $reply_rule_info['image_file']);
                }
            }
        }

        $this->model('openid_weixin_third')->remove_third_party_api_by_account_id($account_id);
    }

    public function get_weixin_rule_image($image_file, $size = '')
    {
        if ($size)
        {
            $size .= '_';
        }

        return get_setting('upload_url') . '/weixin/' . $size . $image_file;
    }

    public function send_text_message($openid, $message, $url = null)
    {
        $app_id = get_setting('weixin_app_id');

        $app_secret = get_setting('weixin_app_secret');

        if (get_setting('weixin_account_role') != 'service' OR !$app_id OR !$app_secret)
        {
            return false;
        }

        if ($url)
        {
            $message_body = array(
                'touser' => $openid,
                'msgtype' => 'news',
                'news' => array(
                    'articles' => array(
                        array(
                            'title' => '通知消息',
                            'description' => $message,
                            'url' => $url
                        )
                    )
                )
            );
        }
        else
        {
            $message_body = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $message
                )
            );
        }

        $result = $this->model('openid_weixin_weixin')->access_request($app_id, $app_secret, 'message/custom/send', 'POST', $this->replace_post($message_body));

        if (!$result)
        {
            return false;
        }

        if ($result['errcode'] == 40001)
        {
            return $this->send_text_message($openid, $message, $url);
        }

        return $result;
    }

    public function update_client_menu($account_info)
    {
        if (!$account_info['weixin_mp_menu'])
        {
            return false;
        }

        foreach ($account_info['weixin_mp_menu'] AS $key => $val)
        {
            if ($val['sub_button'])
            {
                foreach ($val['sub_button'] AS $sub_key => $sub_val)
                {
                    unset($sub_val['sort']);
                    unset($sub_val['command_type']);
                    unset($sub_val['attach_key']);

                    if ($sub_val['type'] == 'view')
                    {
                        unset($sub_val['key']);

                        if (strstr($sub_val['url'], base_url()) AND $account_info['weixin_account_role'] == 'service')
                        {
                            $sub_val['url'] = $this->model('openid_weixin_weixin')->redirect_url($sub_val['url']);
                        }
                    }

                    $val['sub_button_no_key'][] = $sub_val;
                }

                $val['sub_button'] = $val['sub_button_no_key'];

                unset($val['sub_button_no_key']);
            }

            unset($val['sort']);
            unset($val['command_type']);
            unset($val['attach_key']);

            if ($val['type'] == 'view')
            {
                unset($val['key']);

                if (strstr($val['url'], base_url()) AND $account_info['weixin_account_role'] == 'service')
                {
                    $val['url'] = $this->model('openid_weixin_weixin')->redirect_url($val['url']);
                }
            }

            $mp_menu_no_key[] = $val;
        }

        $result = $this->model('openid_weixin_weixin')->access_request(
                        $account_info['weixin_app_id'],
                        $account_info['weixin_app_secret'],
                        'menu/create',
                        'POST',
                        $this->replace_post(array('button' => $mp_menu_no_key))
                    );

        if (!$result)
        {
            return AWS_APP::lang()->_t('远程服务器忙,请稍后再试');
        }

        if ($result['errcode'] != 0)
        {
            return $result['errmsg'];
        }
    }

    public function get_client_list_image_by_command($command)
    {
        if (!$this->account_info['weixin_mp_menu'])
        {
            return false;
        }

        foreach ($this->account_info['weixin_mp_menu'] AS $key => $val)
        {
            if ($val['sub_button'])
            {
                foreach ($val['sub_button'] AS $sub_key => $sub_val)
                {
                    if ($sub_key == $command)
                    {
                        return $sub_val['attch_key'];
                    }
                }
            }

            if ($key == $command)
            {
                return $val['attch_key'];
            }
        }
    }

    public function client_list_image_clean($mp_menu)
    {
        if (!is_dir(ROOT_PATH . 'weixin/list_image/'))
        {
            return false;
        }

        foreach ($mp_menu AS $key => $val)
        {
            if ($val['sub_button'])
            {
                foreach ($val['sub_button'] AS $sub_key => $sub_val)
                {
                    $attach_list[] = $sub_val['attch_key'] . '.jpg';
                }
            }

            $attach_list[] = $val['attch_key'] . '.jpg';
        }

        $files_list = fetch_file_lists(ROOT_PATH . 'weixin/list_image/', 'jpg');

        foreach ($files_list AS $search_file)
        {
            if (!in_array(str_replace('square_', '', base_name($search_file))))
            {
                unlink($search_file);
            }
        }
    }

    public function process_mp_menu_post_data($mp_menu_post_data)
    {
        if (!$mp_menu_post_data)
        {
            $mp_menu_post_data = array();
        }

        uasort($mp_menu_post_data, 'array_key_sort_asc_callback');

        foreach ($mp_menu_post_data AS $key => $val)
        {
            if ($val['sub_button'])
            {
                unset($mp_menu_post_data[$key]['key']);

                uasort($mp_menu_post_data[$key]['sub_button'], 'array_key_sort_asc_callback');

                foreach ($mp_menu_post_data[$key]['sub_button'] AS $sub_key => $sub_value)
                {
                    if ($mp_menu_post_data[$key]['sub_button'][$sub_key]['name'] == '' OR $mp_menu_post_data[$key]['sub_button'][$sub_key]['key'] == '')
                    {
                        unset($mp_menu_post_data[$key]['sub_button'][$sub_key]);

                        continue;
                    }

                    if (substr($mp_menu_post_data[$key]['sub_button'][$sub_key]['key'], 0, 7) == 'http://' OR substr($mp_menu_post_data[$key]['sub_button'][$sub_key]['key'], 0, 8) == 'https://')
                    {
                        $mp_menu_post_data[$key]['sub_button'][$sub_key]['type'] = 'view';
                        $mp_menu_post_data[$key]['sub_button'][$sub_key]['url'] = $mp_menu_post_data[$key]['sub_button'][$sub_key]['key'];
                    }
                    else
                    {
                        $mp_menu_post_data[$key]['sub_button'][$sub_key]['type'] = 'click';
                    }
                }
            }
            else
            {
                $mp_menu_post_data[$key]['type'] = 'click';
            }

            if ($mp_menu_post_data[$key]['name'] == '')
            {
                unset($mp_menu_post_data[$key]);
            }

            if (substr($mp_menu_post_data[$key]['key'], 0, 7) == 'http://' OR substr($mp_menu_post_data[$key]['key'], 0, 8) == 'https://')
            {
                $mp_menu_post_data[$key]['type'] = 'view';
                $mp_menu_post_data[$key]['url'] = $mp_menu_post_data[$key]['key'];
            }
        }

        return $mp_menu_post_data;
    }

    public function get_msg_details_by_id($msg_id)
    {
        static $msgs_details;

        if (!$msgs_details[$msg_id])
        {
            $msgs_details[$msg_id] = $this->fetch_row('weixin_msg', 'id = ' . intval($msg_id));

            if (!$msgs_details[$msg_id])
            {
                return false;
            }

            if (!$msgs_details[$msg_id]['main_msg'])
            {
                unset($msgs_details[$msg_id]['main_msg']);
            }
            else
            {
                $msgs_details[$msg_id]['main_msg'] = unserialize($msgs_details[$msg_id]['main_msg']);
            }

            if (!$msgs_details[$msg_id]['articles_info'])
            {
                unset($msgs_details[$msg_id]['articles_info']);
            }
            else
            {
                $msgs_details[$msg_id]['articles_info'] = unserialize($msgs_details[$msg_id]['articles_info']);
            }

            if (!$msgs_details[$msg_id]['questions_info'])
            {
                unset($msgs_details[$msg_id]['questions_info']);
            }
            else
            {
                $msgs_details[$msg_id]['questions_info'] = unserialize($msgs_details[$msg_id]['questions_info']);
            }
        }

        return $msgs_details[$msg_id];
    }

    public function get_groups()
    {
        $groups = AWS_APP::cache()->get('weixin_groups');

        if (!$groups)
        {
            $result = $this->model('openid_weixin_weixin')->access_request(
                            get_setting('weixin_app_id'),
                            get_setting('weixin_app_secret'),
                            'groups/get',
                            'GET'
                        );

            if (!$result)
            {
                return AWS_APP::lang()->_t('远程服务器忙');
            }

            if ($result['errcode'])
            {
                return $result['errmsg'];
            }

            foreach ($result['groups'] AS $group)
            {
                $groups[$group['id']] = $group;
            }

            AWS_APP::cache()->set('weixin_groups', $groups, get_setting('cache_level_normal'));
        }

        return $groups;
    }

    public function add_main_msg_to_mpnews($main_msg)
    {
        $result = $this->model('openid_weixin_weixin')->upload_file($main_msg['img'], 'image');

        if (!$result)
        {
            return AWS_APP::lang()->_t('远程服务器忙');
        }

        if ($result['errcode'])
        {
            return $result['errmsg'];
        }

        $this->mpnews['articles'][] = array(
                                            'thumb_media_id' => $result['media_id'],
                                            'author' => $main_msg['author'],
                                            'title' => $main_msg['title'],
                                            'content_source_url' => $main_msg['url'],
                                            'content' => $main_msg['content'],
                                            'show_cover_pic' => $main_msg['show_cover_pic']
                                        );

        $this->to_save_main_msg = array(
                                        'title' => $main_msg['title'],
                                        'url' => $main_msg['url']
                                    );
    }

    public function add_articles_to_mpnews($article_ids)
    {
        $articles_info = $this->model('article')->get_article_info_by_ids($article_ids);

        if (!$articles_info)
        {
            return false;
        }

        foreach ($articles_info AS $article_info)
        {
            $published_uids[] = $article_info['uid'];
        }

        $users_info = $this->model('account')->get_user_info_by_uids($published_uids);

        foreach ($articles_info AS $article_info)
        {
            $user_info = $users_info[$article_info['uid']];

            $img = get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], 'max');

            if (!is_file($img))
            {
                $img = ROOT_PATH . 'static/common/avatar-max-img.jpg';
            }

            $result = $this->model('openid_weixin_weixin')->upload_file($img, 'image');

            if (!$result)
            {
                return AWS_APP::lang()->_t('远程服务器忙');
            }

            if ($result['errcode'])
            {
                return $result['errmsg'];
            }

            $this->mpnews['articles'][] = array(
                                                'thumb_media_id' => $result['media_id'],
                                                'author' => $user_info['user_name'],
                                                'title' => $article_info['title'],
                                                'content_source_url' => get_js_url('/m/article/' . $article_info['id']),
                                                'content' => FORMAT::parse_markdown($article_info['message']),
                                                'show_cover_pic' => '0'
                                            );

            $this->to_save_articles[$article_info['id']] = array(
                                                                'id' => $article_info['id'],
                                                                'title' => $article_info['title']
                                                            );
        }
    }

    public function add_questions_to_mpnews($question_ids)
    {
        $questions_info = $this->model('question')->get_question_info_by_ids($question_ids);

        if (!$questions_info)
        {
            return false;
        }

        foreach ($questions_info AS $question_info)
        {
            $published_uids[] = $question_info['published_uid'];
        }

        $users_info = $this->model('account')->get_user_info_by_uids($published_uids);

        foreach ($questions_info AS $question_info)
        {
            $user_info = $users_info[$question_info['published_uid']];

            $img = get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], 'max');

            if (!is_file($img))
            {
                $img = ROOT_PATH . 'static/common/avatar-max-img.jpg';
            }

            $result = $this->model('openid_weixin_weixin')->upload_file($img, 'image');

            if (!$result)
            {
                return AWS_APP::lang()->_t('远程服务器忙');
            }

            if ($result['errmsg'])
            {
                return $result['errmsg'];
            }

            $this->mpnews['articles'][] = array(
                                                'thumb_media_id' => $result['media_id'],
                                                'author' => $user_info['user_name'],
                                                'title' => $question_info['question_content'],
                                                'content_source_url' => get_js_url('/m/question/' . $question_info['question_id']),
                                                'content' => FORMAT::parse_markdown($question_info['question_detail']),
                                                'show_cover_pic' => '0'
                                            );

            $this->to_save_questions[$question_info['question_id']] = array(
                                                                            'id' => $question_info['question_id'],
                                                                            'title' => $question_info['question_content']
                                                                        );
        }
    }

    public function upload_mpnews()
    {
        if (!$this->mpnews)
        {
            return AWS_APP::lang()->_t('没有要群发的内容');
        }

        $result = $this->model('openid_weixin_weixin')->access_request(
                        get_setting('weixin_app_id'),
                        get_setting('weixin_app_secret'),
                        'media/uploadnews',
                        'POST',
                        $this->replace_post($this->mpnews)
                    );

        if (!$result)
        {
            return AWS_APP::lang()->_t('远程服务器忙');
        }

        if ($result['errmsg'])
        {
            return $result['errmsg'];
        }

        $this->media_id = $result['media_id'];
    }

    public function send_msg($group_id, $msgtype)
    {
        $msg = array(
                    'filter' => array(
                                    'group_id' => $group_id,
                                ),
                    $msgtype => array(
                                    'media_id' => $this->media_id,
                                ),
                    'msgtype' => $msgtype
                );

        $result = $this->model('openid_weixin_weixin')->access_request(
                        get_setting('weixin_app_id'),
                        get_setting('weixin_app_secret'),
                        'message/mass/sendall',
                        'POST',
                        $this->replace_post($msg)
                    );

        if (!$result)
        {
            return AWS_APP::lang()->_t('远程服务器忙');
        }

        if ($result['errcode'] != 0)
        {
            return $result['errmsg'];
        }

        $this->msg_id = $result['msg_id'];
    }

    public function save_sent_msg($group_name, $filter_count)
    {
        return $this->insert('weixin_msg', array(
            'msg_id' => $this->msg_id,
            'group_name' => trim($group_name),
            'status' => 'pending',
            'main_msg' => serialize($this->to_save_main_msg),
            'articles_info' => serialize($this->to_save_articles),
            'questions_info' => serialize($this->to_save_questions),
            'create_time' => time(),
            'filter_count' => intval($filter_count)
        ));
    }

    public function create_qr_code($scene_id)
    {
        if (!$scene_id)
        {
            return AWS_APP::lang()->_t('scene_id 错误');
        }

        $result = $this->model('openid_weixin_weixin')->access_request(
                        get_setting('weixin_app_id'),
                        get_setting('weixin_app_secret'),
                        'qrcode/create',
                        'POST',
                        $this->replace_post(array(
                            'action_name' => 'QR_LIMIT_SCENE',
                            'action_info' => array(
                                'scene' => array('scene_id' => intval($scene_id))
                        ))));

        if (!$result)
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return AWS_APP::lang()->_t('远程服务器忙');
        }

        if ($result['errcode'])
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return $result['errmsg'];
        }

        if (!$result['ticket'])
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return AWS_APP::lang()->_t('获取 ticket 失败');
        }

        $this->update('weixin_qr_code', array('ticket' => $result['ticket']), 'scene_id = ' . intval($scene_id));

        $qr_code = curl_get_contents('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($result['ticket']));

        if (!$qr_code)
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return AWS_APP::lang()->_t('换取二维码失败');
        }

        $img_file_name = intval($scene_id) . '.jpg';

        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg',
            'upload_path' => get_setting('upload_dir') . '/weixin_qr_code',
            'is_image' => TRUE,
            'overwrite' => TRUE,
            'file_name' => $img_file_name
        ));

        AWS_APP::upload()->do_upload($img_file_name, $qr_code);

        $upload_error = AWS_APP::upload()->get_error();

        if ($upload_error)
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return AWS_APP::lang()->_t('保存二维码图片失败，错误为 %s', $upload_error);
        }

        $upload_data = AWS_APP::upload()->data();

        if (!$upload_data)
        {
            $this->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

            return AWS_APP::lang()->_t('保存二维码图片失败，请与管理员联系');
        }
    }

    public function remove_qr_code($scene_id)
    {
        if (!$scene_id)
        {
            return false;
        }

        $this->model('weixin')->delete('weixin_qr_code', 'scene_id = ' . intval($scene_id));

        @unlink(get_setting('upload_dir') . '/weixin_qr_code/' . intval($scene_id) . '.jpg');
    }

    public function decrypt_msg($encrypt)
    {
        $pc = new Services_Weixin_WXBizMsgCrypt($this->account_info['weixin_mp_token'], $this->account_info['weixin_encoding_aes_key'], $this->account_info['weixin_app_id']);

        $format = '<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>';

        $from_xml = sprintf($format, $encrypt);

        $decrypted_msg = '';

        $app_id = '';

        $err_code = $pc->decryptMsg($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $from_xml, $decrypted_msg, $app_id);

        if ($err_code != 0)
        {
            return false;
        }

        if (!$this->account_info['weixin_app_id'])
        {
            $this->account_info['weixin_app_id'] = $app_id;
        }

        return (array)simplexml_load_string($decrypted_msg, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    public function encrypt_msg($msg)
    {
        $pc = new Services_Weixin_WXBizMsgCrypt($this->account_info['weixin_mp_token'], $this->account_info['weixin_encoding_aes_key'], $this->account_info['weixin_app_id']);

        $encrypted_msg = '';

        $err_code = $pc->encryptMsg($msg, time(), mt_rand(1000000000, 9999999999), $encrypted_msg);

        if ($err_code != 0)
        {
            return false;
        }

        return $encrypted_msg;
    }
}
