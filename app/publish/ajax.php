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

class ajax extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function fetch_question_category_action()
    {
        if (get_setting('category_enable') != 'Y')
        {
            exit(json_encode(array()));
        }

        exit($this->model('system')->build_category_json('question', 0));
    }

    public function attach_upload_action()
    {
        if (get_setting('upload_enable') != 'Y' OR !$_GET['id'])
        {
            die;
        }

        switch ($_GET['id'])
        {
            case 'article':
                $item_type = $_GET['id'];

                break;

            case 'question':
                $item_type = 'questions';

                break;

            default:
                $_GET['id'] = 'answer';

                $item_type = 'answer';

                break;
        }

        AWS_APP::upload()->initialize(array(
            'allowed_types' => get_setting('allowed_upload_types'),
            'upload_path' => get_setting('upload_dir') . '/' . $item_type . '/' . gmdate('Ymd'),
            'is_image' => FALSE,
            'max_size' => get_setting('upload_size_limit')
        ));

        if (isset($_GET['aws_upload_file']))
        {
            AWS_APP::upload()->do_upload($_GET['aws_upload_file'], file_get_contents('php://input'));
        }
        else if (isset($_FILES['aws_upload_file']))
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
        }

        $attach_id = $this->model('publish')->add_attach($_GET['id'], $upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);

        $output = array(
            'success' => true,
            'delete_url' => get_js_url('/publish/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
                'attach_id' => $attach_id,
                'access_key' => $_GET['attach_access_key']
            )))),
            'attach_id' => $attach_id,
            'attach_tag' => 'attach'

        );

        $attach_info = $this->model('publish')->get_attach_by_id($attach_id);

        if ($attach_info['thumb'])
        {
            $output['thumb'] = $attach_info['thumb'];
        }
        else
        {
            $output['class_name'] = $this->model('publish')->get_file_class(basename($upload_data['full_path']));
        }

        exit(htmlspecialchars(json_encode($output), ENT_NOQUOTES));
    }

    public function article_attach_edit_list_action()
    {
        if (! $article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
        }

        if ($article_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
        }

        if ($article_attach = $this->model('publish')->get_attach('article', $_POST['article_id']))
        {
            foreach ($article_attach as $attach_id => $val)
            {
                $article_attach[$attach_id]['class_name'] = $this->model('publish')->get_file_class($val['file_name']);

                $article_attach[$attach_id]['delete_link'] = get_js_url('/publish/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
                    'attach_id' => $attach_id,
                    'access_key' => $val['access_key']
                ))));

                $article_attach[$attach_id]['attach_id'] = $attach_id;
                $article_attach[$attach_id]['attach_tag'] = 'attach';
            }
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'attachs' => $article_attach
        ), 1, null));
    }

    public function question_attach_edit_list_action()
    {
        if (! $question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('无法获取附件列表')));
        }

        if ($question_info['published_uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
        }

        if ($question_attach = $this->model('publish')->get_attach('question', $_POST['question_id']))
        {
            foreach ($question_attach as $attach_id => $val)
            {
                $question_attach[$attach_id]['class_name'] = $this->model('publish')->get_file_class($val['file_name']);

                $question_attach[$attach_id]['delete_link'] = get_js_url('/publish/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
                    'attach_id' => $attach_id,
                    'access_key' => $val['access_key']
                ))));

                $question_attach[$attach_id]['attach_id'] = $attach_id;
                $question_attach[$attach_id]['attach_tag'] = 'attach';
            }
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'attachs' => $question_attach
        ), 1, null));
    }

    public function answer_attach_edit_list_action()
    {
        if (!$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复不存在')));
        }

        if ($answer_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个附件列表')));
        }

        if ($answer_attach = $this->model('publish')->get_attach('answer', $_POST['answer_id']))
        {
            foreach ($answer_attach as $attach_id => $val)
            {
                $answer_attach[$attach_id]['class_name'] = $this->model('publish')->get_file_class($val['file_name']);
                $answer_attach[$attach_id]['delete_link'] = get_js_url('/publish/ajax/remove_attach/attach_id-' . base64_encode(H::encode_hash(array(
                    'attach_id' => $attach_id,
                    'access_key' => $val['access_key']
                ))));

                $answer_attach[$attach_id]['attach_id'] = $attach_id;
                $answer_attach[$attach_id]['attach_tag'] = 'attach';
            }
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'attachs' => $answer_attach
        ), 1, null));
    }

    public function remove_attach_action()
    {
        if ($attach_info = H::decode_hash(base64_decode($_GET['attach_id'])))
        {
            $this->model('publish')->remove_attach($attach_info['attach_id'], $attach_info['access_key']);
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function modify_question_action()
    {
        if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
        }

        if ($question_info['lock'] AND !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题已锁定, 不能编辑')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'])
        {
            if ($question_info['published_uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个问题')));
            }
        }

        if (!$_POST['category_id'] AND get_setting('category_enable') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请选择分类')));
        }

        if (cjk_strlen($_POST['question_content']) < 5)
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得少于 5 个字')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($_POST['question_content']) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得大于') . ' ' . get_setting('question_title_limit') . ' ' . AWS_APP::lang()->_t('字节')));
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['question_detail']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['question_detail'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'question', $this->user_id);

        if ($_POST['do_delete'] AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
        }

        if ($_POST['do_delete'])
        {
            if ($this->user_id != $question_info['published_uid'])
            {
                $this->model('account')->send_delete_message($question_info['published_uid'], $question_info['question_content'], $question_info['question_detail']);
            }

            $this->model('question')->remove_question($question_info['question_id']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/home/explore/')
            ), 1, null));
        }

        $IS_MODIFY_VERIFIED = TRUE;

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND $question_info['published_uid'] != $this->user_id)
        {
            $IS_MODIFY_VERIFIED = FALSE;
        }

        $this->model('question')->update_question($question_info['question_id'], $_POST['question_content'], $_POST['question_detail'], $this->user_id, $IS_MODIFY_VERIFIED, $_POST['modify_reason'], $question_info['anonymous'], $_POST['category_id']);

        if ($this->user_id != $question_info['published_uid'])
        {
            $this->model('question')->add_focus_question($question_info['question_id'], $this->user_id);

            $this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_MOD_QUESTION, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
                'from_uid' => $this->user_id,
                'question_id' => $question_info['question_id']
            ));

            $this->model('email')->action_email('QUESTION_MOD', $question_info['published_uid'], get_js_url('/question/' . $question_info['question_id']), array(
                'user_name' => $this->user_info['user_name'],
                'question_title' => $question_info['question_content']
            ));
        }

        if ($_POST['category_id'] AND $_POST['category_id'] != $question_info['category_id'])
        {
            $category_info = $this->model('system')->get_category_info($_POST['category_id']);

            ACTION_LOG::save_action($this->user_id, $question_info['question_id'], ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_CATEGORY, $category_info['title'], $category_info['id']);
        }

        if ($_POST['attach_access_key'] AND $IS_MODIFY_VERIFIED)
        {
            if ($this->model('publish')->update_attach('question', $question_info['question_id'], $_POST['attach_access_key']))
            {
                ACTION_LOG::save_action($this->user_id, $question_info['question_id'], ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_ATTACH);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/question/' . $question_info['question_id'] . '?column=log&rf=false')
        ), 1, null));

    }

    public function publish_question_action()
    {
        if (!$this->user_info['permission']['publish_question'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限发布问题')));
        }

        if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
        }

        if (!$_POST['question_content'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入问题标题')));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题分类')));
        }

        if (cjk_strlen($_POST['question_content']) < 5)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题标题字数不得少于 5 个字')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($_POST['question_content']) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得大于 %s 字节', get_setting('question_title_limit'))));
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['question_detail']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if ($_POST['topics'])
        {
            foreach ($_POST['topics'] AS $key => $topic_title)
            {
                $topic_title = trim($topic_title);

                if (!$topic_title)
                {
                    unset($_POST['topics'][$key]);
                }
                else
                {
                    $_POST['topics'][$key] = $topic_title;
                }
            }

            if (get_setting('question_topics_limit') AND sizeof($_POST['topics']) > get_setting('question_topics_limit'))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('单个问题话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
            }
        }

        if (!$_POST['topics'] AND get_setting('new_question_force_add_topic') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为问题添加话题')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['question_detail'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        if ($_POST['weixin_media_id'])
        {
            $_POST['weixin_media_id'] = base64_decode($_POST['weixin_media_id']);

            $weixin_pic_url = AWS_APP::cache()->get('weixin_pic_url_' . md5($_POST['weixin_media_id']));

            if (!$weixin_pic_url)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片已过期或 media_id 无效')));
            }

            $file = $this->model('openid_weixin_weixin')->get_file($_POST['weixin_media_id']);

            if (!$file)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('远程服务器忙')));
            }

            if (is_array($file) AND $file['errmsg'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('获取图片失败，错误为: %s', $file['errmsg'])));
            }

            AWS_APP::upload()->initialize(array(
                'allowed_types' => get_setting('allowed_upload_types'),
                'upload_path' => get_setting('upload_dir') . '/questions/' . gmdate('Ymd'),
                'is_image' => TRUE,
                'max_size' => get_setting('upload_size_limit')
            ));

            AWS_APP::upload()->do_upload($_POST['weixin_media_id'] . '.jpg', $file);

            $upload_error = AWS_APP::upload()->get_error();

            if ($upload_error)
            {
                switch ($upload_error)
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，错误为 %s' . $upload_error)));

                        break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，本站不允许上传 jpeg 格式的图片')));

                        break;

                    case 'upload_invalid_filesize':
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));

                        break;
                }
            }

            $upload_data = AWS_APP::upload()->data();

            if (!$upload_data)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，请与管理员联系')));
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

            $this->model('publish')->add_attach('question', $upload_data['orig_name'], $_POST['attach_access_key'], time(), basename($upload_data['full_path']), true);
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'question', $this->user_id);

        if ($this->publish_approval_valid())
        {
            $this->model('publish')->publish_approval('question', array(
                'question_content' => $_POST['question_content'],
                'question_detail' => $_POST['question_detail'],
                'category_id' => $_POST['category_id'],
                'topics' => $_POST['topics'],
                'anonymous' => $_POST['anonymous'],
                'attach_access_key' => $_POST['attach_access_key'],
                'ask_user_id' => $_POST['ask_user_id'],
                'permission_create_topic' => $this->user_info['permission']['create_topic']
            ), $this->user_id, $_POST['attach_access_key']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/publish/wait_approval/')
            ), 1, null));
        }
        else
        {
            $question_id = $this->model('publish')->publish_question($_POST['question_content'], $_POST['question_detail'], $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);

            if ($_POST['_is_mobile'])
            {
                if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id))
                {
                    if ($weixin_user['location_update'] > time() - 7200)
                    {
                        $this->model('geo')->set_location('question', $question_id, $weixin_user['longitude'], $weixin_user['latitude']);
                    }
                }

                $url = get_js_url('/m/question/' . $question_id);
            }
            else
            {
                $url = get_js_url('/question/' . $question_id);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $url
            ), 1, null));
        }
    }

    public function publish_article_action()
    {
        if (!$this->user_info['permission']['publish_article'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限发布文章')));
        }

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入文章标题')));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章分类')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($_POST['title']) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章标题字数不得大于 %s 字节', get_setting('question_title_limit'))));
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['message']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['message'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if ($_POST['topics'])
        {
            foreach ($_POST['topics'] AS $key => $topic_title)
            {
                $topic_title = trim($topic_title);

                if (!$topic_title)
                {
                    unset($_POST['topics'][$key]);
                }
                else
                {
                    $_POST['topics'][$key] = $topic_title;
                }
            }

            if (get_setting('question_topics_limit') AND sizeof($_POST['topics']) > get_setting('question_topics_limit'))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('单个文章话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
            }
        }
        if (get_setting('new_question_force_add_topic') == 'Y' AND !$_POST['topics'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为文章添加话题')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'article', $this->user_id);

        if ($this->publish_approval_valid())
        {
            $this->model('publish')->publish_approval('article', array(
                'title' => $_POST['title'],
                'message' => $_POST['message'],
                'category_id' => $_POST['category_id'],
                'topics' => $_POST['topics'],
                'permission_create_topic' => $this->user_info['permission']['create_topic']
            ), $this->user_id, $_POST['attach_access_key']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/publish/wait_approval/')
            ), 1, null));
        }
        else
        {
            $article_id = $this->model('publish')->publish_article($_POST['title'], $_POST['message'], $this->user_id, $_POST['topics'], $_POST['category_id'], $_POST['attach_access_key'], $this->user_info['permission']['create_topic']);

            if ($_POST['_is_mobile'])
            {
                $url = get_js_url('/m/article/' . $article_id);
            }
            else
            {
                $url = get_js_url('/article/' . $article_id);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $url
            ), 1, null));
        }
    }

    public function modify_article_action()
    {
        if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
        }

        if ($article_info['lock'] AND !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章已锁定, 不能编辑')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_article'])
        {
            if ($article_info['uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个文章')));
            }
        }

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入文章标题')));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章分类')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($_POST['title']) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章标题字数不得大于') . ' ' . get_setting('question_title_limit') . ' ' . AWS_APP::lang()->_t('字节')));
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['message']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['message'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'article', $this->user_id);

        if ($_POST['do_delete'] AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除文章的权限')));
        }

        if ($_POST['do_delete'])
        {
            if ($this->user_id != $article_info['uid'])
            {
                $this->model('account')->send_delete_message($article_info['uid'], $article_info['title'], $article_info['message']);
            }

            $this->model('article')->remove_article($article_info['id']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/home/explore/')
            ), 1, null));
        }

        $this->model('article')->update_article($article_info['id'], $_POST['title'], $_POST['message'], $_POST['topics'], $_POST['category_id'], $this->user_info['permission']['create_topic']);

        if ($_POST['attach_access_key'])
        {
            $this->model('publish')->update_attach('article', $article_info['id'], $_POST['attach_access_key']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/article/' . $article_info['id'])
        ), 1, null));
    }

    public function save_related_link_action()
    {
        if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            if ($question_info['published_uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限执行该操作')));
            }
        }

        if (substr($_POST['link'], 0, 7) != 'http://' AND substr($_POST['link'], 0, 8) != 'https://')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('链接格式不正确')));
        }

        $this->model('related')->add_related_link($this->user_id, 'question', $_POST['item_id'], $_POST['link']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_related_link_action()
    {
        if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            if ($question_info['published_uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限执行该操作')));
            }
        }

        $this->model('related')->remove_related_link($_POST['id'], $_POST['item_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}