<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   =======
=================================
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

class weibo extends AWS_ADMIN_CONTROLLER
{
    public function msg_action()
    {
        $this->crumb(AWS_APP::lang()->_t('消息接收'), 'admin/weibo/msg/');

        $services_info = $this->model('openid_weibo_weibo')->get_services_info();

        if ($services_info)
        {
            foreach ($services_info AS $service_info)
            {
                $service_uids[] = $service_info['uid'];
            }

            $service_users_info = $this->model('account')->get_user_info_by_uids($service_uids);

            TPL::assign('service_users_info', $service_users_info);
        }

        $tmp_service_users_info = AWS_APP::cache()->get('tmp_service_account');

        if ($tmp_service_users_info)
        {
            TPL::assign('tmp_service_users_info', $tmp_service_users_info);
        }

        TPL::assign('published_user', get_setting('weibo_msg_published_user'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(806));

        TPL::output('admin/weibo/msg');
    }
}
