<?php

$aws_plugin = array(
	'title' => 'External for Anwsion',	// 插件标题
	'version' => 20140221,	// 插件版本
	'description' => 'Anwsion 调用插件',	// 插件描述
	'requirements' => '20140221',	// 最低 Build 要求
	
	'contents' => array(
		// 对控制器构造部署代码 (setup)
		'setups' => array(
			
		),
	
		// 对控制器 Action 部署代码 (只支持模板输出之前)
		'actions' => array(
		
		),
		
		// 注册 Model, 用 $this->model('name') 访问
		'model' => array(
			'class_name' => 'aws_external_class',	// Model name, 以 _class 结尾
			'include' => 'aws_external.php',	// 引入代码文件位置
		),
	),
);
