<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
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
        $rule_action['rule_type'] = 'white';

        $rule_action['actions'] = array(

        );

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function publish_ticket_action()
    {
        if (!$this->user_info['permission']['publish_ticket'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限发布问题')));
        }

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入问题标题')));
        }

        if (human_valid('ticket_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
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
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('单个工单话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
            }
        }

        if (!$_POST['topics'] AND get_setting('new_question_force_add_topic') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请为工单添加话题')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($_POST['message'], $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        $this->model('draft')->delete_draft(1, 'ticket', $this->user_id);

        $ticket_id = $this->model('publish')->publish_ticket($_POST['title'], $_POST['message'], $this->user_id, $_POST['topics'], $_POST['priority'], $_POST['attach_access_key'], $this->user_info['permission']['create_topic']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/ticket/' . $ticket_id)
        ), 1, null));
    }
}
