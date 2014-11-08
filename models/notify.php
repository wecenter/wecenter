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

class notify_class extends AWS_MODEL
{
	//=========模型类别:model_type===================================================

	const CATEGORY_QUESTION	= 1;	// 问题
	const CATEGORY_PEOPLE	= 4;	// 人物
	const CATEGORY_CONTEXT	= 7;	// 文字

	const CATEGORY_ARTICLE	= 8;	// 文章


	//=========操作标示:action_type==================================================

	const TYPE_PEOPLE_FOCUS	= 101;	// 被人关注
	const TYPE_NEW_ANSWER	= 102;	// 关注的问题增加了新回复
	const TYPE_COMMENT_AT_ME	= 103;	// 有评论@提到我
	const TYPE_INVITE_QUESTION	= 104;	// 被人邀请问题问题
	const TYPE_ANSWER_COMMENT	= 105;	// 我的回复被评论
	const TYPE_QUESTION_COMMENT	= 106;	// 我的问题被评论
	const TYPE_ANSWER_AGREE	= 107;	// 我的回复收到赞同
	const TYPE_ANSWER_THANK	= 108;	// 我的回复收到感谢
	const TYPE_MOD_QUESTION	= 110;	// 我发布的问题被编辑
	const TYPE_REMOVE_ANSWER	= 111;	// 我发表的回复被删除

	const TYPE_REDIRECT_QUESTION	= 113;	// 我发布的问题被重定向
	const TYPE_QUESTION_THANK	= 114;	// 我发布的问题收到感谢
	const TYPE_CONTEXT	= 100;	// 纯文本通知

	const TYPE_ANSWER_AT_ME	= 115;	// 有回答 @ 提到我
	const TYPE_ANSWER_COMMENT_AT_ME	= 116;	// 有回答评论 @ 提到我

	const TYPE_ARTICLE_NEW_COMMENT	= 117; // 文章有新评论
	const TYPE_ARTICLE_COMMENT_AT_ME	= 118; // 文章评论提到我

	const TYPE_ARTICLE_APPROVED = 131; // 文章通过审核
	const TYPE_ARTICLE_REFUSED = 132; // 文章未通过审核
	const TYPE_QUESTION_APPROVED = 133; // 问题通过审核
	const TYPE_QUESTION_REFUSED = 134; // 问题未通过审核

	public $notify_actions = array();
	public $notify_action_details;

	public function setup()
	{
		if ($this->notify_action_details = AWS_APP::config()->get('notification')->action_details)
		{
			foreach ($this->notify_action_details as $key => $val)
			{
				$this->notify_actions[] = $key;
			}
		}
	}

	/**
	 * 发送通知
	 * @param $action_type	操作类型，使用notify_class调用TYPE
	 * @param $uid			接收用户id
	 * @param $data			附加数据
	 * @param $model_type	可选，合并类别，使用 notify_class 调用 CATEGORY
	 * @param $source_id	可选，合并子ID
	 */
	public function send($sender_uid, $recipient_uid, $action_type, $model_type = 0, $source_id = 0, $data = array())
	{
		if (!$recipient_uid)
		{
			return false;
		}

		if ((!in_array($action_type, $this->notify_actions) AND $action_type) OR !$this->check_notification_setting($recipient_uid, $action_type))
		{
			return false;
		}

		if ($notification_id = $this->insert('notification', array(
			'sender_uid' => intval($sender_uid),
			'recipient_uid' => intval($recipient_uid),
			'action_type' => intval($action_type),
			'model_type' => intval($model_type),
			'source_id' => $source_id,
			'add_time' => time(),
			'read_flag' => 0
		)))
		{
			$this->insert('notification_data', array(
				'notification_id' => $notification_id,
				'data' => serialize($data)
			));

			$this->model('account')->update_notification_unread($recipient_uid);

			return $notification_id;
		}
	}

