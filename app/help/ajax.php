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
        $rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
        }
    }

    public function add_data_action()
    {
        if (!$_POST['id'] OR !$_POST['type'] OR !$_POST['item_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        $chapter_info = $this->model('help')->get_chapter_by_id($_POST['id']);

        if (!$chapter_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定章节不存在')));
        }

        switch ($_POST['type'])
        {
            case 'question':
                $question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);

                if (!$question_info)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定问题不存在')));
                }

                $this->model('help')->add_data($chapter_info['id'], 'question', $question_info['question_id']);

                break;

            case 'article':
                $article_info =  $this->model('article')->get_article_info_by_id($_POST['item_id']);

                if (!$article_info)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定文章不存在')));
                }

                $this->model('help')->add_data($chapter_info['id'], 'article', $article_info['id']);

                break;

            default:
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_data_action()
    {
        if (!$_POST['type'] OR !$_POST['item_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        switch ($_POST['type'])
        {
            case 'question':
                $question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);

                if (!$question_info)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定问题不存在')));
                }

                if (!$question_info['chapter_id'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定问题不在帮助中心中')));
                }

                $this->model('help')->remove_data('question', $question_info['question_id']);

                break;

            case 'article':
                $article_info =  $this->model('article')->get_article_info_by_id($_POST['item_id']);

                if (!$article_info)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定文章不存在')));
                }

                if (!$article_info['chapter_id'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定文章不在帮助中心中')));
                }

                $this->model('help')->remove_data('article', $article_info['id']);

                break;

            default:
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function list_action()
    {
        $chapter_list = $this->model('help')->get_chapter_list();

        if (!$chapter_list)
        {
            H::ajax_json_output(array());
        }

        foreach ($chapter_list AS $chapter_info)
        {
            $output[$chapter_info['id']] = array(
                'id' => $chapter_info['id'],
                'title' => $chapter_info['title'],
                'icon' => get_chapter_icon_url($chapter_info['id'], 'min')
            );
        }

        H::ajax_json_output($output);
    }
}
