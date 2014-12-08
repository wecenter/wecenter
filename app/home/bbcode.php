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

class bbcode extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array(
			
		);

		return $rule_action;
	}
	
	public function index_action()
	{
		$text = '[color="red"]Lorem ipsum dolor sit amet, consectetur adipiscing elit.[/color]
[color="blue"]Volutpat tellus vulputate dui venenatis quis euismod turpis pellentesque.[/color]
[color="#f66"]Suspendisse sit amet ipsum eu odio sagittis ultrices at non sapien.[/color]
[color="#ff0088"]Quisque viverra feugiat purus, in luctus faucibus felis eget viverra.[/color]
[color="#cccccc"]Suspendisse sit amet ipsum eu odio sagittis ultrices at non sapien.[/color]';
		
		$Decoda = new Services_Decoda($text, array(
			'xhtmlOutput' => true,
			'strictMode' => false,
			'escapeHtml' => true
		));
		
		$Decoda->defaults();
		
		$Decoda->whitelist('color', 'b', 'i', 'u', 'list', 'quote', 'code', 'img', 'url');
		
		echo $Decoda->parse();
	}
}