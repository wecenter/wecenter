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

if (! defined('IN_ANWSION'))
{
	die();
}

class question extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(301));
	}

	public function question_list_action()
	{
		if ($this->is_post())
		{
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
				'url' => get_js_url('/admin/question/question_list/' . implode('__', $param))
			), 1, null));
		}

		$where = array();

		if ($_GET['keyword'])
		{
			$where[] = "(MATCH(question_content_fulltext) AGAINST('" . $this->model('question')->quote($this->model('search_fulltext')->encode_search_code($this->model('system')->analysis_keyword($_GET['keyword']))) . "' IN BOOLEAN MODE))";
		}

		if ($_GET['category_id'])
		{
			if ($category_ids = $this->model('system')->get_category_with_child_ids('question', $_GET['category_id']))
			{
				$where[] = 'category_id IN (' . implode(',', $category_ids) . ')';
			}
			else
			{
				$where[] = 'category_id = ' . intval($category_id);
			}
		}

		if (base64_decode($_GET['start_date']))
		{
			$where[] = 'add_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}

		if (base64_decode($_GET['end_date']))
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}

		if ($_GET['user_name'])
		{
			$user_info = $this->model('account')->get_user_info_by_username($_GET['user_name']);

			$where[] = 'published_uid = ' . intval($user_info['uid']);
		}

		if ($_GET['answer_count_min'])
		{
			$where[] = 'answer_count >= ' . intval($_GET['answer_count_min']);
		}

		if ($_GET['answer_count_max'])
		{
			$where[] = 'answer_count <= ' . intval($_GET['answer_count_max']);
		}

		if ($_GET['best_answer'])
		{
			$where[] = 'best_answer > 0';
		}

		if ($question_list = $this->model('question')->fetch_page('question', implode(' AND ', $where), 'question_id DESC', $_GET['page'], $this->per_page))
		{
			$total_rows = $this->model('question')->found_rows();

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

		$url_param = array();

		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/question/question_list/') . implode('__', $url_param),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		$this->crumb(AWS_APP::lang()->_t('问题管理'), 'admin/question/question_list/');

		TPL::assign('question_count', $total_rows);
		TPL::assign('category_list', $this->model('system')->build_category_html('question', 0, 0, null, true));
		TPL::assign('keyword', $_GET['keyword']);
		TPL::assign('list', $question_list);

		TPL::output('admin/question/question_list');
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
			'base_url' => get_js_url('/admin/question/report_list/status-' . intval($_GET['status'])),
			'total_rows' => $report_total,
			'per_page' => $this->per_page
		))->create_links());

		TPL::output('admin/question/report_list');
	}
}