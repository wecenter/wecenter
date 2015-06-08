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

class reputation_class extends AWS_MODEL
{
	public function get_reputation_topic($uids)
	{
		if (!is_array($uids))
		{
			return false;
		}

		array_walk_recursive($uids, 'intval_string');

		return $this->fetch_all('reputation_topic', 'uid IN(' . implode(',', $uids) . ')', 'topic_count DESC');
	}

	public function calculate_by_uid($uid)
	{
		if (!$user_info = $this->model('account')->get_user_info_by_uid($uid))
		{
			return false;
		}

		if ($user_articles = $this->query_all('SELECT id FROM ' . get_table('article') . ' WHERE uid = ' . $user_info['uid']))
		{
			foreach ($user_articles as $articles_key => $articles_val)
			{
				$articles_ids[] = $articles_val['id'];
			}

			if ($articles_ids)
			{
				$articles_vote_agree_users = $this->model('article')->get_article_vote_by_ids('article', $articles_ids, 1);
				$articles_vote_against_users = $this->model('article')->get_article_vote_by_ids('article', $articles_ids, -1);

				if ($article_topics_query = $this->query_all('SELECT item_id, topic_id FROM ' . get_table('topic_relation') . ' WHERE item_id IN(' . implode(',', $articles_ids) . ") AND `type` = 'article'"))
				{
					foreach ($article_topics_query AS $article_topics_key => $article_topics_val)
					{
						$article_topics[$article_topics_val['item_id']][] = $article_topics_val;
					}

					unset($article_topics_query);
				}
			}	
			foreach ($user_articles as $articles_key => $articles_val)
			{
				// 赞同的用户
				if ($articles_vote_agree_users[$articles_val['id']])
				{
					foreach($articles_vote_agree_users[$articles_val['id']] AS $articles_vote_agree_user)
					{
						$s_agree_value = $s_agree_value + $articles_vote_agree_user['reputation_factor'];
					}
				}

				// 反对的用户
				if ($articles_vote_against_users[$articles_val['id']])
				{
					foreach($articles_vote_against_users[$articles_val['id']] AS $articles_vote_against_user)
					{
						$s_against_value = $s_against_value + $articles_vote_against_user['reputation_factor'];
					}
				}
			}
						$article_reputation = $s_agree_value - $s_against_value;			
			$reputation_log_factor = get_setting('reputation_log_factor');

			if ($article_reputation < 0)
			{
				$article_reputation = (0 - $article_reputation) - 0.5;

				if ($reputation_log_factor > 1)
				{
					$article_reputation = (0 - log($article_reputation, $reputation_log_factor));
				}
			}
			else if ($article_reputation > 0)
			{
				$article_reputation = $article_reputation + 0.5;

				if ($reputation_log_factor > 1)
				{
					$article_reputation = log($article_reputation, $reputation_log_factor);
				}
			}

			// 计算在话题中的威望
			if ($article_reputation)
			{
				if ($article_topics[$articles_val['id']])
				{
					foreach ($article_topics[$articles_val['id']] as $key => $topic_info)
					{
						$user_topics[$topic_info['topic_id']] = array(
							'topic_id' => $topic_info['topic_id'],
							'count' => (intval($user_topics[$topic_info['topic_id']]['count']) + 1),
							'agree_count' => (intval($user_topics[$topic_info['topic_id']]['agree_count']) + sizeof($articles_vote_agree_users[$articles_val['id']])),
							'thanks_count' => 0,
							'reputation' => ($user_topics[$topic_info['topic_id']]['reputation'] + $article_reputation)
						);
					}
				}
			}

			$user_reputation = $user_reputation + $article_reputation;
		}

		if ($users_anwsers = $this->query_all('SELECT answer_id, question_id, agree_count, thanks_count FROM ' . get_table('answer') . ' WHERE uid = ' . $user_info['uid']))
		{
			foreach ($users_anwsers as $anwsers_key => $answers_val)
			{
				$answer_ids[] = $answers_val['answer_id'];
				$question_ids[] = $answers_val['question_id'];
			}

			if ($question_ids)
			{
				if ($questions_info_query = $this->query_all('SELECT question_id, best_answer, published_uid, category_id FROM ' . get_table('question') . ' WHERE question_id IN(' . implode(',', $question_ids) . ')'))
				{
					foreach ($questions_info_query AS $questions_info_key => $questions_info_val)
					{
						$questions_info[$questions_info_val['question_id']] = $questions_info_val;
					}

					unset($questions_info_query);
				}

				if ($question_topics_query = $this->query_all('SELECT item_id, topic_id FROM ' . get_table('topic_relation') . ' WHERE item_id IN(' . implode(',', $question_ids) . ") AND `type` = 'question'"))
				{
					foreach ($question_topics_query AS $question_topics_key => $question_topics_val)
					{
						$question_topics[$question_topics_val['item_id']][] = $question_topics_val;
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
				if (!$questions_info[$answers_val['question_id']])
				{
					continue;
				}

				$answer_reputation = 0;	// 回复威望系数

				$s_publisher_agree = 0;	// 得到发起者赞同
				$s_publisher_against = 0;	// 得道发起者反对

				$s_agree_value = 0;	// 赞同威望系数
				$s_against_value = 0;	// 反对威望系数

				// 是否最佳回复
				if ($questions_info[$answers_val['question_id']]['best_answer'] == $answers_val['answer_id'])
				{
					$s_best_answer = 1;
				}
				else
				{
					$s_best_answer = 0;
				}

				// 赞同的用户
				if ($vote_agree_users[$answers_val['answer_id']])
				{
					foreach ($vote_agree_users[$answers_val['answer_id']] AS $key => $val)
					{
						// 排除发起者
						if ($questions_info[$answers_val['question_id']]['published_uid'] != $val['answer_uid'])
						{
							$s_agree_value = $s_agree_value + $val['reputation_factor'];

							if ($questions_info[$answers_val['question_id']]['published_uid'] == $val['vote_uid'] AND !$s_publisher_agree)
							{
								$s_publisher_agree = 1;
							}
						}
					}
				}

				// 反对的用户
				if ($vote_against_users[$answers_val['answer_id']])
				{
					foreach ($vote_against_users[$answers_val['answer_id']] AS $key => $val)
					{
						// 排除发起者
						if ($questions_info[$answers_val['question_id']]['published_uid'] != $val['answer_uid'])
						{
							$s_against_value = $s_against_value + $val['reputation_factor'];

							if ($questions_info[$answers_val['question_id']]['published_uid'] == $val['vote_uid'] AND !$s_publisher_against)
							{
								$s_publisher_against = 1;
							}
						}
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

				//（用户组威望系数 x 赞同数量 - 用户组威望系数 x 反对数量）+ 发起者赞同反对系数 + 最佳答案系数
				$answer_reputation = $s_agree_value - $s_against_value + ($s_publisher_agree * $publisher_reputation_factor) - ($s_publisher_against * $publisher_reputation_factor) + ($s_best_answer * $best_answer_reput);

				if ($answer_reputation < 0)
				{
					$answer_reputation = (0 - $answer_reputation) - 0.5;

					if ($reputation_log_factor > 1)
					{
						$answer_reputation = (0 - log($answer_reputation, $reputation_log_factor));
					}
				}
				else if ($answer_reputation > 0)
				{
					$answer_reputation = $answer_reputation + 0.5;

					if ($reputation_log_factor > 1)
					{
						$answer_reputation = log($answer_reputation, $reputation_log_factor);
					}
				}

				// 计算在话题中的威望
				if ($answer_reputation)
				{
					if ($question_topics[$answers_val['question_id']])
					{
						foreach ($question_topics[$answers_val['question_id']] as $key => $topic_info)
						{
							$user_topics[$topic_info['topic_id']] = array(
								'topic_id' => $topic_info['topic_id'],
								'count' => (intval($user_topics[$topic_info['topic_id']]['count']) + 1),
								'agree_count' => (intval($user_topics[$topic_info['topic_id']]['agree_count']) + $answers_val['agree_count']),
								'thanks_count' => (intval($user_topics[$topic_info['topic_id']]['thanks_count']) + $answers_val['thanks_count']),
								'reputation' => ($user_topics[$topic_info['topic_id']]['reputation'] + $answer_reputation)
							);
						}
					}
				}

				if ($questions_info[$answers_val['question_id']]['category_id'])
				{
					$user_reputation_category[$questions_info[$answers_val['question_id']]['category_id']]['reputation'] += $answer_reputation;

					$user_reputation_category[$questions_info[$answers_val['question_id']]['category_id']]['agree_count'] += $answers_val['agree_count'];

					$user_reputation_category[$questions_info[$answers_val['question_id']]['category_id']]['questions'][$answers_val['question_id']] = $answers_val['question_id'];
				}

				$user_reputation = $user_reputation + $answer_reputation;
			}
		}

		if (is_array($user_topics))
		{
			if ($user_topics = aasort($user_topics, 'count', 'DESC'))
			{
				$user_topics = array_slice($user_topics, 0, 20);
			}

			foreach ($user_topics as $t_key => $t_val)
			{
				if ($reputation_topic_id = $this->fetch_one('reputation_topic', 'auto_id', 'uid = ' . $uid . ' AND topic_id = ' . $t_val['topic_id']))
				{
					$this->update('reputation_topic', array(
						'uid' => $uid,
						'topic_id' => $t_val['topic_id'],
						'topic_count' => $t_val['count'],
						'update_time' => time(),
						'agree_count' => $t_val['agree_count'],
						'thanks_count' => $t_val['thanks_count'],
						'reputation' => round($t_val['reputation'])
					), 'auto_id = ' . $reputation_topic_id);
				}
				else
				{
					$this->insert('reputation_topic', array(
						'uid' => $uid,
						'topic_id' => $t_val['topic_id'],
						'topic_count' => $t_val['count'],
						'update_time' => time(),
						'agree_count' => $t_val['agree_count'],
						'thanks_count' => $t_val['thanks_count'],
						'reputation' => round($t_val['reputation'])
					));
				}
			}
		}

		if (is_array($user_reputation_category))
		{
			foreach ($user_reputation_category as $t_key => $t_val)
			{
				if ($user_reputation_category_id = $this->fetch_one('reputation_category', 'auto_id', 'uid = ' . intval($uid) . ' AND category_id = ' . $t_key))
				{
					$this->update('reputation_category', array(
						'uid' => intval($uid),
						'category_id' => $t_key,
						'update_time' => time(),
						'reputation' => round($t_val['reputation']),
						'agree_count' => $t_val['agree_count'],
						'question_count' => count($t_val['questions'])
					), 'auto_id = ' . $user_reputation_category_id);
				}
				else
				{
					$this->insert('reputation_category', array(
						'uid' => intval($uid),
						'category_id' => $t_key,
						'update_time' => time(),
						'reputation' => round($t_val['reputation']),
						'agree_count' => $t_val['agree_count'],
						'question_count' => count($t_val['questions'])
					));
				}
			}
		}

		$this->model('account')->update_users_fields(array(
			'reputation' => round($user_reputation),
			'reputation_update_time' => time()
		), $uid);

		$this->model('account')->update_user_reputation_group($uid);
	}

	public function calculate($start = 0, $limit = 100)
	{
		if ($users_list = $this->query_all('SELECT uid FROM ' . get_table('users') . ' ORDER BY uid ASC', intval($start) . ',' . intval($limit)))
		{
			foreach ($users_list as $key => $val)
			{
				$this->calculate_by_uid($val['uid']);
			}

			return true;
		}

		return false;
	}

	public function calculate_agree_count($uid, $topic_ids)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		return $this->sum('reputation_topic', 'agree_count', 'uid = ' . intval($uid) . ' AND topic_id IN(' . implode(',', $topic_ids) . ')');
	}

	public function calculate_thanks_count($uid, $topic_ids)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		return $this->sum('reputation_topic', 'thanks_count', 'uid = ' . intval($uid) . ' AND topic_id IN(' . implode(',', $topic_ids) . ')');
	}
}