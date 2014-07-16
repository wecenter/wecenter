<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
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

class ajax extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function login_process_action()
    {
        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, '请填写正确的验证码'));
        }

        if (get_setting('ucenter_enabled') == 'Y')
        {
            if (! $user_info = $this->model('ucenter')->login($this->user_info['email'], $_POST['password']))
            {
                $user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
            }
        }
        else
        {
            $user_info = $this->model('account')->check_login($this->user_info['email'], $_POST['password']);
        }

        if ($user_info['uid'])
        {
            $this->model('admin')->set_admin_login($user_info['uid']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('/admin/')
            ), 1, null));
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('帐号或密码错误')));
        }
    }

    public function save_settings_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['upload_dir'] AND preg_match('/(.*)\/$/i', $_POST['upload_dir']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传文件存放绝对路径不能以 / 结尾')));
        }

        if ($_POST['upload_url'] AND preg_match('/(.*)\/$/i', $_POST['upload_url']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传目录外部访问 URL 地址不能以 / 结尾')));
        }

        if ($_POST['request_route_custom'])
        {
            $_POST['request_route_custom'] = trim($_POST['request_route_custom']);

            if ($request_routes = explode("\n", $_POST['request_route_custom']))
            {
                foreach ($request_routes as $key => $val)
                {
                    if (! strstr($val, '==='))
                    {
                        continue;
                    }

                    list($m, $n) = explode('===', $val);

                    if (substr($n, 0, 1) != '/' OR substr($m, 0, 1) != '/')
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
                    }

                    if (strstr($m, '/admin') OR strstr($n, '/admin'))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则不允许设置 /admin 路由')));
                    }
                }
            }
        }

        if ($_POST['censoruser'])
        {
            $_POST['censoruser'] = trim($_POST['censoruser']);
        }

        if ($_POST['report_reason'])
        {
            $_POST['report_reason'] = trim($_POST['report_reason']);
        }

        if ($_POST['sensitive_words'])
        {
            $_POST['sensitive_words'] = trim($_POST['sensitive_words']);
        }

        $curl_require_setting = array('qq_login_enabled', 'sina_weibo_enabled', 'qq_t_enabled');

        if (array_intersect(array_keys($_POST), $curl_require_setting))
        {
            foreach ($curl_require_setting AS $key)
            {
                if ($_POST[$key] == 'Y' AND !function_exists('curl_init'))
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微博登录、QQ 登录等功能须服务器支持 CURL')));
                }
            }
        }

        if ($_POST['set_notification_settings'])
        {
            if ($notify_actions = $this->model('notify')->notify_action_details)
            {
                $notification_setting = array();

                foreach ($notify_actions as $key => $val)
                {
                    if (! isset($_POST['new_user_notification_setting'][$key]) AND $val['user_setting'])
                    {
                        $notification_setting[] = intval($key);
                    }
                }
            }

            $_POST['new_user_notification_setting'] = $notification_setting;
        }

        if ($_POST['set_email_settings'])
        {
            $email_settings = array(
                'FOLLOW_ME' => 'N',
                'QUESTION_INVITE' => 'N',
                'NEW_ANSWER' => 'N',
                'NEW_MESSAGE' => 'N',
                'QUESTION_MOD' => 'N',
            );

            if ($_POST['new_user_email_setting'])
            {
                foreach ($_POST['new_user_email_setting'] AS $key => $val)
                {
                    unset($email_settings[$val]);
                }
            }

            $_POST['new_user_email_setting'] = $email_settings;
        }

        $this->model('setting')->set_vars($_POST);

        if ($_POST['wecenter_access_token'])
        {
            $this->model('weixin')->get_weixin_app_id_setting_var();
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public function approval_manage_action()
    {
        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        if ($_POST['type'] == 'weibo_msg')
        {
            switch ($_POST['batch_type'])
            {
                case 'approval':
                    foreach ($_POST['approval_ids'] AS $approval_id)
                    {
                        $result = $this->model('weibo')->save_msg_info_to_question($approval_id);

                        if ($result)
                        {
                            H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
                        }
                    }

                    break;

                case 'decline':
                    $this->model('weibo')->del_msg_by_id($_POST['approval_ids']);

                    foreach ($_POST['approval_ids'] AS $approval_id)
                    {
                        $attachs = $this->model('publish')->get_attach('weibo_msg', $approval_id);

                        if ($attachs)
                        {
                            foreach ($attachs AS $attach)
                            {
                                $this->model('publish')->remove_attach($attach['id'], $attach['access_key']);
                            }
                        }
                    }

                    break;
            }
        }
        else
        {
            switch ($_POST['batch_type'])
            {
                case 'approval':
                case 'decline':
                    $func = $_POST['batch_type'] . '_publish';

                    foreach ($_POST['approval_ids'] AS $approval_id)
                    {
                        $this->model('publish')->$func($approval_id);
                    }
                break;
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function article_manage_action()
    {
        if (!$_POST['article_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'del':
                foreach ($_POST['article_ids'] AS $article_id)
                {
                    $this->model('article')->remove_article($article_id);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function save_category_sort_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (is_array($_POST['category']))
        {
            foreach ($_POST['category'] as $key => $val)
            {
                $this->model('category')->set_category_sort($key, $val['sort']);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类排序已自动保存')));
    }

    public function save_category_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['category_id'] AND $_POST['parent_id'] AND $category_list = $this->model('system')->fetch_category('question', $_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统允许最多二级分类, 当前分类下有子分类, 不能移动到其它分类')));
        }

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入分类名称')));
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_POST['category_id'] != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名不可以全为数字')));
            }

            if ($this->model('category')->check_url_token($_POST['url_token'], $_POST['category_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
            }
        }

        if (! $_POST['category_id'])
        {
            $category_id = $this->model('category')->add_category('question', $_POST['title'], $_POST['parent_id']);
        }
        else
        {
            $category_id = intval($_POST['category_id']);
        }

        $category = $this->model('system')->get_category_info($category_id);

        if ($category['id'] == $_POST['parent_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
        }

        $this->model('category')->update_category_info($category_id, $_POST['title'], $_POST['parent_id'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/category/list/')
        ), 1, null));
    }

    public function remove_category_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (intval($_POST['category_id']) == 1)
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('默认分类不可删除')));
        }

        if ($this->model('category')->contents_exists($_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
        }

        $this->model('category')->delete_category('question', $_POST['category_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function move_category_contents_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$_POST['from_id'] OR !$_POST['target_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
        }

        if ($_POST['target_id'] == $_POST['from_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
        }

        $this->model('category')->move_contents($_POST['from_id'], $_POST['target_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edm_add_group_action()
    {
        @set_time_limit(0);

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写用户群名称')));
        }

        $usergroup_id = $this->model('edm')->add_group($_POST['title']);

        switch ($_POST['import_type'])
        {
            case 'text':
                if ($email_list = explode("\n", str_replace(array("\r", "\t"), "\n", $_POST['email'])))
                {
                    foreach ($email_list AS $key => $email)
                    {
                        $this->model('edm')->add_user_data($usergroup_id, $email);
                    }
                }
            break;

            case 'system_group':
                if ($_POST['user_groups'])
                {
                    foreach ($_POST['user_groups'] AS $key => $val)
                    {
                        $this->model('edm')->import_system_email_by_user_group($usergroup_id, $val);
                    }
                }
            break;

            case 'reputation_group':
                if ($_POST['user_groups'])
                {
                    foreach ($_POST['user_groups'] AS $key => $val)
                    {
                        $this->model('edm')->import_system_email_by_reputation_group($usergroup_id, $val);
                    }
                }
            break;

            case 'last_active':
                if ($_POST['last_active'])
                {
                    $this->model('edm')->import_system_email_by_last_active($usergroup_id, $_POST['last_active']);
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edm_add_task_action()
    {
        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写任务名称')));
        }

        if (trim($_POST['subject']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写邮件标题')));
        }

        if (intval($_POST['usergroup_id']) == 0)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户群组')));
        }

        if (trim($_POST['from_name']) == '')
        {
            $_POST['from_name'] = get_setting('site_name');
        }

        $task_id = $this->model('edm')->add_task($_POST['title'], $_POST['subject'], $_POST['message'], $_POST['from_name']);

        $this->model('edm')->import_group_data_to_task($task_id, $_POST['usergroup_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('任务建立完成')));

    }

    public function save_feature_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $feature_id = intval($_GET['feature_id']);

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题标题不能为空')));
        }

        if ($feature_id)
        {
            $feature = $this->model('feature')->get_feature_by_id($feature_id);
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($feature_id != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名不可以全为数字')));
            }

            if ($this->model('feature')->check_url_token($_POST['url_token'], $feature_id))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('专题别名已经被占用请更换一个')));
            }
        }

        if (! $feature_id)
        {
            $feature_id = $this->model('feature')->add_feature($_POST['title']);
        }

        if ($_POST['topics'])
        {
            if ($topics = explode("\n", $_POST['topics']))
            {
                $this->model('feature')->empty_topics($feature_id);
            }

            foreach ($topics AS $key => $topic_title)
            {
                if ($topic_info = $this->model('topic')->get_topic_by_title(trim($topic_title)))
                {
                    $this->model('feature')->add_topic($feature_id, $topic_info['topic_id']);
                }
            }
        }

        $update_data = array(
            'title' => $_POST['title'],
            'description' => htmlspecialchars($_POST['description']),
            'css' => htmlspecialchars($_POST['css']),
            'url_token' => $_POST['url_token'],
            'seo_title' => htmlspecialchars($_POST['seo_title'])
        );

        if ($_FILES['icon']['name'])
        {
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png,gif',
                'upload_path' => get_setting('upload_dir') . '/feature',
                'is_image' => TRUE
            ))->do_upload('icon');


            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                    break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
                    break;
                }
            }

            if (! $upload_data = AWS_APP::upload()->data())
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            foreach (AWS_APP::config()->get('image')->feature_thumbnail as $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $feature_id . "_" . $val['w'] . "_" . $val['h'] . '.jpg';

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            unlink($upload_data['full_path']);

            $update_data['icon'] = basename($thumb_file['min']);
        }

        $this->model('feature')->update_feature($feature_id, $update_data);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/feature/list/')
        ), 1, null));
    }

    public function remove_feature_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('feature')->delete_feature($_POST['feature_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_feature_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['feature_ids'])
        {
            foreach ($_POST['feature_ids'] AS $feature_id => $val)
            {
                $this->model('feature')->update_feature_enabled($feature_id, $_POST['enabled_status'][$feature_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function save_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['nav_sort'])
        {
            if ($menu_ids = explode(',', $_POST['nav_sort']))
            {
                foreach($menu_ids as $key => $val)
                {
                    $this->model('menu')->update_nav_menu($val, array(
                        'sort' => $key
                    ));
                }
            }
        }

        if ($_POST['nav_menu'])
        {
            foreach($_POST['nav_menu'] as $key => $val)
            {
                $this->model('menu')->update_nav_menu($key, $val);
            }
        }

        $settings_var['category_display_mode'] = $_POST['category_display_mode'];
        $settings_var['nav_menu_show_child'] = isset($_POST['nav_menu_show_child']) ? 'Y' : 'N';

        $this->model('setting')->set_vars($settings_var);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
    }

    public function add_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        switch ($_POST['type'])
        {
            case 'category' :
                $type_id = intval($_POST['type_id']);
                $category = $this->model('system')->get_category_info($type_id);
                $title = $category['title'];
            break;

            case 'custom' :
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $link = trim($_POST['link']);
                $type_id = 0;
            break;
        }

        if (!$title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入导航标题')));
        }

        $this->model('menu')->add_nav_menu($title, $description, $_POST['type'], $type_id, $link);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('menu')->remove_nav_menu($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function nav_menu_upload_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg,jpeg,png,gif',
            'upload_path' => get_setting('upload_dir') . '/nav_menu',
            'is_image' => TRUE,
            'file_name' => intval($_GET['id']) . '.jpg',
            'encrypt_name' => FALSE
        ))->do_upload('attach');

        if (AWS_APP::upload()->get_error())
        {
            switch (AWS_APP::upload()->get_error())
            {
                default:
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                break;

                case 'upload_invalid_filetype':
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
                break;
            }
        }

        if (! $upload_data = AWS_APP::upload()->data())
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
        }

        if ($upload_data['is_image'] == 1)
        {
            AWS_APP::image()->initialize(array(
                'quality' => 90,
                'source_image' => $upload_data['full_path'],
                'new_image' => $upload_data['full_path'],
                'width' => 50,
                'height' => 50
            ))->resize();
        }

        $this->model('menu')->update_nav_menu($_GET['id'], array('icon' => basename($upload_data['full_path'])));

        H::ajax_json_output(AWS_APP::RSM(array(
            'preview' => get_setting('upload_url') . '/nav_menu/' . basename($upload_data['full_path'])
        ), 1, null));
    }

    public function add_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的页面 URL')));
        }

        $this->model('page')->add_page($_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function remove_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('page')->remove_page($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edit_page_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$page_info = $this->model('page')->get_page_by_url_id($_POST['page_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面不存在')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($_page_info = $this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            if ($_page_info['id'] != $_page_info['id'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的页面 URL')));
            }
        }

        $this->model('page')->update_page($_POST['page_id'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function save_page_status_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['page_ids'])
        {
            foreach ($_POST['page_ids'] AS $page_id => $val)
            {
                $this->model('page')->update_page_enabled($page_id, $_POST['enabled_status'][$page_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('启用状态已自动保存')));
    }

    public function question_manage_action()
    {
        if (!$_POST['question_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'remove':
                foreach ($_POST['question_ids'] AS $question_id)
                {
                    $this->model('question')->remove_question($question_id);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function report_manage_action()
    {
        if (! $_POST['report_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择内容进行操作')));
        }

        if (! $_POST['action_type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择操作类型')));
        }

        if ($_POST['action_type'] == 'delete')
        {
            foreach ($_POST['report_ids'] as $val)
            {
                $this->model('question')->delete_report($val);
            }
        }
        else if ($_POST['action_type'] == 'handle')
        {
            foreach ($_POST['report_ids'] as $val)
            {
                $this->model('question')->update_report($val, array(
                    'status' => 1
                ));
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function lock_topic_action()
    {
        $this->model('topic')->lock_topic_by_ids($_POST['topic_id'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_topic_action()
    {
        if (! $topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
        }

        if ($topic_info['topic_title'] != $_POST['topic_title'] AND $this->model('topic')->get_topic_by_title($_POST['topic_title']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('同名话题已经存在')));
        }

        $this->model('topic')->update_topic($this->user_id, $_POST['topic_id'], $_POST['topic_title'], $_POST['topic_description']);

        $this->model('topic')->set_is_parent($_POST['topic_id'], $_POST['is_parent']);

        $this->model('topic')->lock_topic_by_ids($_POST['topic_id'], $_POST['topic_lock']);

        $referer_url = (!$_POST['referer_url']) ? get_js_url('/admin/topic/list/') : $_POST['referer_url'];

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => $referer_url
        ), 1, null));
    }

    public function topic_manage_action()
    {
        if (!$_POST['topic_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(nul, -1, AWS_APP::lang()->_t('请选择话题进行操作')));
        }

        switch($_POST['action_type'])
        {
            case 'remove' :
                $this->model('topic')->remove_topic_by_ids($_POST['topic_ids']);
            break;

            case 'lock' :
                $this->model('topic')->lock_topic_by_ids($_POST['topic_ids'], 1);
            break;
        }


        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_user_group_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (empty($val['group_name']))
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入用户组名称')));
                }

                if ($val['reputation_factor'])
                {
                    if (!is_numeric($val['reputation_factor']) || floatval($val['reputation_factor']) < 0)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('威望系数必须为大于或等于 0')));
                    }

                    if (!is_numeric($val['reputation_lower']) || floatval($val['reputation_lower']) < 0 || !is_numeric($val['reputation_higer']) || floatval($val['reputation_higer']) < 0)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('威望介于值必须为大于或等于 0')));
                    }

                    $val['reputation_factor'] = floatval($val['reputation_factor']);
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 1, $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1 OR $group_info['type'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_custom_user_group_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (empty($val['group_name']))
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入用户组名称')));
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 0);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        if ($group_new OR $group_ids)
        {
            $rsm = array(
                'url' => get_js_url('/admin/user/group_list/r-' . rand(1, 999) . '#custom')
            );
        }

        H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
    }

    public function edit_user_group_permission_action()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $permission_array = array(
            'is_administortar',
            'is_moderator',
            'publish_question',
            'publish_approval',
            'publish_approval_time',
            'edit_question',
            'edit_topic',
            'manage_topic',
            'create_topic',
            'redirect_question',
            'upload_attach',
            'publish_url',
            'human_valid',
            'question_valid_hour',
            'answer_valid_hour',
            'visit_site',
            'visit_explore',
            'search_avail',
            'visit_question',
            'visit_topic',
            'visit_feature',
            'visit_people',
            'answer_show',
            'function_interval',
            'publish_article',
            'edit_article',
            'edit_question_topic',
            'publish_comment'
        );

        $group_setting = array();

        foreach ($permission_array as $permission)
        {
            if ($_POST[$permission])
            {
                $group_setting[$permission] = $_POST[$permission];
            }
        }

        $this->model('account')->update_user_group_data($_POST['group_id'], array(
            'permission' => serialize($group_setting)
        ));

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户组权限已更新')));
    }

    public function save_user_action()
    {
        if ($_POST['uid'])
        {
            if (!$user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
            }

            if ($_POST['user_name'] != $user_info['user_name'] AND $this->model('account')->get_user_info_by_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户名已存在')));
            }

            if ($_POST['email'] != $user_info['email'] AND $this->model('account')->get_user_info_by_username($_POST['email']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('E-mail 已存在')));
            }

            if ($_FILES['user_avatar']['name'])
            {
                AWS_APP::upload()->initialize(array(
                    'allowed_types' => 'jpg,jpeg,png,gif',
                    'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], '', 1),
                    'is_image' => TRUE,
                    'max_size' => get_setting('upload_avatar_size_limit'),
                    'file_name' => $this->model('account')->get_avatar($user_info['uid'], '', 2),
                    'encrypt_name' => FALSE
                ))->do_upload('user_avatar');

                if (AWS_APP::upload()->get_error())
                {
                    switch (AWS_APP::upload()->get_error())
                    {
                        default:
                            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                        break;

                        case 'upload_invalid_filetype':
                            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
                        break;

                        case 'upload_invalid_filesize':
                            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
                        break;
                    }
                }

                if (! $upload_data = AWS_APP::upload()->data())
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
                }

                if ($upload_data['is_image'] == 1)
                {
                    foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
                    {
                        $thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($user_info['uid'], $key, 2);

                        AWS_APP::image()->initialize(array(
                            'quality' => 90,
                            'source_image' => $upload_data['full_path'],
                            'new_image' => $thumb_file[$key],
                            'width' => $val['w'],
                            'height' => $val['h']
                        ))->resize();
                    }
                }

                $update_data['avatar_file'] = $this->model('account')->get_avatar($user_info['uid'], null, 1) . basename($thumb_file['min']);
            }

            if ($_POST['email'])
            {
                $update_data['email'] = htmlspecialchars($_POST['email']);
            }

            if ($_POST['invitation_available'])
            {
                $update_data['invitation_available'] = intval($_POST['invitation_available']);
            }

            $update_data['verified'] = $_POST['verified'];
            $update_data['valid_email'] = intval($_POST['valid_email']);
            $update_data['forbidden'] = intval($_POST['forbidden']);

            $update_data['group_id'] = intval($_POST['group_id']);

            if ($update_data['group_id'] == 1 AND !$this->user_info['permission']['is_administortar'])
            {
                unset($update_data['group_id']);
            }

            $update_data['province'] = htmlspecialchars($_POST['province']);
            $update_data['city'] = htmlspecialchars($_POST['city']);

            $update_data['job_id'] = intval($_POST['job_id']);
            $update_data['mobile'] = htmlspecialchars($_POST['mobile']);

            $update_data['sex'] = intval($_POST['sex']);

            $this->model('account')->update_users_fields($update_data, $user_info['uid']);

            if ($_POST['delete_avatar'])
            {
                $this->model('account')->delete_avatar($user_info['uid']);
            }

            if ($_POST['password'])
            {
                $this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_info['uid'], fetch_salt(4));
            }

            $this->model('account')->update_users_attrib_fields(array(
                'signature' => htmlspecialchars($_POST['signature']),
                'qq' => htmlspecialchars($_POST['qq']),
                'homepage' => htmlspecialchars($_POST['homepage'])
            ), $user_info['uid']);

            if ($_POST['user_name'] != $user_info['user_name'])
            {
                $this->model('account')->update_user_name($_POST['user_name'], $user_info['uid']);
            }

            H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户资料更新成功')));
        }
        else
        {
            $_POST['user_name'] = trim($_POST['user_name']);

            $_POST['email'] = trim($_POST['email']);

            $_POST['password'] = trim($_POST['password']);

            $_POST['group_id'] = intval($_POST['group_id']);

            if (!$_POST['user_name'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
            }

            if ($this->model('account')->check_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
            }

            if ($this->model('account')->check_email($_POST['email']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
            }

            if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
            }

            $uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

            $this->model('active')->set_user_email_valid_by_uid($uid);

            $this->model('active')->active_user_by_uid($uid);

            if ($_POST['group_id'] == 1 AND !$this->user_info['permission']['is_administortar'])
            {
                $_POST['group_id'] = 4;
            }

            if ($_POST['group_id'] != 4)
            {
                $this->model('account')->update('users', array(
                    'group_id' => $_POST['group_id'],
                ), 'uid = ' . $uid);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/list/')
            ), 1, null));
        }
    }

    public function forbidden_user_action()
    {
        $this->model('account')->forbidden_user_by_uid($_POST['uid'], $_POST['status'], $this->user_id);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function send_invites_action()
    {
        if ($_POST['email_list'])
        {
            if ($emails = explode("\n", str_replace("\r", "\n", $_POST['email_list'])))
            {
                foreach($emails as $key => $email)
                {
                    if (!H::valid_email($email))
                    {
                        continue;
                    }

                    $email_list[] = strtolower($email);
                }
            }
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入邮箱地址')));
        }

        $this->model('invitation')->send_batch_invitations(array_unique($email_list), $this->user_id, $this->user_info['user_name']);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请已发送')));
    }

    public function remove_job_action()
    {
        $this->model('work')->remove_job($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function add_job_action()
    {
        if (!$_POST['jobs'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入职位名称')));
        }

        $job_list = array();

        if ($job_list_tmp = explode("\n", $_POST['jobs']))
        {
            foreach($job_list_tmp as $key => $job)
            {
                $job_name = trim(strtolower($job));

                if (!empty($job_name))
                {
                    $job_list[] = $job_name;
                }
            }
        }
        else
        {
            $job_list[] = $_POST['jobs'];
        }

        foreach($job_list as $key => $val)
        {
            $this->model('work')->add_job($val);
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_job_action()
    {
        if ($_POST['job_list'])
        {
            foreach($_POST['job_list'] as $key => $val)
            {
                $this->model('work')->update_job($key, array(
                    'job_name' => $val,
                ));
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('职位列表更新成功')));
    }

    public function integral_process_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户进行操作')));
        }

        if (!$_POST['note'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写理由')));
        }

        $this->model('integral')->process($_POST['uid'], 'AWARD', $_POST['integral'], $_POST['note']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/integral_log/uid-' . $_POST['uid'])
        ), 1, null));
    }

    public function register_approval_manage_action()
    {
        if (!is_array($_POST['approval_uids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    $this->model('active')->active_user_by_uid($approval_uid);;
                }
            break;

            case 'decline':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    if ($user_info = $this->model('account')->get_user_info_by_uid($approval_uid))
                    {
                        if ($user_info['email'])
                        {
                            $this->model('email')->action_email('REGISTER_DECLINE', $user_info['email'], null, array(
                                'message' => htmlspecialchars($_POST['reason'])
                            ));
                        }

                        $this->model('system')->remove_user_by_uid($approval_uid, true);
                    }
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_verify_approval_action()
    {
        if ($_POST['uid'])
        {
            $this->model('verify')->update_apply($_POST['uid'], $_POST['name'], $_POST['reason'], array(
                'id_code' => htmlspecialchars($_POST['id_code']),
                'contact' => htmlspecialchars($_POST['contact'])
            ));
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/verify_approval_list/')
        ), 1, null));
    }

    public function verify_approval_manage_action()
    {
        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
            case 'decline':
                $func = $_POST['batch_type'] . '_verify';

                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('verify')->$func($approval_id);
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_user_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        @set_time_limit(0);

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (empty($user_info))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不存在')));
        }
        else
        {
            if ($user_info['group_id'] == 1)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不允许删除管理员用户组用户')));
            }

            $this->model('system')->remove_user_by_uid($_POST['uid'], $_POST['remove_user_data']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/list/')
        ), 1, null));
    }

    public function remove_users_action()
    {
        if (!is_array($_POST['uids']) OR !$_POST['uids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的用户')));
        }

        @set_time_limit(0);

        foreach ($_POST['uids'] AS $uid)
        {
            $user_info = $this->model('account')->get_user_info_by_uid($uid);

            if (empty($user_info))
            {
                continue;
            }
            else
            {
                if ($user_info['group_id'] == 1)
                {
                    continue;
                }

                $this->model('system')->remove_user_by_uid($uid, true);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function weixin_save_reply_rule_status_action()
    {
        if ($_POST['rule_ids'])
        {
            foreach ($_POST['rule_ids'] AS $rule_id => $val)
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
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function weixin_remove_reply_rule_action()
    {
        $this->model('weixin')->remove_reply_rule($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function weixin_mp_menu_list_image_upload_action()
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
            AWS_APP::upload()->do_upload('list_image');
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

    public function save_weixin_account_action()
    {
        if (!$_POST['type'] OR $_POST['type'] == 'update' AND !$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        if (!$_POST['weixin_mp_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信公众平台接口 Token 不能为空')));
        }

        $account_info = array(
                            'weixin_mp_token' => trim($_POST['weixin_mp_token']),
                            'weixin_account_role' => $_POST['weixin_account_role'],
                            'weixin_app_id' => $_POST['weixin_app_id'],
                            'weixin_app_secret' => $_POST['weixin_app_secret'],
                            'wecenter_access_token' => $_POST['wecenter_access_token'],
                            'wecenter_access_secret' => $_POST['wecenter_access_secret']
                        );

        switch ($_POST['type'])
        {
            case 'add':
                $account_id = $this->model('weixin')->insert('weixin_accounts', $account_info);

                H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/admin/weixin/account/id-' . $account_id)), 1, null));

                break;

            case 'update':
                $this->model('weixin')->update_setting_or_account($_POST['id'], $account_info);

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));

                break;
        }
    }

    public function remove_weixin_account_action()
    {
        $this->model('weixin')->remove_weixin_account($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function weixin_send_msg_action()
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

        if (!$_POST['main_msg_context'])
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

        $article_ids = array_unique(array_filter(explode(',', $_POST['article_ids'])));

        $question_ids = array_unique(array_filter(explode(',', $_POST['question_ids'])));

        if (empty($article_ids) AND empty($question_ids))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请添加要群发的文章或问题 id')));
        }

        $total = count($article_ids) + count($question_ids);

        if ($total > 9)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('最多可添加 9 个文章和问题')));
        }

        if ($_FILES['main_msg_img']['error'] === UPLOAD_ERR_OK)
        {
            if ($_FILES['main_msg_img']['type'] != 'image/jpeg')
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('只允许上传 jpeg 格式的图片')));
            }

            if ($_FILES['main_msg_img']['size'] > '262144')
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('图片最大为 256KB')));
            }

            if (is_uploaded_file($_FILES['main_msg_img']['tmp_name']))
            {
                $main_msg_img = $_FILES['main_msg_img']['tmp_name'];
            }
            else
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
            'context' => $_POST['main_msg_context'],
            'img' => $main_msg_img,
            'show_cover_pic' => $_POST['show_cover_pic']
        );

        $error_msg = $this->model('weixin')->add_main_msg_to_mpnews($main_msg);

        if (isset($error_msg))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传封面图失败, 错误信息: %s', $error_msg)));
        }

        if (!empty($article_ids))
        {
            $error_msg = $this->model('weixin')->add_articles_to_mpnews($article_ids);

            if (isset($error_msg))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传文章作者的头像失败, 错误信息: %s', $error_msg)));
            }
        }

        if (!empty($question_ids))
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

        H::ajax_json_output(AWS_APP::RSM(array('url' => get_js_url('/admin/weixin/sent_msg_details/id-' . $msg_id)), 1, null));
    }

    public function statistic_action()
    {
        if (!$start_time = strtotime($_GET['start_date']))
        {
            $start_time = strtotime('-12 months');
        }

        if (!$end_time = strtotime($_GET['end_date']))
        {
            $end_time = time();
        }

        if ($_GET['tag'])
        {
            $statistic_tag = explode(',', $_GET['tag']);
        }

        if (!$month_list = get_month_list($start_time, $end_time, 'y'))
        {
            die;
        }

        foreach ($month_list AS $key => $val)
        {
            $labels[] = $val['year'] . '-' . $val['month'];
            $data_template[] = 0;
        }

        if (!$statistic_tag)
        {
            die;
        }

        foreach ($statistic_tag AS $key => $val)
        {
            switch ($val)
            {
                case 'new_answer':  // 新增答案
                case 'new_question':    // 新增问题
                case 'new_user':    // 新注册用户
                case 'user_valid':  // 新激活用户
                case 'new_topic':   // 新增话题
                case 'new_answer_vote': // 新增答案投票
                case 'new_answer_thanks': // 新增答案感谢
                case 'new_favorite_item': // 新增收藏条目
                case 'new_question_thanks': // 新增问题感谢
                case 'new_question_redirect': // 新增问题重定向
                    $statistic[] = $this->model('system')->statistic($val, $start_time, $end_time);
                break;
            }
        }

        foreach($statistic AS $key => $val)
        {
            $statistic_data = $data_template;

            foreach ($val AS $k => $v)
            {
                $data_key = array_search($v['date'], $labels);

                $statistic_data[$data_key] = $v['count'];
            }

            $data[] = $statistic_data;

        }

        echo json_encode(array(
            'labels' => $labels,
            'data' => $data
        ));
    }

    public function weibo_batch_action()
    {
        if (!$_POST['action'] OR !$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        if ($_POST['action'] == 'add_service_user' OR $_POST['action'] == 'del_service_user')
        {
            $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

            if (empty($user_info))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
            }

            $service_info = $this->model('openid_weibo')->get_users_sina_by_uid($user_info['uid']);

            $tmp_service_account = AWS_APP::cache()->get('tmp_service_account');
        }

        switch ($_POST['action'])
        {
            case 'add_service_user':
                if ($service_info)
                {
                    if (isset($service_info['last_msg_id']))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已是回答用户')));
                    }

                    $this->model('weibo')->update_service_account($user_info['uid'], 'add');
                }
                else
                {
                    if ($tmp_service_account[$user_info['uid']])
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已是回答用户')));
                    }

                    $tmp_service_account[$user_info['uid']] = array(
                                                                    'uid' => $user_info['uid'],
                                                                    'user_name' => $user_info['user_name'],
                                                                    'url_token' => $user_info['url_token']
                                                                );

                    natsort($tmp_service_account);

                    AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);
                }

                break;

            case 'del_service_user':
                if ($service_info)
                {
                    if (!isset($service_info['last_msg_id']))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不是回答用户')));
                    }

                    $this->model('weibo')->update_service_account($user_info['uid'], 'del');
                }
                else
                {
                    if (!$tmp_service_account[$user_info['uid']])
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户不是回答用户')));
                    }

                    unset($tmp_service_account[$user_info['uid']]);

                    AWS_APP::cache()->set('tmp_service_account', $tmp_service_account, 86400);
                }

                break;

            case 'add_published_user':
                $weibo_msg_published_user = get_setting('weibo_msg_published_user');

                if ($_POST['uid'] != $weibo_msg_published_user['uid'])
                {
                    $published_user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

                    $this->model('setting')->set_vars(array(
                        'weibo_msg_published_user' => array(
                            'uid' => $published_user_info['uid'],
                            'user_name' => $published_user_info['user_name'],
                            'url_token' => $published_user_info['url_token']
                    )));
                }

                break;

            case 'weibo_msg_enabled':
                if ($_POST['uid'] == 1)
                {
                    $weibo_msg_enabled = 'Y';
                }
                else
                {
                    $weibo_msg_enabled = 'N';
                }

                $this->model('setting')->set_vars(array(
                    'weibo_msg_enabled' => $weibo_msg_enabled
                ));

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function create_weixin_qr_code_action()
    {
        if (!$_POST['description'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('描述不能为空')));
        }

        $scene_id = $this->model('weixin')->insert('weixin_qr_code', array('description' => $_POST['description']));

        $result = $this->model('weixin')->create_qr_code($scene_id);

        if ($result)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_weixin_qr_code_action()
    {
        if (!$_POST['scene_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('ID 不能为空')));
        }

        $this->delete('weixin_qr_code', 'scene_id = ' . intval($$_POST['scene_id']));

        unlink(get_setting('upload_dir') . '/weixin_qr_code/' . $_POST['scene_id'] . '.jpg');

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_approval_item_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('ID 不能为空')));
        }

        if (!$_POST['type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('类型不能为空')));
        }

        if ($_POST['type'] == 'weibo_msg')
        {
            $approval_item = $this->model('weibo')->get_msg_info_by_id($_POST['id']);

            if ($approval_item['question_id'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该消息已通过审核')));
            }

            $approval_item['type'] = 'weibo_msg';
        }
        else
        {
            $approval_item = $this->model('publish')->get_approval_item($_POST['id']);
        }

        if (empty($approval_item))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('待审项不存在')));
        }

        if ($_POST['type'] != $approval_item['type'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('类型不正确')));
        }

        if (!$_POST['title'] AND ($_POST['type'] == 'question' OR $_POST['type'] == 'article'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('标题不能为空')));
        }

        if (!$_POST['content'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不能为空')));
        }

        switch ($approval_item['type'])
        {
            case 'question':
                $approval_item['data']['question_content'] = htmlspecialchars_decode($_POST['title']);

                $approval_item['data']['question_detail'] = htmlspecialchars_decode($_POST['content']);

                $approval_item['data']['topics'] = explode(',', htmlspecialchars_decode($_POST['topics']));

                break;

            case 'answer':
                $approval_item['data']['answer_content'] = htmlspecialchars_decode($_POST['content']);

                break;

            case 'article':
                $approval_item['data']['title'] = htmlspecialchars_decode($_POST['title']);

                $approval_item['data']['message'] = htmlspecialchars_decode($_POST['content']);

                break;

            case 'article_comment':
                $approval_item['data']['message'] = htmlspecialchars_decode($_POST['content']);

                break;
        }

        if ($approval_item['type'] != 'article_comment' AND $_POST['remove_attachs'])
        {
            foreach ($_POST['remove_attachs'] AS $attach_id)
            {
                $this->model('publish')->remove_attach($attach_id, $approval_item['data']['attach_access_key']);
            }
        }

        if ($approval_item['type'] == 'weibo_msg')
        {
            $this->model('weibo')->update('weibo_msg', array('text' => $_POST['content']), 'id = ' . $approval_item['id']);
        }
        else
        {
            $this->model('publish')->update('approval', array('data' => serialize($approval_item['data'])), 'id = ' . $approval_item['id']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/approval/list/')
        ), 1, null));
    }

    public function save_today_topics()
    {
        $today_topics = trim($_POST['today_topics']);

        $this->model('setting')->set_vars(array('today_topics' => $today_topics));

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('修改成功')));
    }
}
