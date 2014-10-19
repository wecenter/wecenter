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

class answer_class extends AWS_MODEL
{
	public function get_answer_by_id($answer_id)
	{
		static $answers;

		if ($answers[$answer_id])
		{
			return $answers[$answer_id];
		}

		$answers[$answer_id] = $this->fetch_row('answer', 'answer_id = ' . intval($answer_id));

		return $answers[$answer_id];
	}

	public function get_answers_by_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		if ($answers = $this->fetch_all('answer', "answer_id IN (" . implode(', ', $answer_ids) . ")"))
		{
			foreach ($answers AS $key => $val)
			{
				$result[$val['answer_id']] = $val;
			}
		}

		return $result;
	}

	public function get_answer_count_by_question_id($question_id, $where = null)
	{
		if ($where)
		{
			$where = ' AND ' . $where;
		}

		return $this->count('answer', "question_id = " . intval($question_id) . $where);
	}

	public function get_answer_list_by_question_id($question_id, $limit = 20, $where = null, $order = 'answer_id DESC')
	{
		if ($where)
		{
			$_where = ' AND (' . $where . ')';
		}

		if ($answer_list = $this->fetch_all('answer', 'question_id = ' . intval($question_id) . $_where, $order, $limit))
		{
			foreach($answer_list as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($users_info = $this->model('account')->get_user_info_by_uids($uids, true))
			{
				foreach($answer_list as $key => $val)
				{
					$answer_list[$key]['user_info'] = $users_info[$val['uid']];
				}
			}
		}

		return $answer_list;
	}

	public function get_vote_user_by_answer_id($answer_id)
	{
		if (!$answer_id)
		{
			return array();
		}

		if ($users = $this->query_all("SELECT vote_uid FROM " . $this->get_table('answer_vote') . " WHERE answer_id = " . intval($answer_id) . " AND vote_value = 1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	public function get_vote_user_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return array();
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($users = $this->query_all("SELECT vote_uid, answer_id FROM " . $this->get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_ids) . ") AND vote_value = 1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['answer_id']][$val['vote_uid']] = $vote_users_info[$val['vote_uid']];
			}
		}

		return $data;
	}


	/**
	 *
	 * 根据回复问题ID，得到反对的用户
	 * @param int $answer_id
	 *
	 * @return array
	 */
	public function get_vote_against_user_by_answer_id($answer_id)
	{
		if (!$answer_id)
		{
			return array();
		}

		if ($users = $this->query_all("SELECT vote_uid FROM " . $this->get_table('answer_vote') . " WHERE answer_id = " . intval($answer_id) . " AND vote_value = -1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	public function get_vote_agree_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($votes = $this->fetch_all('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = 1'))
		{
			foreach ($votes as $key => $val)
			{
				$data[$val['answer_id']][] = $val;
			}
		}

		return $data;
	}

	public function get_vote_against_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($votes = $this->fetch_all('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = -1'))
		{
			foreach ($votes as $key => $val)
			{
				$data[$val['answer_id']][] = $val;
			}
		}

		return $data;
	}

	/**
	 *
	 * 根据回复问题ID，得到反对的用户
	 * @param int $answer_id
	 *
	 * @return array
	 */
	public function get_vote_against_user_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return array();
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($users = $this->query_all("SELECT vote_uid, answer_id FROM " . $this->get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_ids) . ") AND vote_value = -1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['answer_id']][$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	/**
	 *
	 * 保存问题回复内容
	 */
	public function save_answer($question_id, $answer_content, $uid, $anonymous = 0)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}

		if (!$answer_id = $this->insert('answer', array(
			'question_id' => $question_info['question_id'],
			'answer_content' => htmlspecialchars($answer_content),
			'add_time' => time(),
			'uid' => intval($uid),
			'category_id' => $question_info['category_id'],
			'anonymous' => intval($anonymous),
			'ip' => ip2long(fetch_ip())
		)))
		{
			return false;
		}

		$this->update('question', array(
			'update_time' => time(),
		), 'question_id = ' . intval($question_id));

		$this->model('question')->update_answer_count($question_id);
		$this->model('question')->update_answer_users_count($question_id);

		$this->shutdown_update('users', array(
			'answer_count' => $this->count('answer', 'uid = ' . intval($uid))
		), 'uid = ' . intval($uid));

		return $answer_id;
	}

	/**
	 *
	 * 更新问题回复内容
	 */
	public function update_answer($answer_id, $question_id, $answer_content, $attach_access_key)
	{
		$answer_id = intval($answer_id);
		$question_id = intval($question_id);

		if (!$answer_id OR !$question_id)
		{
			return false;
		}

		$data = array(
			'answer_content' => htmlspecialchars($answer_content)
		);

		// 更新问题最后时间
		$this->shutdown_update('question', array(
			'update_time' => time(),
		), 'question_id = ' . intval($question_id));

		if ($attach_access_key)
		{
			$this->model('publish')->update_attach('answer', $answer_id, $attach_access_key);
		}

		return $this->update('answer', $data, 'answer_id = ' . intval($answer_id));
	}

	public function update_answer_by_id($answer_id, $answer_info)
	{
		return $this->update('answer', $answer_info, 'answer_id = ' . intval($answer_id));
	}

	public function set_answer_publish_source($answer_id, $publish_source)
	{
		return $this->update('answer', array(
			'publish_source' => htmlspecialchars($publish_source)
		), 'answer_id = ' . intval($answer_id));
	}

	/**
	 *
	 * 回复投票
	 * @param int $answer_id   //回复id
	 * @param int $question_id //问题ID
	 * @param int $vote_value  //-1反对 1 赞同
	 * @param int $uid         //用户ID
	 *
	 * @return boolean true|false
	 */
	public function change_answer_vote($answer_id, $vote_value = 1, $uid = 0,$reputation_factor=0)
	{
		if (!$answer_id)
		{
			return false;
		}

		if (! in_array($vote_value, array(
			- 1,
			0,
			1
		)))
		{
			return false;
		}

		$answer_info = $this->get_answer_by_id($answer_id);

		$question_id = $answer_info['question_id'];
		$answer_uid = $answer_info['uid'];

		if (!$vote_info = $this->get_answer_vote_status($answer_id, $uid)) //添加记录
		{
			$this->insert('answer_vote', array(
				'answer_id' => $answer_id,
				'answer_uid' => $answer_uid,
				'vote_uid' => $uid,
				'add_time' => time(),
				'vote_value' => $vote_value,
				'reputation_factor' => $reputation_factor
			));

			if ($vote_value == 1)
			{
				ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_AGREE, '', intval($answer_id));
			}

		}
		else if ($vote_info['vote_value'] == $vote_value)
		{
			$this->delete_answer_vote($vote_info['voter_id']);

			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_AGREE . ' AND uid = ' . intval($uid) . ' AND associate_id = ' . intval($question_id) . ' AND associate_attached = ' . intval($answer_id));
		}
		else
		{
			$this->set_answer_vote_status($vote_info['voter_id'], $vote_value);

			if ($vote_value == 1)
			{
				ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_AGREE, '', $answer_id);
			}
		}

		if ($vote_value == 1 AND $vote_info['vote_value'] != 1 AND $answer_info['uid'] != $uid)
		{
			$this->model('notify')->send($uid, $answer_info['uid'], notify_class::TYPE_ANSWER_AGREE, notify_class::CATEGORY_QUESTION, $question_id, array(
				'from_uid' => $uid,
				'question_id' => $question_id,
				'item_id' => $answer_id,
			));
		}

		$this->update_vote_count($answer_id, 'against');
		$this->update_vote_count($answer_id, 'agree');

		$this->update_question_vote_count($question_id);

		// 更新回复作者的被赞同数
		$this->model('account')->sum_user_agree_count($answer_uid);

		return true;
	}

	/**
	 * 删除回复投票
	 * Enter description here ...
	 * @param unknown_type $voter_id
	 */
	public function delete_answer_vote($voter_id)
	{
		return $this->delete('answer_vote', "voter_id = " . intval($voter_id));
	}

	public function update_vote_count($answer_id, $type)
	{
		if (! in_array($type, array(
			'against',
			'agree'
		)))
		{
			return false;
		}

		$vote_value = ($type == 'agree') ? '1' : '-1';

		$count = $this->count('answer_vote', 'answer_id = ' . intval($answer_id) . ' AND vote_value = ' . $vote_value);

		return $this->query("UPDATE " . $this->get_table('answer') . " SET {$type}_count = {$count} WHERE answer_id = " . intval($answer_id));
	}

	public function update_question_vote_count($question_id)
	{
		if (!$answers = $this->get_answer_list_by_question_id($question_id, null))
		{
			return false;
		}

		$answer_ids = array();

		foreach($answers as $key => $val)
		{
			$answer_ids[] = $val['answer_id'];
		}

		$agree_count = $this->count('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = 1');

		$against_count = $this->count('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = -1');

		return $this->update('question', array(
			'agree_count' => $agree_count,
			'against_count' => $against_count
		), 'question_id = ' . intval($question_id));
	}

	public function set_answer_vote_status($voter_id, $vote_value)
	{
		return $this->update('answer_vote', array(
			"add_time" => time(),
			"vote_value" => $vote_value
		), "voter_id = " . intval($voter_id));
	}

	public function get_answer_vote_status($answer_id, $uid)
	{
		if (is_array($answer_id))
		{
			if ($result = $this->query_all("SELECT answer_id, vote_value FROM " . get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_id) . ") AND vote_uid = " . intval($uid)))
			{
				foreach ($result AS $key => $val)
				{
					$vote_status[$val['answer_id']] = $val;
				}
			}

			foreach ($answer_id AS $aid)
			{
				if ($vote_status[$aid])
				{
					$result[$aid] = $vote_status[$aid]['vote_value'];
				}
				else
				{
					$result[$aid] = '0';
				}
			}

			return $result;
		}
		else
		{
			return $this->fetch_row('answer_vote', "answer_id = " . intval($answer_id) . " AND vote_uid = " . intval($uid));
		}
	}

	/**
	 * 删除问题关联的所有回复及相关的内容
	 */
	public function remove_answers_by_question_id($question_id)
	{
		if (!$answers = $this->get_answer_list_by_question_id($question_id))
		{
			return false;
		}

		foreach ($answers as $key => $val)
		{
			$answer_ids[] = $val['answer_id'];
		}

		return $this->remove_answer_by_ids($answer_ids);
	}

	/**
	 * 根据回复集合批量删除回复
	 */
	public function remove_answer_by_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		foreach ($answer_ids as $answer_id)
		{
			$this->remove_answer_by_id($answer_id);
		}

		return true;
	}

	public function remove_answer_by_id($answer_id)
	{
		if ($answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			$this->delete('answer_vote', "answer_id = " . intval($answer_id)); // 删除赞同

			$this->delete('answer_comments', 'answer_id = ' . intval($answer_id));	// 删除评论

			$this->delete('answer_thanks', 'answer_id = ' . intval($answer_id));	// 删除感谢

			$this->delete('answer_uninterested', 'answer_id = ' . intval($answer_id));	// 删除没有帮助

			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_ANSWER . ' AND associate_id = ' . intval($answer_id));
			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id));

			if ($attachs = $this->model('publish')->get_attach('answer', $answer_id))
			{
				foreach ($attachs as $key => $val)
				{
					$this->model('publish')->remove_attach($val['id'], $val['access_key']);
				}
			}

			$this->delete('answer', "answer_id = " . intval($answer_id));

			$this->model('question')->update_answer_count($answer_info['question_id']);
		}

		return true;
	}

	public function has_answer_by_uid($question_id, $uid)
	{
		return $this->fetch_one('answer', 'answer_id', "question_id = " . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function get_last_answer($question_id)
	{
		return $this->fetch_row('answer', 'question_id = ' . intval($question_id), 'answer_id DESC');
	}

	public function get_last_answer_by_question_ids($question_ids)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($question_ids, 'intval_string');

		if ($last_answer_query = $this->query_all("SELECT last_answer FROM " . get_table('question') . " WHERE question_id IN (" . implode(',', $question_ids) . ")"))
		{
			foreach ($last_answer_query AS $key => $val)
			{
				if ($val['last_answer'])
				{
					$last_answer_ids[] = $val['last_answer'];
				}
			}

			if ($last_answer_ids)
			{
				if ($last_answer = $this->fetch_all('answer', "answer_id IN (" . implode(',', $last_answer_ids) . ")"))
				{
					foreach ($last_answer AS $key => $val)
					{
						$result[$val['question_id']] = $val;
					}
				}
			}
		}

		return $result;
	}

	public function get_answer_agree_users($answer_id)
	{
		if ($agrees = $this->fetch_all('answer_vote', "answer_id = " . intval($answer_id) . " AND vote_value = 1"))
		{
			foreach ($agrees as $key => $val)
			{
				$agree_uids[] = $val['vote_uid'];
			}
		}

		if ($users = $this->model('account')->get_user_info_by_uids($agree_uids))
		{
			foreach ($users as $key => $val)
			{
				$user_infos[$val['uid']] = $val;
			}
		}

		if ($agree_uids)
		{
			foreach ($agree_uids as $key => $val)
			{
				$agree_users[$val] = $user_infos[$val]['user_name'];
			}
		}
		return $agree_users;
	}

	public function update_answer_comments_count($answer_id)
	{
		$count = $this->count('answer_comments', "answer_id = " . intval($answer_id));

		$this->shutdown_update('answer', array(
			'comment_count' => $count
		), "answer_id = " . intval($answer_id));
	}

	public function insert_answer_comment($answer_id, $uid, $message)
	{
		if (!$answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			return false;
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']))
		{
			return false;
		}

		$message = $this->model('question')->parse_at_user($message, false, false, true);

		$comment_id = $this->insert('answer_comments', array(
			'uid' => intval($uid),
			'answer_id' => intval($answer_id),
			'message' => htmlspecialchars($message),
			'time' => time()
		));

		if ($answer_info['uid'] != $uid)
		{
			$this->model('notify')->send($uid, $answer_info['uid'], notify_class::TYPE_ANSWER_COMMENT, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
				'from_uid' => $uid,
				'question_id' => $answer_info['question_id'],
				'item_id' => $answer_info['answer_id'],
				'comment_id' => $comment_id
			));

			if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($answer_info['uid']))
			{
				$weixin_user_info = $this->model('account')->get_user_info_by_uid($weixin_user['uid']);

				if ($weixin_user_info['weixin_settings']['NEW_COMMENT'] != 'N')
				{
					$this->model('weixin')->send_text_message($weixin_user['openid'], "您在 [" . $question_info['question_content'] . "] 中的回答收到了新评论:\n\n" . strip_tags($message), $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id'] . '?answer_id=' . $answer_info['answer_id'] . '&single=TRUE'));
				}
			}
		}

		if ($at_users = $this->model('question')->parse_at_user($message, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $uid)
				{
					$this->model('notify')->send($uid, $user_id, notify_class::TYPE_ANSWER_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
						'from_uid' => $uid,
						'question_id' => $answer_info['question_id'],
						'item_id' => $answer_info['answer_id'],
						'comment_id' => $comment_id
					));

					if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($user_id))
					{
						$answer_user = $this->model('account')->get_user_info_by_uid($uid);

						$this->model('weixin')->send_text_message($weixin_user['openid'], $answer_user['user_name'] . " 在问题 [" . $question_info['question_content'] . "] 的答案评论中提到了您", $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id'] . '?answer_id=' . $answer_info['answer_id'] . '&single=TRUE'));
					}
				}
			}
		}

		$this->update_answer_comments_count($answer_id);

		return $comment_id;
	}

	public function get_answer_comments($answer_id)
	{
		return $this->fetch_all('answer_comments', "answer_id = " . intval($answer_id), "time ASC");
	}

	public function get_comment_by_id($comment_id)
	{
		return $this->fetch_row('answer_comments', "id = " . intval($comment_id));
	}

	public function remove_comment($comment_id)
	{
		return $this->delete('answer_comments', "id = " . intval($comment_id));
	}

	public function user_rated($type, $answer_id, $uid)
	{
		if (!$uid)
		{
			return false;
		}

		switch ($type)
		{
			default:
				return false;
			break;

			case 'thanks':
			case 'uninterested':

			break;
		}

		static $user_rated;

		if ($user_rated[$type . '_' . $answer_id . '_' . $uid])
		{
			return $user_rated[$type . '_' . $answer_id . '_' . $uid];
		}

		$user_rated[$type . '_' . $answer_id . '_' . $uid] = $this->fetch_row('answer_' . $type, 'uid = ' . intval($uid) . ' AND answer_id = ' . intval($answer_id));

		return $user_rated[$type . '_' . $answer_id . '_' . $uid];
	}

	public function users_rated($type, $answer_ids, $uid)
	{
		if (!$uid)
		{
			return false;
		}

		if (!is_array($answer_ids))
		{
			return false;
		}

		switch ($type)
		{
			default:
				return false;
			break;

			case 'thanks':
			case 'uninterested':

			break;
		}

		$all_rated = $this->fetch_all('answer_' . $type, 'uid = ' . intval($uid) . ' AND answer_id IN(' . implode(',', $answer_ids) . ')');

		foreach ($all_rated AS $key => $val)
		{
			$users_rated[$val['answer_id']] = $val;
		}

		return $users_rated;
	}

	public function user_rate($type, $answer_id, $uid, $user_name)
	{
		switch ($type)
		{
			default:
				return false;
			break;

			case 'thanks':
			case 'uninterested':

			break;
		}

		if ($user_rated = $this->user_rated($type, $answer_id, $uid))
		{
			if ($type == 'thanks')
			{
				return true;
			}

			$this->delete('answer_' . $type, 'uid = ' . intval($uid) . ' AND answer_id = ' . intval($answer_id));
		}
		else
		{
			$this->insert('answer_' . $type, array(
				'uid' => $uid,
				'answer_id' => $answer_id,
				'user_name' => $user_name,
				'time' => time()
			));
		}

		$this->update_user_rate_count($type, $answer_id);

		$answer_info = $this->get_answer_by_id($answer_id);

		if ($type == 'thanks')
		{
			$this->model('integral')->process($uid, 'ANSWER_THANKS', get_setting('integral_system_config_thanks'), '感谢回复 #' . $answer_info['answer_id'], $answer_info['answer_id']);

			$this->model('integral')->process($answer_info['uid'], 'THANKS_ANSWER', -get_setting('integral_system_config_thanks'), '回复被感谢 #' . $answer_info['answer_id'], $answer_info['answer_id']);
		}
		else if ($answer_info['uninterested_count'] >= get_setting('uninterested_fold'))
		{
			if (!$this->model('integral')->fetch_log($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id']))
			{
				ACTION_LOG::set_fold_action_history($answer_info['answer_id'], 1);

				$this->model('integral')->process($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id'], get_setting('integral_system_config_answer_fold'), '回复折叠 #' . $answer_info['answer_id']);
			}
		}

		$this->model('account')->update_thanks_count($answer_info['uid']);

		return !$user_rated;
	}

	public function update_user_rate_count($type, $answer_id)
	{
		switch ($type)
		{
			default:
				return false;
			break;

			case 'thanks':
			case 'uninterested':

			break;
		}

		$this->shutdown_update('answer', array(
			$type . '_count' => $this->count('answer_' . $type, 'answer_id = ' . intval($answer_id))
		), 'answer_id = ' . intval($answer_id));
	}

	public function set_best_answer($answer_id)
	{
		if (!$answer_info = $this->get_answer_by_id($answer_id))
		{
			return false;
		}

		$this->model('integral')->process($answer_info['uid'], 'BEST_ANSWER', get_setting('integral_system_config_best_answer'), '问题 #' . $answer_info['question_id'] . ' 最佳回复');

		$this->shutdown_update('question', array(
			'best_answer' => $answer_info['answer_id']
		), 'question_id = ' . $answer_info['question_id']);
	}

	public function calc_best_answer()
	{
		if (!$best_answer_day = intval(get_setting('best_answer_day')))
		{
			return false;
		}

		$start_time = time() - $best_answer_day * 3600 * 24;

		if ($questions = $this->query_all("SELECT question_id FROM " . $this->get_table('question') . " WHERE add_time < " . $start_time . " AND best_answer = 0 AND answer_count > " . get_setting('best_answer_min_count')))
		{
			foreach ($questions AS $key => $val)
			{
				$best_answer = $this->fetch_row('answer', 'question_id = ' . intval($val['question_id']), 'agree_count DESC');

				if ($best_answer['agree_count'] > get_setting('best_agree_min_count'))
				{
					$this->set_best_answer($best_answer['answer_id']);
				}
			}
		}

		return true;
	}
}