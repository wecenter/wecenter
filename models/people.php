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

class people_class extends AWS_MODEL
{	
	var $search_users_total = 0;
	
	public function update_views($uid)
	{
		if (AWS_APP::cache()->get('update_views_people_' . md5(session_id()) . '_' . intval($question_id)))
		{
			return false;
		}
		
		AWS_APP::cache()->set('update_views_people_' . md5(session_id()) . '_' . intval($question_id), time(), get_setting('cache_level_normal'));
		
		return $this->query('UPDATE ' . $this->get_table('users') . ' SET views_count = views_count + 1 WHERE uid = ' . intval($uid));
	}
	
	public function get_user_reputation_topic($uid, $user_reputation, $limit = 10)
	{
		$reputation_topics = $this->get_users_reputation_topic($uid, array(
			$uid => $user_reputation
		), $limit);
		
		return $reputation_topics[$uid];
	}
	
	public function get_users_reputation_topic($uids, $users_reputation, $limit = 10)
	{
		if ($users_reputation_topics = $this->model('reputation')->get_reputation_topic($uids))
		{
			foreach ($users_reputation_topics as $key => $val)
			{
				if ($val['reputation'] < 1)
				{
					continue;
				}
				
				$reputation_topics[$val['uid']][] = $val;
			}
		}
		
		if ($reputation_topics)
		{
			foreach ($reputation_topics AS $uid => $reputation_topic)
			{
				$reputation_topic = array_slice(aasort($reputation_topic, 'reputation', 'DESC'), 0, $limit);
				
				foreach ($reputation_topic as $key => $val)
				{
					$topic_ids[$val['topic_id']] = $val['topic_id'];
				}
				
				foreach ($reputation_topic as $key => $val)
				{
					$reputation_topic[$key]['topic_title'] = $topics[$val['topic_id']]['topic_title'];
					$reputation_topic[$key]['url_token'] = $topics[$val['topic_id']]['url_token'];
				}
				
				$reputation_topics[$uid] = $reputation_topic;
			}
			
			$topics = $this->model('topic')->get_topics_by_ids($topic_ids);
			
			foreach ($reputation_topics as $uid => $reputation_topic)
			{
				foreach ($reputation_topic as $key => $val)
				{
					$reputation_topics[$uid][$key]['topic_title'] = $topics[$val['topic_id']]['topic_title'];
					$reputation_topics[$uid][$key]['url_token'] = $topics[$val['topic_id']]['url_token'];
				}
			}
		}
		
		return $reputation_topics;
	}
	
	public function search_users($page, $per_page, $user_name = null, $email = null, $group_id = null, $ip = null, $integral_min = null, $integral_max = null, $reputation_min = null, $reputation_max = null, $job_id = null, $province = null, $city = null)
	{
		$where = array();
		
		if ($user_name)
		{
			$where[] = "user_name LIKE '%" . $this->quote($user_name) . "%'";
		}
		
		if ($email)
		{
			$where[] = "email = '" . $this->quote($email) . "'";
		}
		
		if ($group_id)
		{
			$where[] = 'group_id = ' . intval($group_id);
		}
		
		if ($ip)
		{
			if (preg_match('/.*\.\\*$/i', $ip))
			{
				$ip_base = ip2long(str_replace('*', '0', $ip));

				$where[] = 'last_ip BETWEEN ' . $this->quote($ip_base) . ' AND ' . ($this->quote($ip_base) + 255);
			}
			else
			{
				$where[] = 'last_ip = ' . ip2long($ip);
			}
		}
		
		if ($integral_min)
		{
			$where[] = 'integral >= ' . intval($integral_min);
		}
		
		if ($integral_max)
		{
			$where[] = 'integral <= ' . intval($integral_max);
		}
		
		if ($reputation_min)
		{
			$where[] = 'reputation >= ' . intval($reputation_min);
		}
		
		if ($reputation_max)
		{
			$where[] = 'reputation <= ' . intval($reputation_max);
		}
		
		if ($job_id)
		{
			$where[] = 'job_id = ' . intval($job_id);
		}
		
		if ($province)
		{
			$where[] = "province = '" . $this->quote($province) . "'";
		}
		
		if ($city)
		{
			$where[] = "city = '" . $this->quote($city) . "'";
		}
		
		return $this->fetch_page('users', implode(' AND ', $where), 'uid DESC', $page, $per_page);
	}
}