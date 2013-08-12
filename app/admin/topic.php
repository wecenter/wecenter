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


if (!defined('IN_ANWSION'))
{
	die;
}

class topic extends AWS_ADMIN_CONTROLLER
{
	var $per_page = 20;

	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(303));
	}
	
	public function list_action()
	{
		if ($_POST)
		{
			foreach ($_POST as $key => $val)
			{
				if ($key == 'keyword')
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/topic/list/' . implode('__', $param)
			), 1, null));
		}
		
		$topic_list = $this->model('topic')->get_topic_search_list($_GET['page'], $this->per_page, $_GET['keyword'], $_GET['question_count_min'], $_GET['question_count_max'], $_GET['topic_pic'], $_GET['topic_description']);
		
		$total_rows = $this->model('topic')->found_rows();
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if ($key != 'page')
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/topic/list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url, 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('话题管理'), "admin/topic/list/");
		
		TPL::assign('topic_num', $total_rows);
		TPL::assign('search_url', $search_url);
		TPL::assign('list', $topic_list);
		TPL::output("admin/topic/list");
	}

	/**
	 * 格式化话题列表
	 * @param unknown_type $list
	 */
	function topic_list_process($list)
	{
		if (empty($list))
		{
			return false;
		}
		
		foreach ($list as $key => $topic)
		{
			$list[$key]['add_time'] = date("Y-m-d H:i", $topic['add_time']);
			$list[$key]['topic_title'] = cjk_substr($topic['topic_title'], 0, 12, 'UTF-8', '...');
			$list[$key]['topic_pic'] = get_topic_pic_url('min', $topic['topic_pic']);
			
			if ($topic['parent_id'] > 0)
			{
				$list[$key]['parent'] = $this->model('topic')->get_topic_by_id($topic['parent_id']);
			}
		}
		
		return $list;
	}
	
	public function topic_lock_action()
	{
		define('IN_AJAX', TRUE);
		
		$status = isset($_GET['status']) ? $_GET['status'] : 0;
		
		$this->model('topic')->lock_topic_by_ids($_GET['topic_id'], $status);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('话题编辑'), 'admin/topic/edit/');
		
		if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'));
		}

		TPL::assign('topic_info', $topic_info);
		
		TPL::import_js('js/ajaxupload.js');
		
		TPL::output("admin/topic/edit");
	}
	
	public function save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}
		
		$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']);
			
		if ($topic_info['topic_title'] != $_POST['topic_title'] AND $this->model('topic')->get_topic_by_title($_POST['topic_title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('同名话题已经存在')));
		}
		
		$this->model('topic')->update_topic($_GET['topic_id'], $_POST['topic_title'], $_POST['topic_description']);
		
		$this->model('topic')->lock_topic_by_ids($_GET['topic_id'], $_POST['topic_lock']);
			
		$referer_url = empty($_POST['referer_url']) ? get_js_url('/admin/topic/list/') : $_POST['referer_url'];
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => $referer_url
		), 1, null));
	}
	
	public function topic_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (!$_POST['topic_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(nul, -1, AWS_APP::lang()->_t('请选择话题进行操作')));
		}
		
		switch($_POST['action_type'])
		{
			case 'remove' : 
				$this->model('topic')->remove_topic_by_ids($_POST['topic_ids']);
			break;
			
			case 'lock' : 
				$this->model('topic')->lock_topic_by_ids($_POST['topic_ids'], 1);
			break;
		}
		
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}