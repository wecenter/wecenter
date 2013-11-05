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

class ACTION_LOG
{
	const CATEGORY_QUESTION = 1;	// 问题
	
	const CATEGORY_ANSWER = 2;	// 回答
	
	const CATEGORY_COMMENT = 3;	// 评论

	const CATEGORY_TOPIC = 4;	// 话题 
	
	
	const ADD_QUESTION = 101;	// 添加问题
	
	const MOD_QUESTON_TITLE = 102;	// 修改问题标题
	
	const MOD_QUESTION_DESCRI = 103;	// 修改问题描述 
	
	const ADD_REQUESTION_FOCUS = 105;	// 添加问题关注
	
	const DELETE_REQUESTION_FOCUS = 106;	// 删除问题关注
	
	const REDIRECT_QUESTION = 107;	// 问题重定向
	
	const MOD_QUESTION_CATEGORY = 108;	// 修改问题分类
	
	const MOD_QUESTION_ATTACH = 109;	// 修改问题附件
	
	const DEL_REDIRECT_QUESTION = 110;	// 删除问题重定向
	
	const MOD_QUESTION = 111;	// 修改问题
	
	const ANSWER_QUESTION = 201;	// 回复问题
	
	const MOD_ANSWER = 202;	// 修改回复
	
	const DELETE_ANSWER = 203;	// 删除回复 
	
	const ADD_AGREE = 204;	// 增加赞同
	
	const ADD_AGANIST = 205;	// 增加反对投票
	
	const ADD_USEFUL = 206;	// 加感谢作者
	
	const ADD_UNUSEFUL = 207;	// 问题没有帮助
	
	const DEL_AGREE = 208;	// 取消赞成
	
	const DEL_AGANIST = 209;	// 取消反对投票
	
	const ADD_COMMENT = 301;	// 增加评论
	
	const DELETE_COMMENT = 302;	// 删除评论
	
	const ADD_TOPIC = 401;	// 创建话题
	
	const MOD_TOPIC = 402;	// 修改话题
	
	const MOD_TOPIC_DESCRI = 403;	// 修改话题描述
	
	const MOD_TOPIC_PIC = 404;	// 修改话题图片
	
	const DELETE_TOPIC = 405;	// 删除话题
	
	const ADD_TOPIC_FOCUS = 406;	// 添加话题关注
	
	const DELETE_TOPIC_FOCUS = 407;	// 删除话题关注

	const ADD_RELATED_TOPIC = 410;	// 添加相关话题

	const DELETE_RELATED_TOPIC = 411;	// 删除相关话题
	
	const ADD_ARTICLE = 501;	// 添加文章
	
	
	public static function associate_fresh_action($history_id, $associate_id, $associate_type, $associate_action, $uid, $anonymous, $add_time)
	{
		AWS_APP::model()->delete('user_action_history_fresh', 'associate_id = ' . intval($associate_id) . ' AND associate_type = ' . intval($associate_type) . ' AND uid = ' . intval($uid));
		
		self::clean_fresh_action($history_id, $associate_id, $associate_type, $associate_action, $uid);
		
		return AWS_APP::model()->insert('user_action_history_fresh', array(
			'history_id' => intval($history_id),
			'associate_id' => intval($associate_id),
			'associate_type' => intval($associate_type),
			'associate_action' => intval($associate_action),
			'uid' => intval($uid),
			'anonymous' => intval($anonymous),
			'add_time' => $add_time
		));
	}
	
	public static function clean_fresh_action($history_id, $associate_id, $associate_type, $associate_action, $uid)
	{
		
	}
	
