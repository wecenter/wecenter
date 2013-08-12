<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function get_favorite_tags_action()
	{
		echo json_encode($this->model('favorite')->get_favorite_tags($this->user_id, 10));
	}
	
	public function update_favorite_tag_action()
	{
		if (rtrim($_POST['tags'], ',') == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请添加话题标签')));
		}
		
		$this->model('favorite')->add_favorite($_POST['answer_id'], $this->user_id);
		
		$this->model('favorite')->update_favorite_tag($_POST['answer_id'], $_POST['tags'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function remove_favorite_item_action()
	{
		$this->model('favorite')->remove_favorite_item($_POST['answer_id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function remove_favorite_tag_action()
	{
		$this->model('favorite')->remove_favorite_tag($_POST['answer_id'], $_POST['tags'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}