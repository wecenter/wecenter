<?php

$config['language_characteristic'] = array(
	// 某些动作的确认指令
	'ok' => array(
		'好', '好的', '是', '是的', '恩', '可', '可以', '行', '行啊', '中', '要', '哦', '嗯', '确认', '确定', 'yes', '更多'
	),

	// 某些动作提示的取消指令
	'cancel' => array(
		'不', '不要', '别', '算了', '取消', 'no', '否', "don't"
	)
);

/****** 微信自定义菜单选项 ******/

// 热门问题图文列表封面图
$config['default_list_image'] = G_STATIC_URL . '/common/weixin_default_image.png';

$config['default_list_image_path'] = ROOT_PATH . 'static/common/weixin_default_image.png';
