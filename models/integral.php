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

class integral_class extends AWS_MODEL
{
	public function process($uid, $action, $integral, $note = '', $item_id = null)
	{
		/*if (get_setting('integral_system_enabled') == 'N')
		{
			return false;
		}*/

		if ($integral == 0)
		{
			return false;
		}

		$log_id = $this->log($uid, $action, $integral, $note, $item_id);

		$this->sum_integral($uid);

		return $log_id;
	}

	public function fetch_log($uid, $action)
	{
		return $this->fetch_row('integral_log', 'uid = ' . intval($uid) . ' AND action = \'' . $this->quote($action) . '\'');
	}

	public function log($uid, $action, $integral, $note = '', $item_id = null)
	{
		if ($user_info = $this->model('account')->get_user_info_by_uid($uid))
		{
			return $this->insert('integral_log', array(
				'uid' => intval($uid),
				'action' => $action,
				'integral' => (int)$integral,
				'balance' => ((int)$user_info['integral'] + (int)$integral),
				'note' => $note,
				'item_id' => (int)$item_id,
				'time' => time()
			));
		}
	}

	// 根据日志计算积分
	public function sum_integral($uid)
	{
		return $this->update('users', array(
			'integral' => $this->sum('integral_log', 'integral', 'uid = ' . intval($uid))
		), 'uid = ' . intval($uid));
	}

	public function parse_log_item($parse_items)
	{
		if (!is_array($parse_items))
		{
			return false;
		}

		foreach ($parse_items AS $log_id => $item)
		{
			if (strstr($item['action'], 'ANSWER_FOLD_'))
			{
				$item['action'] = 'ANSWER_FOLD';
			}

			switch ($item['action'])
			{
				case 'NEWS_QUESTION':
				case 'ANSWER_QUESTION':
				case 'QUESTION_ANSWER':
				case 'INVITE_ANSWER':
				case 'ANSWER_INVITE':
				case 'THANKS_QUESTION':
				case 'QUESTION_THANKS':
					$question_ids[] = $item['item_id'];
				break;

				case 'ANSWER_THANKS':
				case 'THANKS_ANSWER':
				case 'ANSWER_FOLD':
				case 'BEST_ANSWER':
					$answer_ids[] = $item['item_id'];
				break;

				case 'INVITE':
					$user_ids[] = $item['item_id'];
				break;
			}
		}

		if ($question_ids)
		{
			$questions_info = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		if ($answer_ids)
		{
			$answers_info = $this->model('answer')->get_answers_by_ids($answer_ids);
		}

		if ($user_ids)
		{
			$users_info = $this->model('account')->get_user_info_by_uids($user_ids);
		}

		foreach ($parse_items AS $log_id => $item)
		{
			if (!$item['item_id'])
			{
				continue;
			}

			if (strstr($item['action'], 'ANSWER_FOLD_'))
			{
				$item['action'] = 'ANSWER_FOLD';
			}

			switch ($item['action'])
			{
				case 'NEWS_QUESTION':
				case 'ANSWER_INVITE':
				case 'ANSWER_QUESTION':
				case 'QUESTION_ANSWER':
				case 'INVITE_ANSWER':
				case 'THANKS_QUESTION':
				case 'QUESTION_THANKS':
					if ($questions_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '问题: ' . $questions_info[$item['item_id']]['question_content'],
							'url' => get_js_url('/question/' . $item['item_id'])
						);
					}

				break;

				case 'ANSWER_THANKS':
				case 'THANKS_ANSWER':
				case 'ANSWER_FOLD':
				case 'BEST_ANSWER':
					if ($answers_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '答案: ' . cjk_substr($answers_info[$item['item_id']]['answer_content'], 0, 24, 'UTF-8', '...'),
							'url' => get_js_url('/question/id-' . $answers_info[$item['item_id']]['question_id'] . '__answer_id-' . $item['item_id'] . '__single-TRUE')
						);
					}
				break;

				case 'INVITE':
					if ($users_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '会员: ' . $users_info[$item['item_id']]['user_name'],
							'url' => get_js_url('/people/' . $users_info[$item['item_id']]['uid'])
						);
					}
				break;
			}
		}

		return $result;
	}
}