	public static function save_action($uid, $associate_id, $action_type, $action_id, $action_content = null, $action_attch = null, $add_time = null, $anonymous = null, $addon_data = null)
	{
		if (!$uid OR !$associate_id)
		{
			return false;
		}
		
		if (is_numeric($action_attch))
		{
			$action_attch_insert = $action_attch;
		}
		else
		{
			$action_attch_insert = '-1';
			$action_attch_update = $action_attch;
		}
		
		if (!$add_time)
		{
			$add_time = time();
		}
		
		$history_id = AWS_APP::model()->insert('user_action_history', array(
			'uid' => intval($uid), 
			'associate_type' => $action_type, 
			'associate_action' => $action_id, 
			'associate_id' => $associate_id, 
			'associate_attached' => $action_attch_insert,
			'add_time' => $add_time,
			'anonymous' => intval($anonymous),
		));
		
		AWS_APP::model()->insert('user_action_history_data', array(
			'history_id' => $history_id, 
			'associate_content' => htmlspecialchars($action_content), 
			'associate_attached' => htmlspecialchars($action_attch_update),
			'addon_data' => $addon_data ? serialize($addon_data) : '',
		));
		
		self::associate_fresh_action($history_id, $associate_id, $action_type, $action_id, $uid, $anonymous, $add_time);
		
		return $history_id;
	}

	/**
	 * 
	 * 根据事件 ID,得到事件列表
	 * @param boolean $count
	 * @param int     $event_id
	 * @param int     $limit
	 * @param int     $action_type
	 * @param int     $action_id
	 * 
	 * @return array
	 */
	public static function get_action_by_event_id($event_id = 0, $limit = 20, $action_type = null, $action_id = null)
	{
		if ($event_id)
		{
			$where[] = 'associate_id = ' . intval($event_id);
		}
		
		if ($action_type)
		{
			$where[] = 'associate_type IN (' . $action_type . ')';
		}
		
		if ($action_id)
		{
			$where[] = 'associate_action IN (' . $action_id . ')';
		}
		else
		{
			$where[] = 'associate_action NOT IN (' . implode(',', array(				
				self::ADD_REQUESTION_FOCUS,
				self::DELETE_REQUESTION_FOCUS,
				self::DELETE_ANSWER,
				self::ADD_AGREE,
				self::ADD_AGANIST,
				self::ADD_USEFUL,
				self::ADD_UNUSEFUL,
				self::DEL_AGREE,
				self::DEL_AGANIST,
			)) . ')';
		}
		
		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', implode(' AND ', $where), 'add_time DESC', $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
				
			$actions_data = self::get_action_data_by_history_ids($history_ids);
			
			foreach ($user_action_history AS $key => $val)
			{
				$user_action_history[$key]['addon_data'] = $actions_data[$val['history_id']]['addon_data'];
				$user_action_history[$key]['associate_content'] = $actions_data[$val['history_id']]['associate_content'];
				
				if ($val['associate_attached'] == -1)
				{
					$user_action_history[$key]['associate_attached'] = $actions_data[$val['history_id']]['associate_attached'];
				}
			}
		}
			
