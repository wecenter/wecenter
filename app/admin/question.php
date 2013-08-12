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

if (! defined('IN_ANWSION'))
{
	die();
}

class question extends AWS_ADMIN_CONTROLLER
{
	var $per_page = 20;
	
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(301));
	}

	public function question_list_action()
	{
		if ($this->is_post())
		{
			if ($_POST['user_name'] && (! $user_info = $this->model('account')->get_user_info_by_username($_POST['user_name'])))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
			}
			
			foreach ($_POST as $key => $val)
			{
				if ($key == 'start_date' OR $key == 'end_date')
				{
					$val = base64_encode($val);
				}
				
				if ($key == 'keyword' OR $key == 'user_name')
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/question/question_list/' . implode('__', $param)
			), 1, null));
		}
		
		if ($question_list = $this->model('question')->search_questions_list($_GET['page'], $this->per_page, $_GET['keyword'], $_GET['category_id'], base64_decode($_GET['start_date']), base64_decode($_GET['end_date']), $_GET['answer_count_min'], $_GET['answer_count_max'], rawurldecode($_GET['user_name']), $_GET['best_answer']))
		{
			foreach ($question_list AS $key => $val)
			{
				$question_list_uids[$val['published_uid']] = $val['published_uid'];
			}
			
			if ($question_list_uids)
			{
				$question_list_user_infos = $this->model('account')->get_user_info_by_uids($question_list_uids);
			}
			
			foreach ($question_list AS $key => $val)
			{
				$question_list[$key]['user_info'] = $question_list_user_infos[$val['published_uid']];
			}
		}
		
		
		$total_rows = $this->model('question')->search_questions_total;
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if ($key != 'page')
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/question/question_list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url, 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('问题管理'), "admin/question/question_list/");
		
		TPL::assign('question_num', $total_rows);
		TPL::assign('search_url', $search_url);
		TPL::assign('category_list', $this->model('system')->build_category_html('question', 0, 0, null, true));
		TPL::assign('keyword', $_GET['keyword']);
		TPL::assign('list', $question_list);
		TPL::output("admin/question/question_list");
	}

	public function question_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $_POST['question_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题进行操作')));
		}
		
		$this->model('question')->remove_question_by_ids($_POST['question_ids']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function answer_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $_POST['answer_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(nul, -1, AWS_APP::lang()->_t('请选择回复进行操作')));
		}
		
		$this->model('answer')->remove_answers_by_ids($_POST['answer_ids']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function report_list_action()
	{
		if ($report_list = $this->model('question')->get_report_list('status = ' . intval($_GET['status']), $_GET['page'], $this->per_page))
		{
			$report_total = $this->model('question')->found_rows();
			
			$userinfos = $this->model('account')->get_user_info_by_uids(fetch_array_value($report_list, 'uid'));
			
			foreach ($report_list as $key => $val)
			{
				$report_list[$key]['user'] = $userinfos[$val['uid']];
			}
		}
		
		$this->crumb(AWS_APP::lang()->_t('用户举报'), 'admin/question/report_list/');
		
		TPL::assign('list', $report_list);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(306));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/question/report_list/status-' . intval($_GET['status']), 
			'total_rows' => $report_total, 
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::output('admin/question/report_list');
	}

	public function report_batch_action()
	{
		$action_type = $_POST['action_type'];
		
		if (! $_POST['report_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择内容进行操作')));
		}
		
		if (! $action_type)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择操作类型')));
		}
		
		if ($action_type == 'delete')
		{
			foreach ($_POST['report_ids'] as $val)
			{
				$this->model('question')->delete_report($val);
			}
		}
		else if ($action_type == 'handle')
		{
			foreach ($_POST['report_ids'] as $val)
			{
				$this->model('question')->update_report($val, array(
					'status' => 1
				));
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function report_handle_ajax_action()
	{
		$this->model('question')->update_report($_GET['report_id'], array(
			'status' => 1
		));
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function approval_list_action()
	{
		if (!$_GET['type'])
		{
			$_GET['type'] = 'question';
		}
		
		switch ($_GET['type'])
		{
			case 'question':
				TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));
			break;
			
			case 'answer':
				TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));
			break;
		}
		
		if ($approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page))
		{
			$found_rows = $this->model('publish')->found_rows();
			
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_setting('base_url') . '/?/admin/question/approval_list/type-' . $_GET['type'], 
				'total_rows' => $found_rows, 
				'per_page' => $this->per_page
			))->create_links());
			
			foreach ($approval_list AS $key => $val)
			{
				if (!$approval_uids[$val['uid']])
				{
					$approval_uids[$val['uid']] = $val['uid'];
				}
			}
			
			TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($approval_uids));
		}
		
		TPL::assign($_GET['type'] . '_count', intval($found_rows));
		
		$this->crumb(AWS_APP::lang()->_t('内容审核'), 'admin/question/approval_list/');
		
		TPL::assign('approval_list', $approval_list);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));
		
		TPL::output('admin/question/approval_list');
	}
	
	public function approval_preview_action()
	{
		if (!$approval_item = $this->model('publish')->get_approval_item($_GET['id']))
		{
			die;
		}
		
		switch ($approval_item['type'])
		{
			case 'question':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['question_detail'])));
			break;
			
			case 'answer':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['answer_content'])));
			break;
		}
		
		if ($approval_item['data']['attach_access_key'])
		{
			$approval_item['attachs'] = $this->model('publish')->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']);
		}
		
		TPL::assign('approval_item', $approval_item);
		
		TPL::output('admin/question/approval_preview');
	}
	
	public function approval_batch_action()
	{
		if (!is_array($_POST['approval_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
		}
		
		switch ($_POST['batch_type'])
		{
			case 'approval':
			case 'decline':
				$func = $_POST['batch_type'] . '_publish';
				
				foreach ($_POST['approval_ids'] AS $approval_id)
				{
					$this->model('publish')->$func($approval_id);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}