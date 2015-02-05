<?php
$config[] = array(
    'title' => AWS_APP::lang()->_t('概述'),
    'cname' => 'home',
    'url' => 'admin/',
    'children' => array()
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('全局设置'),
    'cname' => 'setting',
    'children' => array(
        array(
            'id' => 'SETTINGS_SITE',
            'title' => AWS_APP::lang()->_t('站点信息'),
            'url' => 'admin/settings/category-site'
        ),

        array(
            'id' => 'SETTINGS_REGISTER',
            'title' => AWS_APP::lang()->_t('注册访问'),
            'url' => 'admin/settings/category-register'
        ),

        array(
            'id' => 'SETTINGS_FUNCTIONS',
            'title' => AWS_APP::lang()->_t('站点功能'),
            'url' => 'admin/settings/category-functions'
        ),

        array(
            'id' => 'SETTINGS_CONTENTS',
            'title' => AWS_APP::lang()->_t('内容设置'),
            'url' => 'admin/settings/category-contents'
        ),

        array(
            'id' => 'SETTINGS_INTEGRAL',
            'title' => AWS_APP::lang()->_t('威望积分'),
            'url' => 'admin/settings/category-integral'
        ),

        array(
            'id' => 'SETTINGS_PERMISSIONS',
            'title' => AWS_APP::lang()->_t('用户权限'),
            'url' => 'admin/settings/category-permissions'
        ),

        array(
            'id' => 'SETTINGS_MAIL',
            'title' => AWS_APP::lang()->_t('邮件设置'),
            'url' => 'admin/settings/category-mail'
        ),

        array(
            'id' => 'SETTINGS_OPENID',
            'title' => AWS_APP::lang()->_t('开放平台'),
            'url' => 'admin/settings/category-openid'
        ),

        array(
            'id' => 'SETTINGS_CACHE',
            'title' => AWS_APP::lang()->_t('性能优化'),
            'url' => 'admin/settings/category-cache'
        ),

        array(
            'id' => 'SETTINGS_INTERFACE',
            'title' => AWS_APP::lang()->_t('界面设置'),
            'url' => 'admin/settings/category-interface'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('内容管理'),
    'cname' => 'reply',
    'children' => array(
        array(
            'id' => 301,
            'title' => AWS_APP::lang()->_t('问题管理'),
            'url' => 'admin/question/question_list/'
        ),

        array(
            'id' => 309,
            'title' => AWS_APP::lang()->_t('文章管理'),
            'url' => 'admin/article/list/'
        ),

        array(
            'id' => 303,
            'title' => AWS_APP::lang()->_t('话题管理'),
            'url' => 'admin/topic/list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('用户管理'),
    'cname' => 'user',
    'children' => array(
        array(
            'id' => 402,
            'title' => AWS_APP::lang()->_t('用户列表'),
            'url' => 'admin/user/list/'
        ),

        array(
            'id' => 403,
            'title' => AWS_APP::lang()->_t('用户组'),
            'url' => 'admin/user/group_list/'
        ),

        array(
            'id' => 406,
            'title' => AWS_APP::lang()->_t('批量邀请'),
            'url' => 'admin/user/invites/'
        ),

        array(
            'id' => 407,
            'title' => AWS_APP::lang()->_t('职位设置'),
            'url' => 'admin/user/job_list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('审核管理'),
    'cname' => 'report',
    'children' => array(
        array(
            'id' => 300,
            'title' => AWS_APP::lang()->_t('内容审核'),
            'url' => 'admin/approval/list/'
        ),

        array(
            'id' => 401,
            'title' => AWS_APP::lang()->_t('认证审核'),
            'url' => 'admin/user/verify_approval_list/'
        ),

        array(
            'id' => 408,
            'title' => AWS_APP::lang()->_t('注册审核'),
            'url' => 'admin/user/register_approval_list/'
        ),

        array(
            'id' => 306,
            'title' => AWS_APP::lang()->_t('用户举报'),
            'url' => 'admin/question/report_list/'
        )
    )
);

if (check_extension_package('project'))
{
    $config[] = array(
        'title' => '活动管理',
        'cname' => 'reply',
        'children' => array(
            array(
                'id' => 310,
                'title' => '活动管理',
                'url' => 'admin/project/project_list/'
            ),

            array(
                'id' => 311,
                'title' => '活动审核',
                'url' => 'admin/project/approval_list/'
            ),

            array(
                'id' => 312,
                'title' => '订单管理',
                'url' => 'admin/project/order_list/'
            )
        )
    );
}

$config[] = array(
    'title' => AWS_APP::lang()->_t('内容设置'),
    'cname' => 'signup',
    'children' => array(
        array(
            'id' => 307,
            'title' => AWS_APP::lang()->_t('导航设置'),
            'url' => 'admin/nav_menu/'
        ),

        array(
            'id' => 302,
            'title' => AWS_APP::lang()->_t('分类管理'),
            'url' => 'admin/category/list/'
        ),

        array(
            'id' => 304,
            'title' => AWS_APP::lang()->_t('专题管理'),
            'url' => 'admin/feature/list/'
        ),

        array(
            'id' => 308,
            'title' => AWS_APP::lang()->_t('页面管理'),
            'url' => 'admin/page/'
        ),

        array(
            'id' => 305,
            'title' => AWS_APP::lang()->_t('帮助中心'),
            'url' => 'admin/help/list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('微信微博'),
    'cname' => 'share',
    'children' => array(
        array(
            'id' => 802,
            'title' => AWS_APP::lang()->_t('微信多账号管理'),
            'url' => 'admin/weixin/accounts/'
        ),

        array(
            'id' => 803,
            'title' => AWS_APP::lang()->_t('微信菜单管理'),
            'url' => 'admin/weixin/mp_menu/'
        ),

        array(
            'id' => 801,
            'title' => AWS_APP::lang()->_t('微信自定义回复'),
            'url' => 'admin/weixin/reply/'
        ),

        array(
            'id' => 808,
            'title' => AWS_APP::lang()->_t('微信第三方接入'),
            'url' => 'admin/weixin/third_party_access/'
        ),

        array(
            'id' => 805,
            'title' => AWS_APP::lang()->_t('微信二维码管理'),
            'url' => 'admin/weixin/qr_code/'
        ),

        array(
            'id' => 804,
            'title' => AWS_APP::lang()->_t('微信消息群发'),
            'url' => 'admin/weixin/sent_msgs_list/'
        ),

        array(
            'id' => 806,
            'title' => AWS_APP::lang()->_t('微博消息接收'),
            'url' => 'admin/weibo/msg/'
        ),

        array(
            'id' => 807,
            'title' => AWS_APP::lang()->_t('邮件导入'),
            'url' => 'admin/edm/receiving_list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('邮件群发'),
    'cname' => 'inbox',
    'children' => array(
        array(
            'id' => 702,
            'title' => AWS_APP::lang()->_t('任务管理'),
            'url' => 'admin/edm/tasks/'
        ),

        array(
            'id' => 701,
            'title' => AWS_APP::lang()->_t('用户群管理'),
            'url' => 'admin/edm/groups/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('工具'),
    'cname' => 'job',
    'children' => array(
        array(
            'id' => 501,
            'title' => AWS_APP::lang()->_t('系统维护'),
            'url' => 'admin/tools/',
        )
    )
);

if (file_exists(AWS_PATH . 'config/admin_menu.extension.php'))
{
    include_once(AWS_PATH . 'config/admin_menu.extension.php');
}
