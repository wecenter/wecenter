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
    die;
}

class main extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

        if ($this->user_info['permission']['visit_chapter'] AND $this->user_info['permission']['visit_site'])
        {
            $rule_action['actions'][] = 'index';
        }

        return $rule_action;
    }

    public function index_action()
    {
        if (is_mobile())
        {
            HTTP::redirect('/m/chapter/' . $_GET['id']);
        }

        if ($_GET['id'])
        {
            $chapter_list = $this->model('chapter')->get_chapter_list();

            if (!$chapter_list)
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定章节不存在'), '/');
            }

            foreach ($chapter_list AS $chapter_info)
            {
                if ($chapter_info['url_token'] == $_GET['id'])
                {
                    $chapter = $chapter_info;

                    break;
                }
            }

            if (!$chapter)
            {
                $chapter = $chapter_list[$_GET['id']];
            }

            if (!$chapter)
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定章节不存在'), '/chapter/');
            }

            TPL::assign('chapter_info', $chapter);

            $data_list = $this->model('chapter')->get_data_list($chapter['id'], null, true);

            if ($data_list)
            {
                TPL::assign('data_list', $data_list);
            }

            $this->crumb($chapter['title'], '/help/' . ($chapter['url_token']) ? $chapter['url_token'] : $chapter['id']);

            TPL::output('chapter/index');
        }
        else
        {
            $chapter_list = $this->model('chapter')->get_chapter_list();

            if ($chapter_list)
            {
                TPL::assign('chapter_list', $chapter_list);
            }

            $data_list = $this->model('chapter')->get_data_list(null, 5, true);

            if ($data_list)
            {
                TPL::assign('data_list', $data_list);
            }

            $this->crumb(AWS_APP::lang()->_t('帮助中心'), '/help/');

            TPL::output('chapter/square');
        }
    }
}