	/**
	 * 获得通知列表
	 * read_status 0 - 未读, 1 - 已读, other - 所有
	 */
	public function list_notification($recipient_uid, $read_status = 0, $limit = null)
	{
		if (!$notify_ids = $this->get_notification_list($recipient_uid, $read_status, $limit))
		{
			return false;
		}

		if (!$notify_list = $this->get_notification_by_ids($notify_ids))
		{
			return false;
		}

		if ($unread_notifys = $this->get_unread_notification($recipient_uid))
		{
			$unread_extends = array();
			$unique_people = array();

			foreach ($unread_notifys as $key => $val)
			{
				if ($val['model_type'] == self::CATEGORY_QUESTION OR $val['model_type'] == self::CATEGORY_ARTICLE)
				{
					if (isset($unique_people[$val['source_id']][$val['action_type']][$val['data']['from_uid']]))
					{
						continue;
					}

					$unread_extends[$val['model_type']][$val['source_id']][] = $val;

					$action_type = $val['action_type'];

					if ($val['action_type'] == self::TYPE_QUESTION_THANK)
					{
						$action_type = self::TYPE_ANSWER_THANK;
					}

					$action_ex_details[$val['source_id']][$action_type][] = $val;

					$uids[] = $val['data']['from_uid'];

					$unique_people[$val['source_id']][$val['action_type']][$val['data']['from_uid']] = 1;
				}
			}
		}

		foreach ($notify_list as $key => $val)
		{
			if ($val['data']['question_id'])
			{
				$question_ids[] = $val['data']['question_id'];
			}

			if ($val['data']['article_id'])
			{
				$article_ids[] = $val['data']['article_id'];
			}

			if ($val['data']['from_uid'])
			{
				$uids[] = intval($val['data']['from_uid']);
			}

			if ($read_status == 0 AND count($unread_extends[$val['model_type']][$val['source_id']]) AND $this->notify_action_details[$val['action_type']]['combine'] == 1)
			{
				$notify_list[$key]['extends'] = $unread_extends[$val['model_type']][$val['source_id']];
				$notify_list[$key]['extend_details'] = $action_ex_details[$val['source_id']];
			}
		}

		if ($question_ids)
		{
			$question_list = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		if ($article_ids)
		{
			$article_list = $this->model('article')->get_article_info_by_ids($article_ids);
		}

		if ($uids)
		{
			$user_infos = $this->model('account')->get_user_info_by_uids($uids);
		}

		foreach ($notify_list as $key => $notify)
		{
			if (!$data = $notify['data'])
			{
				continue;
			}

			$tmp_data = array();

			$tmp_data['notification_id'] = $notify['notification_id'];
			$tmp_data['model_type'] = $notify['model_type'];
			$tmp_data['action_type'] = $notify['action_type'];
			$tmp_data['read_flag'] = $notify['read_flag'];
			$tmp_data['add_time'] = $notify['add_time'];

			$tmp_data['anonymous'] = $data['anonymous'];

			if ($data['from_uid'])
			{
				$user_info = $user_infos[$data['from_uid']];

				$tmp_data['p_user_name'] = $user_info['user_name'];
				$tmp_data['p_url'] = get_js_url('/people/' . $user_info['url_token']);
			}

			$token = 'notification_id=' . $notify['notification_id'];

			switch ($notify['model_type'])
			{
				case self::CATEGORY_ARTICLE :
					if ($notify['action_type'] == self::TYPE_ARTICLE_REFUSED)
					{
						$tmp_data['title'] = $data['title'];
					}
					else
					{
						if (!$article_list[$data['article_id']])
						{
							continue;
						}

						$tmp_data['title'] = $article_list[$data['article_id']]['title'];
					}

					$querys = array();

					$querys[] = $token;

					if ($notify['extends'])
					{
						$tmp_data['extend_count'] = count($notify['extends']);

						foreach ($notify['extends'] as $ex_key => $ex_notify)
						{
							$from_uid = $ex_notify['data']['from_uid'];

							if ($ex_notify['data']['item_id'])
							{
								$item_ids[] = $ex_notify['data']['item_id'];
							}
						}

						if ($item_ids)
						{
							asort($item_ids);

							$querys[] = 'item_id=' . implode(',', array_unique($item_ids));
						}

						$tmp_data['extend_details'] = $this->format_extend_detail($notify['extend_details'], $user_infos);
					}
					else if ($data['item_id'])
					{
						$querys[] = 'item_id=' . $data['item_id'];
					}

					$tmp_data['key_url'] = get_js_url('/article/' . $data['article_id'] . '?' . implode('&', $querys));

					break;

				case self::CATEGORY_QUESTION :
					switch ($notify['action_type'])
					{
						default :
							if ($notify['action_type'] == self::TYPE_QUESTION_REFUSED)
							{
								$tmp_data['title'] = $data['title'];
							}
							else
							{
								if (!$question_list[$data['question_id']])
								{
									continue;
								}

								$tmp_data['title'] = $question_list[$data['question_id']]['question_content'];
							}

							$rf = false;

							$querys = array();

							$querys[] = $token;

							if ($notify['extends'])
							{
								$tmp_data['extend_count'] = count($notify['extends']);

								$answer_ids = array();

								$comment_type = array();

								foreach ($notify['extends'] as $ex_key => $ex_notify)
								{
									if ($ex_notify['action_type'] == self::TYPE_INVITE_QUESTION)
									{
										$from_uid = $ex_notify['data']['from_uid'];
									}

									if ($ex_notify['action_type'] == self::TYPE_QUESTION_COMMENT OR $ex_notify['action_type'] == self::TYPE_COMMENT_AT_ME)
									{
										$comment_type[] = 'question';
									}

									if ($ex_notify['data']['item_id'])
									{
										$answer_ids[] = $ex_notify['data']['item_id'];
									}

									if ($ex_notify['action_type'] == self::TYPE_REDIRECT_QUESTION)
									{
										$rf = true;
									}
								}

								if (! $rf)
								{
									$querys[] = 'rf=false';
								}

								if ($from_uid)
								{
									$querys[] = 'source=' . base64_encode($from_uid);
								}

								if ($comment_type)
								{
									if (count(array_unique($comment_type)) == 1)
									{
										$querys[] = 'comment_unfold=' . array_pop($comment_type);
									}
									else if (count(array_unique($comment_type)) == 2)
									{
										$querys[] = 'comment_unfold=all';
									}
								}

								if ($answer_ids)
								{
									$answer_ids = array_unique($answer_ids);

									asort($answer_ids);

									$querys[] = 'item_id=' . implode(',', $answer_ids) . '#!answer_' . array_pop($answer_ids);
								}

								$tmp_data['extend_details'] = $this->format_extend_detail($notify['extend_details'], $user_infos);
							}
							else
							{
								switch ($notify['action_type'])
								{
									case self::TYPE_REDIRECT_QUESTION:
									case self::TYPE_QUESTION_REFUSED:
										break;

									case self::TYPE_MOD_QUESTION:
										$querys[] = 'column=log';

										break;

									case self::TYPE_INVITE_QUESTION:
										$querys[] = 'source=' . base64_encode($data['from_uid']);

										break;

									case self::TYPE_QUESTION_COMMENT:
									case self::TYPE_COMMENT_AT_ME:
										$querys[] = 'comment_unfold=question';
										break;

									default:
										$querys[] = 'rf=false';

										break;
								}

								if ($data['item_id'])
								{
									$querys[] = 'item_id=' . $data['item_id'] . '&answer_id=' . $data['item_id'] . '&single=TRUE#!answer_' . $data['item_id'];
								}
							}

							$tmp_data['key_url'] = get_js_url('/question/' . $data['question_id'] . '?' . implode('&', $querys));

							break;
					}

					break;

				case self::CATEGORY_PEOPLE :
					if (!$user_info)
					{
						unset($tmp_data);

						continue;
					}

					$tmp_data['key_url'] = $tmp_data['p_url'] . '?' . $token;
					break;

				case self::CATEGORY_CONTEXT :
					$tmp_data['content'] = $data['content'];
					break;
			}

			if ($tmp_data)
			{
				$list[] = $tmp_data;
			}
			else
			{
				$this->delete_notify('notification_id = ' . intval($notify['notification_id']));
			}
		}

		return $this->format_notification($list);
	}

	function format_extend_detail($extends, $user_infos)
	{
		if (!$extends OR ! is_array($extends))
		{
			return $extends;
		}

		$ex_details = array();

		foreach ($extends as $action_type => $val)
		{
			$answer_ids = array();
			$comment_type = array();
			$action_users = array();

			foreach ($val as $action)
			{
				$notification_id = $action['notification_id'];

				$uid = intval($action['data']['from_uid']);

				if ($uid)
				{
					$action_users[$uid][] = $action;
				}
			}

			$tmp_data['count'] = count($val);

			foreach ($action_users as $uid => $action)
			{
				$querys = array();

				$rf = false;

				$column_log = false;

				$notification_ids = array();

				foreach ($action as $ex_notify)
				{
					$notification_ids[] = $ex_notify['notification_id'];

					if ($ex_notify['action_type'] == self::TYPE_QUESTION_COMMENT OR ($ex_notify['action_type'] == self::TYPE_COMMENT_AT_ME AND $ex_notify['data']['comment_type'] == 1))
					{
						$comment_type[] = 'question';
					}

					if ($ex_notify['action_type'] == self::TYPE_ARTICLE_NEW_COMMENT OR $ex_notify['action_type'] == self::TYPE_ARTICLE_COMMENT_AT_ME)
					{
						$comment_type[] = 'article';
					}

					if ($ex_notify['data']['item_id'])
					{
						$answer_ids[] = $ex_notify['data']['item_id'];
					}

					if ($ex_notify['action_type'] == self::TYPE_REDIRECT_QUESTION)
					{
						$rf = true;
					}

					if ($ex_notify['action_type'] == self::TYPE_MOD_QUESTION)
					{
						$column_log = true;
					}

					if ($ex_notify['data']['anonymous'])
					{
						$anonymous = true;
					}
				}

				if (! $rf)
				{
					$querys[] = 'rf=false';
				}

				$querys[] = 'notification_id=' . implode(',', $notification_ids);

				if ($column_log)
				{
					$querys[] = 'column=log';
				}

				if (!($ex_notify['action_type'] == self::TYPE_ARTICLE_NEW_COMMENT OR $ex_notify['action_type'] == self::TYPE_ARTICLE_COMMENT_AT_ME) AND $comment_type)
				{
					if (count(array_unique($comment_type)) == 1)
					{
						$querys[] = 'comment_unfold=' . array_pop($comment_type);
					}
					else if (count(array_unique($comment_type)) == 2)
					{
						$querys[] = 'comment_unfold=all';
					}
				}

				if ($answer_ids)
				{
					$answer_ids = array_unique($answer_ids);

					asort($answer_ids);

					$querys[] = 'item_id=' . implode(',', $answer_ids) . '#!answer_' . array_pop($answer_ids);
				}

				if ($ex_notify['action_type'] == self::TYPE_ARTICLE_NEW_COMMENT OR $ex_notify['action_type'] == self::TYPE_ARTICLE_COMMENT_AT_ME)
				{
					$url = 'article/' . $val[0]['data']['article_id'] . '?' . implode('&', $querys);
				}
				else
				{
					$url = 'question/' . $val[0]['data']['question_id'] . '?' . implode('&', $querys);
				}

				$tmp_data['users'][$uid] = array(
					'username' => $anonymous ? AWS_APP::lang()->_t('匿名用户') : $user_infos[$uid]['user_name'],
					'url' => $url
				);
			}

			$ex_details[$action_type] = $tmp_data;
		}

		return $ex_details;
	}

	/**
	 * 检查指定用户的通知设置
	 */
	public function check_notification_setting($recipient_uid, $action_type)
	{
		if (!in_array($action_type, $this->notify_actions))
		{
			return false;
		}

		$notification_setting = $this->model('account')->get_notification_setting_by_uid($recipient_uid);

		// 默认不认置则全部都发送
		if (!$notification_setting['data'])
		{
			return true;
		}

		if (in_array($action_type, $notification_setting['data']))
		{
			return false;
		}

		return true;
	}

	/**
	 *
	 * 阅读段信息
	 * @param int $notification_id 信息id
	 *
	 * @return array信息内容数组
	 */
	public function read_notification($notification_id, $uid = null)
	{
		$notification_ids = explode(',', $notification_id);

		array_walk_recursive($notification_ids, 'intval_string');

		if (count($notification_ids) == 1 AND intval($notification_id) > 0)
		{
			$notify_info = $this->get_notification_by_id($notification_id, $uid);

			$unread_notifys = $this->get_unread_notification($uid);

			if (!$notify_info OR !$unread_notifys)
			{
				return false;
			}

			$unread_extends = array();

			foreach ($unread_notifys as $key => $val)
			{
				$unread_extends[$val['model_type']][$val['source_id']][] = $val;
			}

			$notifications = $unread_extends[$notify_info['model_type']][$notify_info['source_id']];

			$notification_ids = array();

			if (!$notifications)
			{
				$notification_ids[] = $notification_id;
			}
			else
			{
				foreach ($notifications as $key => $val)
				{
					$notification_ids[] = $val['notification_id'];
				}
			}
		}

		if ($notification_ids)
		{
			foreach($notification_ids as $key => $val)
			{
				if (!is_digits($val))
				{
					return false;
				}

				$notification_ids[$key] = intval($val);
			}

			$this->update('notification', array(
				'read_flag' => 1
			), 'recipient_uid = ' . intval($uid) . ' AND notification_id IN (' . implode(',', $notification_ids) . ')');

			$this->model('account')->update_notification_unread($uid);

			return true;
		}
	}

	public function mark_read_all($uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), 'recipient_uid = ' . intval($uid));

