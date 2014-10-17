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

class ajax_help extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
    }

    public function save_chapter_action()
    {
        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写章节标题')));
        }

        if ($_POST['id'])
        {
            $chapter_info = $this->model('help')->get_chapter_by_id($_POST['id']);

            if (!$chapter_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定章节不存在')));
            }
        }

        if ($chapter_info)
        {
            $this->model('help')->save_chapter($chapter_info['id'], $_POST['title'], $_POST['description'], $_POST['url_token']);

            $id = $chapter_info['id'];
        }
        else
        {
            $id = $this->model('help')->save_chapter(null, $_POST['title'], $_POST['description'], $_POST['url_token']);

            if (!$id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存失败')));
            }
        }

        if ($_FILES['icon']['name'])
        {
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png,gif',
                'upload_path' => get_setting('upload_dir') . '/chapter',
                'is_image' => TRUE
            ))->do_upload('icon');


            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));

                        break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));

                        break;
                }
            }

            $upload_data = AWS_APP::upload()->data();

            if (!$upload_data)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            foreach (AWS_APP::config()->get('image')->chapter_thumbnail as $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $id . "-" . $key . '.jpg';

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            @unlink($upload_data['full_path']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/help/list/')
        ), 1, null));
    }

    public function save_chapter_sort_action()
    {
        if (!$_POST['sort'] OR !is_array($_POST['sort']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        foreach ($_POST['sort'] AS $id => $sort)
        {
            $this->model('help')->set_chapter_sort($id, $sort);
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('章节排序已自动保存')));
    }

    public function remove_chapter_action()
    {
        if (!$this->model('help')->remove_chapter($_POST['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('删除章节失败')));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_data_sort_action()
    {
        if (!$_POST['data'] OR !is_array($_POST['data']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        foreach ($_POST['data'] AS $data_info)
        {
            $this->model('help')->set_data_sort($data_info['id'], $data_info['type'], $data_info['sort']);
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('数据排序已自动保存')));
    }

    public function remove_data_action()
    {
        if (!$_POST['type'] OR !$_POST['item_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        $this->model('help')->remove_data($_POST['type'], $_POST['item_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
