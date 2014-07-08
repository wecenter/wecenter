<?php

$config[] = array(
	'title' => '全局',
	'cname' => 'briefcase',
	'children' => array(
		array(
			'id' => 100,
			'title' => '概述',
			'url' => 'admin/',
		),

		array(
			'id' => 'SETTINGS_SITE',
			'title' => '系统设置',
			'url' => 'admin/settings/category-site',
		),
		array(
			'id' => 'SETTINGS_REGISTER',
			'title' => '注册与访问',
			'url' => 'admin/settings/category-register',
		),
		array(
			'id' => 'SETTINGS_FUNCTIONS',
			'title' => '站点功能',
			'url' => 'admin/settings/category-functions',
		),
		array(
			'id' => 'SETTINGS_CONTENTS',
			'title' => '内容设置',
			'url' => 'admin/settings/category-contents',
		),
		array(
			'id' => 'SETTINGS_INTEGRAL',
			'title' => '积分与威望',
			'url' => 'admin/settings/category-integral',
		),
		array(
			'id' => 'SETTINGS_PERMISSIONS',
			'title' => '用户权限',
			'url' => 'admin/settings/category-permissions',
		),
		array(
			'id' => 'SETTINGS_MAIL',
			'title' => '邮件设置',
			'url' => 'admin/settings/category-mail',
		),
		array(
			'id' => 'SETTINGS_OPENID',
			'title' => '开放平台',
			'url' => 'admin/settings/category-openid',
		),

		array(
			'id' => 'SETTINGS_CACHE',
			'title' => '性能优化',
			'url' => 'admin/settings/category-cache',
		),

		array(
			'id' => 'SETTINGS_INTERFACE',
			'title' => '界面设置',
			'url' => 'admin/settings/category-interface',
		),
	)
);

$config[] = array(
	'title' => '内容',
	'cname' => 'bookmark',
	'children' => array(
		array(
			'id' => 307,
			'title' => '导航设置',
			'url' => 'admin/nav_menu/',
		),

		array(
			'id' => 300,
			'title' => '内容审核',
			'url' => 'admin/approval/list/',
		),

		array(
			'id' => 301,
			'title' => '问题管理',
			'url' => 'admin/question/question_list/',
		),

		array(
			'id' => 309,
			'title' => '文章管理',
			'url' => 'admin/article/list/',
		),

		array(
			'id' => 302,
			'title' => '分类设置',
			'url' => 'admin/category/list/',
		),
		array(
			'id' => 303,
			'title' => '话题管理',
			'url' => 'admin/topic/list/',
		),
		array(
			'id' => 304,
			'title' => '专题管理',
			'url' => 'admin/feature/list/',
		),
		array(
			'id' => 308,
			'title' => '页面管理',
			'url' => 'admin/page/',
		),
		array(
			'id' => 306,
			'title' => '用户举报',
			'url' => 'admin/question/report_list/',
		),
	)
);

$config[] = array(
	'title' => '用户',
	'cname' => 'users',
	'children' => array(
		array(
			'id' => 408,
			'title' => '注册审核',
			'url' => 'admin/user/register_approval_list/',
		),
		array(
			'id' => 401,
			'title' => '认证审核',
			'url' => 'admin/user/verify_approval_list/',
		),
		array(
			'id' => 402,
			'title' => '会员列表',
			'url' => 'admin/user/list/',
		),
		array(
			'id' => 403,
			'title' => '用户组',
			'url' => 'admin/user/group_list/',
		),
		array(
			'id' => 406,
			'title' => '批量邀请',
			'url' => 'admin/user/invites/',
		),
		array(
			'id' => 407,
			'title' => '职位设置',
			'url' => 'admin/user/job_list/',
		)
	)
);

$config[] = array(
	'title' => '邮件群发',
	'cname' => 'envelope',
	'children' => array(
		array(
			'id' => 701,
			'title' => '用户群管理',
			'url' => 'admin/edm/groups/',
		),
		array(
			'id' => 702,
			'title' => '任务管理',
			'url' => 'admin/edm/tasks/',
		),
	)
);

if (get_setting('weixin_mp_token'))
{
	$config[] = array(
		'title' => '微信',
		'cname' => 'weixin',
		'children' => array(
			array(
				'id' => 802,
				'title' => '多账号管理',
				'url' => 'admin/weixin/accounts/'
			),

			array(
				'id' => 801,
				'title' => '自定义回复',
				'url' => 'admin/weixin/reply/'
			),

			array(
				'id' => 803,
				'title' => '菜单管理',
				'url' => 'admin/weixin/mp_menu/'
			),

			array(
				'id' => 804,
				'title' => '消息群发',
				'url' => 'admin/weixin/sent_msgs_list/'
			),

			array(
				'id' => 805,
				'title' => '二维码管理',
				'url' => 'admin/weixin/qr_code/'
			)
		)
	);
}

if (get_setting('sina_akey') AND get_setting('sina_skey'))
{
	$config[] = array(
		'title' => '微博',
		'cname' => 'weibo',
		'children' => array(
			array(
				'id' => 901,
				'title' => '消息接收',
				'url' => 'admin/weibo/msg/'
			)
		)
	);
}

$config[] = array(
	'title' => '工具',
	'cname' => 'wrench',
	'children' => array(
		array(
			'id' => 501,
			'title' => '系统维护',
			'url' => 'admin/tools/',
		),
	)
);
