<?php

$aws_plugin = array(
	'title' => 'WeiXin Enterprise Edition for WeCenter',	// 插件标题
	'version' => 20131224,	// 插件版本
	'description' => '微信公众平台企业版',	// 插件描述
	'requirements' => '20131213',	// 最低 Build 要求
	
	'contents' => array(
		// 对控制器构造部署代码 (setup)
		'setups' => array(
			
		),
	
		// 对控制器 Action 部署代码 (只支持模板输出之前)
		'actions' => array(
		
		),
		
		// 注册 Model, 用 $this->model('name') 访问
		'model' => array(
			'class_name' => 'aws_weixin_enterprise_class',	// Model name, 以 _class 结尾
			'include' => 'model.php',	// 引入代码文件位置
		),
	),
);
