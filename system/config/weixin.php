<?php

// 确认指令
$config['language_characteristic'] = array(
	// 确认
	'ok' => array(
		'好', '好的', '是', '是的', '恩', '可', '可以', '行', '行啊', '中', '要', '哦', '嗯', '确认', '确定', 'yes', '更多'
	),
	
	// 取消
	'cancel' => array(
		'不', '不要', '别', '算了', '取消', 'no', '否', "don't"
	),
	
	// 脏话
	'bad' => array(
		'fuck', 'shit', '狗屎', '婊子', '贱', '你妈', '你娘', '你祖宗', '滚', '你妹', '日', '操', '靠', '干'
	),
);

// 指令错误的提示信息
$config['help_message'] = "以下指令可以帮助您更好的利用微信公众号:\n\n绑定状态 - 查询微信绑定状态\n解除绑定 - 解除微信绑定\n我的问题 - 显示我的提问\n最新问题 - 显示最新提问\n最新通知 - 显示最新通知";

// 继续提问提示信息
$config['publish_message'] = "您的问题没有人提到过, 需要帮忙么? 回复 '是' 提交问题到社区";

// 提问完成提示信息
$config['publish_success_message'] = "您的问题已提交，晚点您可以输入 '我的问题' 查看";

// 脏话提示信息
$config['bad_language_message'] = "说脏话都不是好孩子!";

// 最新问题指令
$config['command_new'] = '最新问题';

// 最新通知指令
$config['command_notifications'] = '最新通知';

// 我的问题指令
$config['command_my'] = '我的问题';

// 绑定状态指令
$config['command_bind_info']= '绑定状态';

// 解除绑定指令
$config['command_unbind']= '解除绑定';