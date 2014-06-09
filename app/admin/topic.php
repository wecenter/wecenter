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
				'url' => get_js_url('/admin/topic/list/' . implode('__', $param))
			), 1, null));
		}
		
		$where = array();
		
		if ($_GET['keyword'])
		{			
			$where[] = "topic_title LIKE '" . $this->model('topic')->quote($_GET['keyword']) . "%'";
		}
		
		if ($_GET['question_count_min'] OR $_GET['question_count_min'] == '0')
		{
			$where[] = 'discuss_count >= ' . intval($_GET['question_count_min']);
		}
		
		if ($_GET['question_count_max'] OR $_GET['question_count_max'] == '0')
		{
			$where[] = 'discuss_count <= ' . intval($_GET['question_count_max']);
		}
		
		if (base64_decode($_GET['start_date']))
		{
			$where[] = 'add_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}

		if (base64_decode($_GET['end_date']))
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}
		
		$topic_list = $this->model('topic')->get_topic_list(implode(' AND ', $where), 'topic_id DESC', $this->per_page, $_GET['page']);
		
		$total_rows = $this->model('topic')->found_rows();
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/topic/list/' . implode('__', $url_param)), 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('话题管理'), "admin/topic/list/");
		
		TPL::assign('topics_count', $total_rows);
		TPL::assign('search_url', $search_url);
		TPL::assign('list', $topic_list);
		TPL::output("admin/topic/list");
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
		
		TPL::output('admin/topic/edit');
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