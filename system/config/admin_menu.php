<?php

$config[] = array(
	'id' => 1,
	'title' => '全局',
	'cname' => 'system',
	'children' => array(
		array(
			'id' => 100,
			'title' => '管理首页',
			'url' => 'admin/',
		),
		
		array(
			'id' => 101,
			'title' => '系统设置',
			'url' => 'admin/settings/',
		),
		array(
			'id' => 102,
			'title' => '注册与访问',
			'url' => 'admin/settings/#!register_viste',
		),
		array(
			'id' => 103,
			'title' => '站点功能',
			'url' => 'admin/settings/#!functions',
		),
		array(
			'id' => 104,
			'title' => '内容设置',
			'url' => 'admin/settings/#!contents',
		),
		array(
			'id' => 106,
			'title' => '积分与威望',
			'url' => 'admin/settings/#!integral',
		),
		array(
			'id' => 111,
			'title' => '用户权限',
			'url' => 'admin/settings/#!permissions',
		),
		array(
			'id' => 107,
			'title' => '邮件设置',
			'url' => 'admin/settings/#!email',
		),
		array(
			'id' => 108,
			'title' => '开放平台',
			'url' => 'admin/settings/#!openid',
		),

		array(
			'id' => 109,
			'title' => '性能优化',
			'url' => 'admin/settings/#!cache',
		),
		
		array(
			'id' => 110,
			'title' => '界面设置',
			'url' => 'admin/settings/#!interface',
		),
		
		array(
			'id' => 111,
			'title' => '词语过滤',
			'url' => 'admin/settings/#!sensitive_words',
		),
		
		array(
			'id' => 112,
			'title' => '话题设置',
			'url' => 'admin/settings/#!topic_settings',
		),
	)
);

$config[] = array(
	'id' => 3,
	'title' => '内容',
	'cname' => 'contents',
	'children' => array(
		array(
			'id' => 306,
			'title' => '导航设置',
			'url' => 'admin/nav_menu/',
		),
		
		array(
			'id' => 300,
			'title' => '内容审核',
			'url' => 'admin/question/approval_list/',
		),
		
		array(
			'id' => 301,
			'title' => '问题列表',
			'url' => 'admin/question/question_list/',
		),
		array(
			'id' => 302,
			'title' => '分类设置',
			'url' => 'admin/category/list/',
		),
		array(
			'id' => 303,
			'title' => '话题列表',
			'url' => 'admin/topic/list/',
		),
		array(
			'id' => 304,
			'title' => '专题管理',
			'url' => 'admin/feature/list/',
		),
		array(
			'id' => 306,
			'title' => '用户举报',
			'url' => 'admin/question/report_list/',
		),
	)
);

$config[] = array(
	'id' => 4,
	'title' => '用户',
	'cname' => 'users',
	'children' => array(
		array(
			'id' => 401,
			'title' => '认证审核',
			'url' => 'admin/user_manage/verify_approval_list/',
		),
		array(
			'id' => 402,
			'title' => '会员列表',
			'url' => 'admin/user_manage/list/',
		),
		array(
			'id' => 403,
			'title' => '用户组',
			'url' => 'admin/user_manage/group_list/',
		),
		array(
			'id' => 405,
			'title' => '添加用户',
			'url' => 'admin/user_manage/user_add/',
		),
		array(
			'id' => 406,
			'title' => '批量邀请',
			'url' => 'admin/user_manage/invites/',
		),
		array(
			'id' => 407,
			'title' => '职位设置',
			'url' => 'admin/user_manage/job_list/',
		),
		array(
			'id' => 408,
			'title' => '查看禁封用户',
			'url' => 'admin/user_manage/forbidden_list/',
		)
	)
);


$config[] = array(
	'id' => 6,
	'title' => '运营',
	'cname' => 'maintain',
	'children' => array(
		array(
			'id' => 602,
			'title' => '数据统计',
			'url' => 'admin/statistic/',
		),
	)
);

$config[] = array(
	'id' => 7,
	'title' => '邮件群发',
	'cname' => 'edm',
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

$config[] = array(
	'id' => 5,
	'title' => '工具',
	'cname' => 'tools',
	'children' => array(
		array(
			'id' => 501,
			'title' => '系统维护',
			'url' => 'admin/tool/',
		),
	)
);
