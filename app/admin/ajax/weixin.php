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

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
    die;
}

class ajax_weixin extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function save_third_party_access_rule_status_action()
    {
        if (!$_POST['rule_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要操作的规则')));
        }

        foreach ($_POST['rule_ids'] AS $rule_id)
        {
            $this->model('openid_weixin_third')->update_third_party_api($rule_id, 'update', null, null, $_POST['enabled'][$rule_id], null, $_POST['rank'][$rule_id]);
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function remove_third_party_access_rule_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的规则')));
        }

        if(!$this->model('openid_weixin_third')->get_third_party_api_by_id($_POST['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('接入规则不存在')));
        }

        $this->model('openid_weixin_third')->remove_third_party_api_by_id($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_third_party_access_rule_action()
    {
        if (!$_POST['url'] OR substr($_POST['url'], 0, 7) != 'http://' AND substr($_POST['url'], 0, 8) != 'https://')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的 URL')));
        }

        $_POST['token'] = trim($_POST['token']);

        if (!$_POST['token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入第三方公众平台接口 Token')));
        }

        if ($_POST['id'])
        {
            $rule_info = $this->model('openid_weixin_third')->get_third_party_api_by_id($_POST['id']);

            if (!$rule_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('接入规则不存在')));
            }

            $this->model('openid_weixin_third')->update_third_party_api($rule_info['id'], 'update', $_POST['url'], $_POST['token']);
        }
        else
        {
            $account_info = $this->model('weixin')->get_account_info_by_id($_POST['account_id']);

            if (!$account_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('公众账号不存在')));
            }

            $this->model('openid_weixin_third')->update_third_party_api(null, 'add', $_POST['url'], $_POST['token'], 1, $account_info['id']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('admin/weixin/third_party_access/id-' . $_POST['account_id'])
        ), 1, null));
    }

    public function create_qr_code_action()
    {
        if (!$_POST['description'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入描述')));
        }

        $scene_id = $this->model('weixin')->insert('weixin_qr_code', array('description' => $_POST['description']));

        $result = $this->model('weixin')->create_qr_code($scene_id);

        if ($result)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_qr_code_action()
    {
        if (!$_POST['scene_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的二维码')));
        }

        $this->model('weixin')->remove_qr_code($_POST['scene_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

   public function save_reply_rule_status_action()
   {
        if (!$_POST['rule_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要操作的规则')));
        }

        foreach ($_POST['rule_ids'] AS $rule_id)
        {
            $this->model('weixin')->update_reply_rule_enabled($rule_id, $_POST['enabled_status'][$rule_id]);

            $this->model('weixin')->update_reply_rule_sort($rule_id, $_POST['sort_status'][$rule_id]);
        }

        if ($_POST['is_subscribe'])
        {
            $account_info['weixin_subscribe_message_key'] = $_POST['is_subscribe'];
        }

        if ($_POST['is_no_result'])
        {
            $account_info['weixin_no_result_message_key'] = $_POST['is_no_result'];
        }

        $this->model('weixin')->update_setting_or_account($_POST['account_id'], $account_info);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function remove_reply_rule_action()
    {
        $this->model('weixin')->remove_reply_rule($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function mp_menu_list_image_upload_action()
    {
        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg,jpeg,png,gif',
            'upload_path' => get_setting('upload_dir') . '/weixin/list_image/',
            'is_image' => TRUE,
            'file_name' => str_replace(array('/', '\\', '.'), '', $_GET['attach_access_key']) . '.jpg',
            'encrypt_name' => FALSE
        ));

        if ($_GET['attach_access_key'])
        {
            AWS_APP::upload()->do_upload('aws_upload_file');
        }
        else
        {
            return false;
        }

        if (AWS_APP::upload()->get_error())
        {
            switch (AWS_APP::upload()->get_error())
            {
                default:
                    die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
                break;

                case 'upload_invalid_filetype':
                    die("{'error':'文件类型无效'}");
                break;

                case 'upload_invalid_filesize':
                    die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_size_limit') .  " KB'}");
                break;
            }
        }

        if (! $upload_data = AWS_APP::upload()->data())
        {
            die("{'error':'上传失败, 请与管理员联系'}");
        }

        if ($upload_data['is_image'] == 1)
        {
            AWS_APP::image()->initialize(array(
                'quality' => 90,
                'source_image' => $upload_data['full_path'],
                'new_image' => $upload_data['full_path'],
                'width' => 640,
                'height' => 320
            ))->resize();

            AWS_APP::image()->initialize(array(
                'quality' => 90,
                'source_image' => $upload_data['full_path'],
                'new_image' => get_setting('upload_dir') . '/weixin/list_image/square_' . basename($upload_data['full_path']),
                'width' => 80,
                'height' => 80
            ))->resize();
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_account_action()
    {
        if (!$_POST['type'] OR $_POST['type'] == 'update' AND !$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        $_POST['weixin_mp_token'] = trim($_POST['weixin_mp_token']);

        if (!$_POST['weixin_mp_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信公众平台接口 Token 不能为空')));
        }

        $_POST['weixin_encoding_aes_key'] = trim($_POST['weixin_encoding_aes_key']);

        if ($_POST['weixin_encoding_aes_key'] AND strlen($_POST['weixin_encoding_aes_key']) != 43)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信公众平台接口 EncodingAESKey 应为 43 位')));
        }

        if (!$_POST['weixin_account_role'] OR !in_array($_POST['weixin_account_role'], array('base', 'subscription', 'general', 'service')))
        {
            $_POST['weixin_account_role'] = 'base';
        }

        $account_info = array(
                            'weixin_mp_token' => $_POST['weixin_mp_token'],
                            'weixin_account_role' => $_POST['weixin_account_role'],
                            'weixin_app_id' => trim($_POST['weixin_app_id']),
                            'weixin_app_secret' => trim($_POST['weixin_app_secret']),
                            'weixin_encoding_aes_key' => $_POST['weixin_encoding_aes_key']
                        );

        switch ($_POST['type'])
        {
            case 'add':
                $account_id = $this->model('weixin')->insert('weixin_accounts', $account_info);

                H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/admin/weixin/account/id-' . $account_id)), 1, null));

                break;

            case 'update':
                $this->model('weixin')->update_setting_or_account($_POST['id'], $account_info);

                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));

                break;
        }
    }

    public function remove_weixin_account_action()
    {
        $this->model('weixin')->remove_weixin_account($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function send_msg_action()
    {
        $group_id = intval($_POST['group_id']);

        $groups = $this->model('weixin')->get_groups();

        $group_name = $groups[$group_id]['name'];

        if (!isset($group_name))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('选择的分组不存在')));
        }

        if (!$_POST['main_msg_title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入封面的标题')));
        }

        if (!$_POST['main_msg_author'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入封面的作者')));
        }

        if (!$_POST['main_msg_content'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入封面的内容')));
        }

        if (!$_POST['main_msg_url'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入封面的原文链接')));
        }

        if ($_POST['show_cover_pic'] != 0 AND $_POST['show_cover_pic'] != 1)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择是否显示封面')));
        }

        $article_ids = array_unique(array_filter(explode(',', trim($_POST['article_ids'], ','))));

        $question_ids = array_unique(array_filter(explode(',', trim($_POST['question_ids'], ','))));

/*
        if (!$article_ids AND !$question_ids)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请添加要群发的文章或问题')));
        }
*/

        if (count($article_ids) + count($question_ids) > 9)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('最多可添加 9 个文章和问题')));
        }

        if ($_FILES['main_msg_img']['error'] === UPLOAD_ERR_OK)
        {
            if ($_FILES['main_msg_img']['type'] != 'image/jpeg')
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许上传 jpeg 格式的图片')));
            }

            if ($_FILES['main_msg_img']['size'] > '1048576')
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('图片最大为 1M')));
            }

            $main_msg_img = TEMP_PATH . 'weixin_img.jpg';

            if (!is_uploaded_file($_FILES['main_msg_img']['tmp_name']) OR !move_uploaded_file($_FILES['main_msg_img']['tmp_name'], $main_msg_img))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }
        }
        else
        {
            $main_msg_img = AWS_APP::config()->get('weixin')->default_list_image_path;
        }

        $main_msg = array(
            'author' => $_POST['main_msg_author'],
            'title' => $_POST['main_msg_title'],
            'url' => $_POST['main_msg_url'],
            'content' => $_POST['main_msg_content'],
            'img' => $main_msg_img,
            'show_cover_pic' => $_POST['show_cover_pic']
        );

        $error_msg = $this->model('weixin')->add_main_msg_to_mpnews($main_msg);

        if (isset($error_msg))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传封面图失败, 错误信息: %s', $error_msg)));
        }

        if ($article_ids)
        {
            $error_msg = $this->model('weixin')->add_articles_to_mpnews($article_ids);

            if (isset($error_msg))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传文章作者的头像失败, 错误信息: %s', $error_msg)));
            }
        }

        if ($question_ids)
        {
            $error_msg = $this->model('weixin')->add_questions_to_mpnews($question_ids);

            if (isset($error_msg))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传问题作者的头像失败, 错误信息: %s', $error_msg)));
            }
        }

        $error_msg = $this->model('weixin')->upload_mpnews();

        if (isset($error_msg))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传图文消息失败, 错误为: %s', $error_msg)));
        }

        $error_msg = $this->model('weixin')->send_msg($group_id, 'mpnews');

        if (isset($error_msg))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('群发任务提交失败, 错误信息: %s', $error_msg)));
        }

        $msg_id = $this->model('weixin')->save_sent_msg($group_name, $groups[$group_id]['count']);

        if (is_file(TEMP_PATH . 'weixin_img.jpg'))
        {
            @unlink(TEMP_PATH . 'weixin_img.jpg');
        }

        H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/admin/weixin/sent_msg_details/id-' . $msg_id)), 1, null));
    }
}