		return $user_action_history;
	}
	
	public static function get_action_data_by_history_ids($history_ids)
	{
		if ($action_data = AWS_APP::model()->fetch_all('user_action_history_data', 'history_id IN(' . implode(',', $history_ids) . ')'))
		{
			foreach ($action_data AS $key => $val)
			{
				if ($val['addon_data'])
				{
					$val['addon_data'] = unserialize($val['addon_data']);
				}
				
				$result[$val['history_id']] = $val;
			}
		}
		
		return $result;
	}
	
	public static function get_action_data_by_history_id($history_id)
	{
		return AWS_APP::model()->fetch_row('user_action_history_data', 'history_id = ' . intval($history_id));
	}

	public static function get_action_by_history_id($history_id)
	{
		if ($action_history = AWS_APP::model()->fetch_row('user_action_history', 'history_id = ' . intval($history_id)))
		{
			$action_history_data = self::get_action_data_by_history_id($action_history['history_id']);
			
			$action_history['associate_content'] = $action_history_data['associate_content'];
			
			if ($action_history['associate_attached'] == -1)
			{
				$action_history['associate_attached'] = $action_history_data['associate_attached'];
			}
		}
		
		return $action_history;
	}

	public static function update_action_time_by_history_id($history_id)
	{
		return AWS_APP::model()->update('user_action_history', array(
			'add_time' => time()
		), 'history_id = ' . intval($history_id));
	}
	
	public static function get_action_by_where($where = null, $limit = 20, $show_anonymous = false, $order = 'add_time DESC')
	{
		if (! $where)
		{
			return false;
		}
		
		$where = '(' . $where . ') AND fold_status = 0';
		
		if (!$show_anonymous)
		{
			$where = '(' . $where . ') AND anonymous = 0';
		}
		
		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', $where, $order, $limit))
		{			
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
				
			$actions_data = self::get_action_data_by_history_ids($history_ids);
				
			foreach ($user_action_history AS $key => $val)
			{
				$user_action_history[$key]['associate_content'] = $actions_data[$val['history_id']]['associate_content'];
				
				if ($val['associate_attached'] == -1)
				{
					$user_action_history[$key]['associate_attached'] = $actions_data[$val['history_id']]['associate_attached'];
				}
			}
		}
		
		return $user_action_history;
	}
		
	public static function get_actions_fresh_by_where($where = null, $limit = 20, $show_anonymous = false)
	{
		if (!$where)
		{
			return false;
		}
			
		if (!$show_anonymous)
		{
			$where = '(' . $where . ') AND anonymous = 0';
		}
		
		if ($action_history = AWS_APP::model()->query_all("SELECT history_id FROM " . get_table('user_action_history_fresh') . " WHERE " . $where . " ORDER BY add_time DESC", $limit))
		{
			foreach ($action_history as $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
			
			if ($action_history = self::get_action_by_where('history_id IN(' . implode(',', $history_ids) . ')', null, null, null))
			{
				foreach ($action_history as $key => $val)
				{
					$last_history[$val['history_id']] = $action_history[$key];
				}
				
				krsort($last_history);
						
				return $last_history;
			}	
		}
	}
	
	public static function format_action_data($action, $uid = 0, $user_name = null, $question_info = null, $topic_info = null)
	{		
		$user_attr = 'class="aw-user-name" data-id="' . $uid . '"';
		$user_url = 'people/' . $uid;
		
		if ($topic_info)
		{
			$topic_attr = 'class="aw-topic-name" data-id="' . $topic_info['topic_id'] . '"';
		}
		
		if ($topic_info AND $topic_info['url_token'])
		{
			$topic_url = 'topic/' . $topic_info['url_token'];
		}
		else
		{
			$topic_url = 'topic/' . $topic_info['topic_id'];
		}
		
		switch ($action)
		{
			case ACTION_LOG::ADD_QUESTION : // '添加问题',
				if ($question_info['anonymous'])
				{
					$action_string = AWS_APP::lang()->_t('匿名用户') . ' ' . AWS_APP::lang()->_t('发起了问题');
				}
				else
				{
					$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('发起了问题');
				}
				break;
				
			case ACTION_LOG::MOD_QUESTON_TITLE : //'修改问题标题',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了问题标题');
				break;
				
			case ACTION_LOG::MOD_QUESTION_DESCRI : // '修改问题描述',	
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了问题');
				break;
				
			case ACTION_LOG::ADD_REQUESTION_FOCUS : // '添加问题关注',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('关注了该问题');;
				break;
				
			case ACTION_LOG::DELETE_REQUESTION_FOCUS : // '删除问题关注',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('取消关注了该问题');
				break;
				
			case ACTION_LOG::ANSWER_QUESTION : // '回复问题',
				if ($topic_info)
				{
					$action_string = '<a href="' . $topic_url . '" ' . $topic_attr . '>' . $topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题添加了一个问题回复');
				}
				else if ($user_name)
				{
					$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('回复了问题');
				}
				else
				{
					$action_string = AWS_APP::lang()->_t('该问题增加了一个回复');
				}
				break;
			
			case ACTION_LOG::MOD_ANSWER : // '修改回复',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改了回复');
				break;
				
			case ACTION_LOG::ADD_AGREE : // '增加赞同',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('赞同了该回复');
				break;
			
			case ACTION_LOG::ADD_COMMENT : // '增加评论',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('发表了评论');
				break;
				
			case ACTION_LOG::DELETE_COMMENT : // '删除评论',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('删除了评论');
				break;
			
			case ACTION_LOG::ADD_TOPIC : // '添加话题',
				if ($topic_info AND $user_name)
				{
					if (isset($topic_info[0]))
					{
						$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('将该问题添加到');
						
						foreach ($topic_info as $key => $val)
						{
							if ($val['url_token'])
							{
								$action_string .= ' <a href="topic/' . $val['url_token'] . '" ' . $topic_attr . '>' . $val['topic_title'] . '</a> ';
							}
							else
							{
								$action_string .= ' <a href="topic/' . $val['topic_id'] . '" ' . $topic_attr . '>' . $val['topic_title'] . '</a> ';
							}
							
							if ($key > 2)
							{
								break;
							}
						}
						
						if (sizeof($topic_info) > 3)
						{
							$action_string .= AWS_APP::lang()->_t('等') . ' ';
						}
						
						$action_string .= AWS_APP::lang()->_t('话题');
					}
					else
					{
						$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('将该问题添加到') . ' <a href="' . $topic_url . '" ' . $topic_attr . '>' . $topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题');
					
					}
				
				}
				else if ($topic_info)
				{
					if (isset($topic_info[0]))
					{
						$action_string = AWS_APP::lang()->_t('该问题被添加到');
						
						foreach ($topic_info as $key => $val)
						{
							if ($val['url_token'])
							{
								$action_string .= ' <a href="topic/' . $val['url_token'] . ' ' . $topic_attr . '>' . $val['topic_title'] . '</a> ';
							}
							else
							{
								$action_string .= ' <a href="topic/' . $val['topic_id'] . ' ' . $topic_attr . '>' . $val['topic_title'] . '</a> ';
							}
							
							if ($key > 2)
							{
								break;
							}
						}
						
						if (sizeof($topic_info) > 3)
						{
							$action_string .= AWS_APP::lang()->_t('等');
						}
						
						$action_string .= AWS_APP::lang()->_t('话题');
					}
					else
					{
						$action_string = AWS_APP::lang()->_t('该问题被添加到') . ' <a href="' . $topic_url . '" ' . $topic_attr . '>' . $topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题');
					}
				}
				else if ($user_name)
				{
					$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('添加了一个话题');
				}
				else
				{
					$action_string = AWS_APP::lang()->_t('该问题添加了一个话题');
				}
				break;
			
			case ACTION_LOG::MOD_TOPIC : // '修改话题',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改话题');
				break;
			
			case ACTION_LOG::MOD_TOPIC_DESCRI : // '修改话题描述',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改话题描述');
				break;
			
			case ACTION_LOG::MOD_TOPIC_PIC : // '修改话题缩略图',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('修改话题图片');
				break;
			
			case ACTION_LOG::DELETE_TOPIC : // '删除话题',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('删除话题');
				break;
			
			case ACTION_LOG::ADD_TOPIC_FOCUS : // '关注话题',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('关注话题');
				break;
			
			case ACTION_LOG::DELETE_TOPIC_FOCUS : // '取消话题关注',
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('取消话题关注');
				break;
				
			case ACTION_LOG::ADD_ARTICLE :
				$action_string = '<a href="' . $user_url . '" ' . $user_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('发表了文章');
				break;
		}
		
		return $action_string;
	}
	
	public static function delete_action_history($where)
	{
		if ($action_history = AWS_APP::model()->fetch_all('user_action_history', $where))
		{
			foreach ($action_history AS $key => $val)
			{
				AWS_APP::model()->delete('user_action_history_data', 'history_id = ' . $val['history_id']);
				AWS_APP::model()->delete('user_action_history_fresh', 'history_id = ' . $val['history_id']);
			}
			
			$action_history = AWS_APP::model()->delete('user_action_history', $where);
		}
	}
	
	public static function set_fold_action_history($answer_id, $fold = 1)
	{
		AWS_APP::model()->update('user_action_history', array(
			'fold_status' => $fold
		), 'associate_type = ' . self::CATEGORY_ANSWER . ' AND associate_id = ' . intval($answer_id));
		
		AWS_APP::model()->update('user_action_history', array(
			'fold_status' => $fold
		), 'associate_type = ' . self::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id));
		
		if ($fold == 1)
		{
			if ($action_history = AWS_APP::model()->fetch_all('user_action_history', 'associate_type IN(' . self::CATEGORY_QUESTION . ',' . self::CATEGORY_ANSWER . ') AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id)))
			{
				foreach ($action_history AS $key => $val)
				{
					AWS_APP::model()->delete('user_action_history_fresh', 'history_id = ' . $val['history_id']);
				}
			}
		}
		
		return $fold;
	}
}