		$this->model('account')->update_notification_unread($uid);

		return true;
	}

	public function delete_notify($where)
	{
		if (!$where)
		{
			return false;
		}

		$this->query('DELETE FROM ' . get_table('notification_data') . ' WHERE notification_id IN (SELECT notification_id FROM ' . get_table('notification') . ' WHERE ' . $where . ')');

		return $this->delete('notification', $where);
	}

	function get_notification_list($recipient_uid, $read_flag = null, $limit = null)
	{
		if (!$recipient_uid)
		{
			return false;
		}

		$where[] = 'recipient_uid = ' . intval($recipient_uid);

		if (isset($read_flag))
		{
			$where[] = 'read_flag = ' . intval($read_flag);
		}

		if ($read_flag == 0)
		{
			$sql = "SELECT MAX(notification_id) AS notification_id FROM " . get_table('notification') . " WHERE " . implode(' AND ', $where) . " GROUP BY model_type, source_id ORDER BY notification_id DESC";
		}
		else
		{
			$sql = "SELECT MAX(notification_id) AS notification_id FROM " . get_table('notification') . " WHERE " . implode(' AND ', $where) . " GROUP BY model_type, source_id, sender_uid, action_type ORDER BY read_flag ASC, notification_id DESC";
		}

		if ($result = $this->query_all($sql, $limit))
		{
			foreach ($result as $val)
			{
				$notification_ids[] = $val['notification_id'];
			}
		}

		return $notification_ids;
	}

	/**
	 * 获得用户未读合并通知
	 */
	function get_unread_notification($recipient_uid)
	{
		if ($notification = $this->fetch_all('notification', 'recipient_uid = ' . intval($recipient_uid) . ' AND read_flag = 0', 'notification_id DESC'))
		{
			$notification_ids = array();

			foreach($notification as $key => $val)
			{
				$notification_ids[] = $val['notification_id'];
			}

			if ($notification_data = $this->fetch_all('notification_data', "notification_id IN (" . implode(',', $notification_ids) . ')'))
			{
				foreach($notification_data as $key => $val)
				{
					$nt_data[$val['notification_id']] = $val['data'];
				}
			}

			foreach($notification as $key => $val)
			{
				$notification[$key]['data'] = unserialize($nt_data[$val['notification_id']]);
			}
		}

		return $notification;
	}

	public function get_notification_by_id($notification_id, $recipient_uid = null)
	{
		if ($notification = $this->get_notification_by_ids(array(
			$notification_id
		), $recipient_uid))
		{
			return $notification[$notification_id];
		}
	}

	public function get_notification_by_ids($notification_ids, $recipient_uid = null)
	{
		if (!is_array($notification_ids))
		{
			return false;
		}

		array_walk_recursive($notification_ids, 'intval_string');

		$where[] = 'notification_id IN (' . implode(',', $notification_ids) . ')';

		if ($recipient_uid)
		{
			$where[] = 'recipient_uid = ' . intval($recipient_uid);
		}

		if (!$notification = $this->fetch_all('notification', implode(' AND ', $where)))
		{
			return false;
		}

		foreach ($notification as $key => $val)
		{
			$notification_data[$val['notification_id']] = $val;
		}

		if ($extra_data = $this->fetch_all('notification_data', "notification_id IN (" . implode(",", $notification_ids) . ')'))
		{
			foreach($extra_data as $key => $val)
			{
				$notification_data[$val['notification_id']]['data'] = unserialize($val['data']);
			}
		}

		foreach($notification_ids as $id)
		{
			$data[$id] = $notification_data[$id];
		}

		return $data;
	}

	public function format_notification($data)
	{
		foreach ($data AS $key => $val)
		{
			if ($val['extend_count'] > 1)
			{
				switch ($val['model_type'])
				{
					case self::CATEGORY_QUESTION:
						$data[$key]['message'] = $val['extend_count'] . ' ' . AWS_APP::lang()->_t('项关于问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::CATEGORY_ARTICLE:
						$data[$key]['message'] = $val['extend_count'] . ' ' . AWS_APP::lang()->_t('项关于文章') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

				}

				foreach($val['extend_details'] AS $action_type => $extend)
				{
					unset($users_list);

					if ($extend['users'])
					{
						foreach($extend['users'] AS $user)
						{
							$users_list .= '<a href="' . $user['url'] . '">' . $user['username'] . '</a>, ';
						}

						$users_list = substr($users_list, 0, -2);
					}

					switch ($action_type)
					{
						case self::TYPE_ANSWER_AT_ME:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('他们在回答中提到了你') . ': ' . $users_list;
							break;

						case self::TYPE_COMMENT_AT_ME:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('他们在问题中的评论提到了你') . ': ' . $users_list;

							break;

						case self::TYPE_ANSWER_COMMENT_AT_ME:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('他们在回答中的评论提到了你') . ': ' . $users_list;

							break;

						case self::TYPE_ARTICLE_COMMENT_AT_ME:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('他们在文章中的评论提到了你') . ': ' . $users_list;

							break;

						case self::TYPE_ARTICLE_NEW_COMMENT:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个新回复, 按评论人查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_NEW_ANSWER:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个新回复, 按回答人查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_ANSWER_COMMENT:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个新评论, 按评论人查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_ANSWER_AGREE:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个新赞同, 按赞同者查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_ANSWER_THANK:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个新感谢, 按感谢者查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_MOD_QUESTION:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 次编辑问题, 按编辑者查看', $extend['count']) . ': ' . $users_list;

							break;

						case self::TYPE_REDIRECT_QUESTION:
							$data[$key]['extend_message'][] = $users_list . ' ' . AWS_APP::lang()->_t('重定向了你发布的问题');

							break;

						case self::TYPE_REMOVE_ANSWER:
							$data[$key]['extend_message'][] = AWS_APP::lang()->_t('%s 个回复被删除', $extend['count']);

							break;

						case self::TYPE_INVITE_QUESTION:
							$data[$key]['extend_message'][] = $users_list . ' ' . AWS_APP::lang()->_t('邀请你参与问题');

							break;
					}
				}
			}
			else
			{
				switch ($val['action_type'])
				{
					case self::TYPE_PEOPLE_FOCUS:
						$data[$key]['message'] = '<a href="' . $val['key_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('关注了你');

						break;

					case self::TYPE_NEW_ANSWER:
						if ($val['anonymous'])
						{
							$data[$key]['message'] = AWS_APP::lang()->_t('匿名用户');
						}
						else
						{
							$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a>';
						}

						$data[$key]['message'] .= ' ' . AWS_APP::lang()->_t('回复了问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_ARTICLE_NEW_COMMENT:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('评论了文章') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_COMMENT_AT_ME:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的评论提到了你');

						break;

					case self::TYPE_ARTICLE_COMMENT_AT_ME:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('在文章') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('评论中回复了你');

						break;

					case self::TYPE_ANSWER_AT_ME:
						if ($val['anonymous'])
						{
							$data[$key]['message'] = AWS_APP::lang()->_t('匿名用户');
						}
						else
						{
							$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a>';
						}

						$data[$key]['message'] .= ' ' . AWS_APP::lang()->_t('在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的回答提到了你');

						break;

					case self::TYPE_ANSWER_COMMENT_AT_ME:
						if ($val['anonymous'])
						{
							$data[$key]['message'] = AWS_APP::lang()->_t('匿名用户');
						}
						else
						{
							$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a>';
						}

						$data[$key]['message'] .= ' ' . AWS_APP::lang()->_t('在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('回答评论中提到了你');
					break;

					case self::TYPE_INVITE_QUESTION:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('邀请你参与问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_ANSWER_COMMENT:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('评论了你在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的回复');

						break;

					case self::TYPE_QUESTION_COMMENT:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('评论了你发起的问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_ANSWER_AGREE:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('赞同了你在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的回复');

						break;

					case self::TYPE_ANSWER_THANK:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('感谢了你在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的回复');

						break;

					case self::TYPE_MOD_QUESTION:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('编辑了你发布的问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_REMOVE_ANSWER:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('删除了你在问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a> ' . AWS_APP::lang()->_t('中的回复');

						break;

					case self::TYPE_REDIRECT_QUESTION:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('重定向了你发起的问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_QUESTION_THANK:
						$data[$key]['message'] = '<a href="' . $val['p_url'] . '">' . $val['p_user_name'] . '</a> ' . AWS_APP::lang()->_t('感谢了你发起的问题') . ' <a href="' . $val['key_url'] . '">' . $val['title'] . '</a>';

						break;

					case self::TYPE_CONTEXT:
						$data[$key]['message'] = $val['content'];

						break;

					case self::TYPE_ARTICLE_APPROVED:
						$data[$key]['message'] = AWS_APP::lang()->_t('你发起的文章 %s 审核通过', '<a href="' . $val['key_url'] . '">' . $val['title'] . '</a>');

						break;

					case self::TYPE_ARTICLE_REFUSED:
						$data[$key]['message'] = AWS_APP::lang()->_t('你发起的文章 %s 审核未通过', $val['title']);

						break;

					case self::TYPE_QUESTION_APPROVED:
						$data[$key]['message'] = AWS_APP::lang()->_t('你发起的问题 %s 审核通过', '<a href="' . $val['key_url'] . '">' . $val['title'] . '</a>');

						break;

					case self::TYPE_QUESTION_REFUSED:
						$data[$key]['message'] = AWS_APP::lang()->_t('你发起的问题 %s 审核未通过', $val['title']);

						break;
				}
			}
		}

		return $data;
	}
}
