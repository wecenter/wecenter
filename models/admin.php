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

class admin_class extends AWS_MODEL
{
    public function fetch_menu_list($select_id)
    {
        $admin_menu = (array)AWS_APP::config()->get('admin_menu');

        if (!$admin_menu)
        {
            return false;
        }

        foreach($admin_menu as $m_id => $menu)
        {
            if ($menu['children'])
            {
                foreach($menu['children'] as $c_id => $c_menu)
                {
                    if ($select_id == $c_menu['id'])
                    {
                        $admin_menu[$m_id]['children'][$c_id]['select'] = true;
                        $admin_menu[$m_id]['select'] = true;
                    }
                }
            }
        }

        return $admin_menu;
    }

    public function set_admin_login($uid)
    {
        AWS_APP::session()->admin_login = H::encode_hash(array(
            'uid' => $uid,
            'UA' => $_SERVER['HTTP_USER_AGENT'],
            'ip' => fetch_ip()
        ));
    }

    public function admin_logout()
    {
        if (isset(AWS_APP::session()->admin_login))
        {
            unset(AWS_APP::session()->admin_login);
        }
    }

    public function notifications_crond()
    {
        $last_version = json_decode(curl_get_contents('http://wenda.wecenter.com/api/version_check.php'), true);

        $admin_notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$admin_notifications)
        {
            $admin_notifications = get_setting('admin_notifications');
        }

        $admin_notifications = array(
                                // 内容审核
                                'answer_approval' => $this->count('approval', 'type = "answer"'),
                                'question_approval' => $this->count('approval', 'type = "question"'),
                                'article_approval' => $this->count('approval', 'type = "article"'),
                                'article_comment_approval' => $this->count('approval', 'type = "article_comment"'),
                                'unverified_modify_count' => $this->count('question', 'unverified_modify_count <> 0'),

                                // 用户举报
                                'user_report' => $this->count('report', 'status = 0'),

                                // 注册审核
                                'register_approval' => $this->count('users', 'group_id = 3'),

                                // 认证审核
                                'verify_approval' => $this->count('verify_apply', 'status = 0'),

                                // 程序更新
                                'last_version' => array(
                                                        'version' => $last_version['version'],
                                                        'build_day' => $last_version['build_day']
                                                    ),

                                // 新浪微博 Access Token 更新
                                'sina_users' => $admin_notifications['sina_users'],

                                // 邮件导入失败
                                'receive_email_error' => $admin_notifications['receive_email_error']
                            );

        if (get_setting('weibo_msg_enabled') == 'question')
        {
            $admin_notifications['weibo_msg_approval'] = $this->count('weibo_msg', 'question_id IS NULL AND ticket_id IS NULL');
        }

        $receiving_email_global_config = get_setting('receiving_email_global_config');

        if ($receiving_email_global_config['enabled'] == 'question')
        {
            $admin_notifications['received_email_approval'] = $this->count('received_email', 'question_id IS NULL AND ticket_id IS NUL');
        }

        AWS_APP::cache()->set('admin_notifications', $admin_notifications, 1800);

        return $this->model('setting')->set_vars(array('admin_notifications' => $admin_notifications));
    }

    public function get_notifications_texts()
    {
        $notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$notifications)
        {
            $notifications = get_setting('admin_notifications');
        }

        if (!$notifications)
        {
            return false;
        }

        if ($notifications['question_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/',
                                            'text' => AWS_APP::lang()->_t('有 %s 个问题待审核', $notifications['question_approval'])
                                        );
        }

        if ($notifications['unverified_modify_count'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-unverified_modify',
                                            'text' => AWS_APP::lang()->_t('有 %s 个问题修改待审核', $notifications['unverified_modify_count'])
                                        );
        }

        if ($notifications['answer_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-answer',
                                            'text' => AWS_APP::lang()->_t('有 %s 个回答待审核', $notifications['answer_approval'])
                                        );
        }

        if ($notifications['article_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-article',
                                            'text' => AWS_APP::lang()->_t('有 %s 篇文章待审核', $notifications['article_approval'])
                                        );
        }

        if ($notifications['article_comment_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-article_comment',
                                            'text' => AWS_APP::lang()->_t('有 %s 个文章评论待审核', $notifications['article_comment_approval'])
                                        );
        }

        if ($notifications['weibo_msg_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-weibo_msg',
                                            'text' => AWS_APP::lang()->_t('有 %s 个微博消息待审核', $notifications['weibo_msg_approval'])
                                        );
        }

        if ($notifications['received_email_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/approval/list/type-received_email',
                                            'text' => AWS_APP::lang()->_t('有 %s 个邮件咨询待审核', $notifications['received_email_approval'])
                                        );
        }

        if ($notifications['user_report'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/question/report_list/',
                                            'text' => AWS_APP::lang()->_t('有 %s 个用户举报待查看', $notifications['user_report'])
                                        );
        }

        if (get_setting('register_valid_type') == 'approval' AND $notifications['register_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/user/register_approval_list/',
                                            'text' => AWS_APP::lang()->_t('有 %s 个新用户待审核', $notifications['register_approval'])
                                        );
        }

        if ($notifications['verify_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/user/verify_approval_list/',
                                            'text' => AWS_APP::lang()->_t('有 %s 个认证申请待审核', $notifications['verify_approval'])
                                        );
        }

        if ($notifications['last_version']['build_day'] > G_VERSION_BUILD)
        {
            $notifications_texts[] = array(
                                            'url' => 'http://www.wecenter.com/downloads/',
                                            'text' => AWS_APP::lang()->_t('程序需要更新，最新版本为 %s', $notifications['last_version']['version'])
                                        );
        }

        if (get_setting('weibo_msg_enabled') == 'Y' AND $notifications['sina_users'])
        {
            foreach ($notifications['sina_users'] AS $sina_user)
            {
                $notifications_texts[] = array(
                                                'url' => 'admin/weibo/msg/',
                                                'text' => AWS_APP::lang()->_t('用户 %s 的微博账号需要更新 Access Token，请重新授权', $sina_user['user_name'])
                                            );
            }
        }

        $receiving_email_global_config = get_setting('receiving_email_global_config');

        if ($receiving_email_global_config['enabled'] == 'Y' AND $notifications['receive_email_error'])
        {
            foreach ($notifications['receive_email_error'] AS $error_msg)
            {
                $notifications_texts[] = array(
                                                'url' => 'admin/edm/receiving/id-' . $error_msg['id'],
                                                'text' => AWS_APP::lang()->_t('邮件导入失败，错误为 %s，请重新配置', $error_msg['msg'])
                                            );
            }
        }

        return $notifications_texts;
    }
}
