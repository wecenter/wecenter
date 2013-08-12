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

class reputation_class extends AWS_MODEL
{
	public function get_reputation_topic($uids)
	{
		if (!is_array($uids))
		{
			$uids = array(
				$uids
			);
		}
		
		array_walk_recursive($uids, 'intval_string');
		
		return $this->fetch_all('reputation_topic', 'uid IN (' . implode(',', $uids) . ')', 'topic_count DESC');
	}
	
	public function calculate_by_uid($uid)
	{
		if (!$user_info = $this->model('account')->get_user_info_by_uid($uid))
		{
			return false;
		}
		
		if ($user_info['reputation_update_time'] > time() - 1800)
		{
			return false;
		}
		
		if ($users_anwsers = $this->query_all("SELECT answer_id, question_id, agree_count, thanks_count FROM " . get_table('answer') . " WHERE uid = " . $uid))
		{				
			foreach ($users_anwsers as $anwsers_key => $answers_val)
			{
				$answer_ids[] = $answers_val['answer_id'];
				$question_ids[] = $answers_val['question_id'];
			}
						
			if ($question_ids)
			{
				if ($questions_info_query = $this->query_all("SELECT question_id, best_answer, published_uid, category_id FROM " . get_table('question') . " WHERE question_id IN (" . implode(',', $question_ids) . ")"))
				{
					foreach ($questions_info_query AS $questions_info_key => $questions_info_val)
					{
						$questions_info[$questions_info_val['question_id']] = $questions_info_val;
					}
							
					unset($questions_info_query);
				}
							
				if ($question_topics_query = $this->query_all("SELECT question_id, topic_id FROM " . get_table('topic_question') . " WHERE question_id IN (" . implode(',', $question_ids) . ")"))
				{
					foreach ($question_topics_query AS $question_topics_key => $question_topics_val)
					{
						$question_topics[$question_topics_val['question_id']][] = $question_topics_val;
					}
							
					unset($question_topics_query);
				}
			}
						
			if ($answer_ids)
			{
				$vote_agree_users = $this->model('answer')->get_vote_agree_by_answer_ids($answer_ids);
				$vote_against_users = $this->model('answer')->get_vote_against_by_answer_ids($answer_ids);
			}
						
			foreach ($users_anwsers as $answers_key => $answers_val)
			{	
				$answer_id = $answers_val['answer_id'];
				$question_id = $answers_val['question_id'];
							
				if (!$questions_info[$question_id])
				{
					continue;
				}
							
				$answer_reputation = 0;	// 回复威望系数
							
				$s_publisher_agree = 0;	// 得到发起者赞同
				$s_publisher_against = 0;	// 得道发起者反对
							
				$s_agree_value = 0;	// 赞同威望系数
				$s_against_value = 0;	// 反对威望系数
							
				// 是否最佳回复
				if ($questions_info[$question_id]['best_answer'] == $answer_id)
				{
					$s_best_answer = 1;
				}
				else
				{
					$s_best_answer = 0;
				}
							
				// 赞同的用户
				if ($vote_agree_users[$answer_id])
				{
					// 排除发起者
					if ($questions_info[$question_id]['published_uid'] == $vote_agree_users[$answer_id]['answer_uid'])
					{
						continue;
					}
									
					$s_agree_value = $s_agree_value + $vote_agree_users[$answer_id]['reputation_factor'];
									
					if ($questions_info[$question_id]['published_uid'] == $vote_agree_users[$answer_id]['vote_uid'])
					{
						$s_publisher_agree = 1;
					}
				}
							
				// 反对的用户
				if ($vote_against_users[$answer_id])
				{
					// 排除发起者
					if ($questions_info[$question_id]['published_uid'] == $vote_against_users[$answer_id]['answer_uid'])
					{
						continue;
					}
									
					$s_against_value = $s_against_value + $vote_against_users[$answer_id]['reputation_factor'];
									
					if ($questions_info[$question_id]['published_uid'] == $vote_against_users[$answer_id]['vote_uid'])
					{
						$s_publisher_against = 1;
					}
				}
							
				if ($s_publisher_agree)
				{
					$s_agree_value = $s_agree_value - 1;
				}
							
				if ($s_publisher_against)
				{
					$s_against_value = $s_against_value - 1;
				}
				
				$best_answer_reput = get_setting('best_answer_reput');	// 最佳回复威望系数
				$publisher_reputation_factor = get_setting('publisher_reputation_factor');	// 发起者赞同/反对威望系数
				$reputation_log_factor = get_setting('reputation_log_factor');
				
				//（用户组威望系数x赞同数量-用户组威望系数x反对数量）+ 发起者赞同反对系数 + 最佳答案系数
				$answer_reputation = $s_agree_value - $s_against_value + ($s_publisher_agree * $publisher_reputation_factor) - ($s_publisher_against * $publisher_reputation_factor) + ($s_best_answer * $best_answer_reput);
							
				if ($answer_reputation < 1)
				{
					$answer_reputation = 0;
				}
				else
				{
					$answer_reputation = $answer_reputation + 0.5;
					$answer_reputation = log($answer_reputation, $reputation_log_factor);
				}
							
				// 计算在话题中的威望
				if ($answer_reputation)
				{
					if ($question_topics[$question_id])
					{
						foreach ($question_topics[$question_id] as $key => $topic_info)
						{								
							$count = intval($user_topics[$topic_info['topic_id']]['count']) + 1;
										
							$agree_count = intval($user_topics[$topic_info['topic_id']]['agree_count']) + $answers_val['agree_count'];
							$thanks_count = intval($user_topics[$topic_info['topic_id']]['thanks_count']) + $answers_val['thanks_count'];
							$reputation = $user_topics[$topic_info['topic_id']]['reputation'] + $answer_reputation;
							$best_answer_count_all = intval($user_topics[$topic_info['topic_id']]['best_answer_count']) + intval($s_best_answer);
										
							$user_topics[$topic_info['topic_id']] = array(
								'topic_id' => $topic_info['topic_id'], 
								'count' => $count, 
								'agree_count' => $agree_count, 
								'thanks_count' => $thanks_count, 
								'reputation' => $reputation, 
								'best_answer_count' => $best_answer_count_all
							);
						}
					}
				}
							
				if ($questions_info[$question_id]['category_id'])
				{
					$user_reputation_category[$questions_info[$question_id]['category_id']]['reputation'] += $answer_reputation;
					$user_reputation_category[$questions_info[$question_id]['category_id']]['agree_count'] += $answers_val['agree_count'];
					$user_reputation_category[$questions_info[$question_id]['category_id']]['questions'][$answers_val['question_id']] = $answers_val['question_id'];
				}
							
				$user_reputation = $user_reputation + $answer_reputation;
			}
						
			if (is_array($user_topics))
			{
				if ($user_topics = aasort($user_topics, 'count', 'DESC'))
				{
					$user_topics = array_slice($user_topics, 0, 20);
				}
						
				foreach ($user_topics as $t_key => $t_val)
				{
					$this->delete('reputation_topic', 'uid = ' . $uid . ' AND topic_id = ' . $t_val['topic_id']);
								
					$this->insert('reputation_topic', array(
						'uid' => $uid,
						'topic_id' => $t_val['topic_id'],
						'topic_count' => $t_val['count'],
						'update_time' => time(),
						'agree_count' => $t_val['agree_count'],
						'thanks_count' => $t_val['thanks_count'],
						'best_answer_count' => $t_val['best_answer_count'],
						'reputation' => round($t_val['reputation'])
					));
				}
			}
					
			if (is_array($user_reputation_category))
			{
				foreach ($user_reputation_category as $t_key => $t_val)
				{
					$this->delete('reputation_category', 'uid = ' . $uid . ' AND category_id = ' . $t_key);
		
					$this->insert('reputation_category', array(
						'uid' => $uid,
						'update_time' => time(),
						'category_id' => $t_key,
						'reputation' => round($t_val['reputation']),
						'agree_count' => $t_val['agree_count'],
						'question_count' => count($t_val['questions'])
					));
				}
			}
					
			$this->model('account')->update_users_fields(array(
				'reputation' => round($user_reputation), 
				'reputation_update_time' => time()
			), $uid);
					
			$this->model('account')->update_user_reputation_group($uid);
		}
		else
		{
			$this->model('account')->update_users_fields(array(
				'reputation' => 0, 
				'reputation_update_time' => time()
			), $uid);
						
			$this->model('account')->update_user_reputation_group($uid);
		}
	}

	public function calculate($start = 0, $limit = 100)
	{
		if ($users_list = $this->query_all("SELECT uid FROM " . get_table('users') . " ORDER BY uid ASC", $start . ',' . $limit))
		{
			foreach ($users_list as $key => $val)
			{
				$this->calculate_by_uid($val['uid']);
			}
			
			return true;
		}
		
		return false;
	}
}