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

class people_class extends AWS_MODEL
{
	public function update_views($uid)
	{
		if (AWS_APP::cache()->get('update_views_people_' . md5(session_id()) . '_' . intval($uid)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_people_' . md5(session_id()) . '_' . intval($uid), time(), get_setting('cache_level_normal'));

		return $this->query('UPDATE ' . $this->get_table('users') . ' SET views_count = views_count + 1 WHERE uid = ' . intval($uid));
	}

	public function get_user_reputation_topic($uid, $user_reputation, $limit = 10)
	{
		$reputation_topics = $this->get_users_reputation_topic(array(
			$uid
		), array(
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
				if ($val['reputation'] < 1 OR $val['agree_count'] < 1)
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

	public function get_near_by_users($longitude, $latitude, $uid, $limit = 10)
	{
		$squares = $this->model('geo')->get_square_point($longitude, $latitude, 50);

		if ($weixin_users = $this->fetch_all('users_weixin', "`uid` != " . intval($uid) . " AND `location_update` > 0 AND `latitude` > " . $squares['BR']['latitude'] . " AND `latitude` < " . $squares['TL']['latitude'] . " AND `longitude` > " . $squares['TL']['longitude'] . " AND `longitude` < " . $squares['BR']['longitude'], 'location_update DESC', null, $limit))
		{
			foreach ($weixin_users AS $key => $val)
			{
				$near_by_uids[] = $val['uid'];
				$near_by_location_update[$val['uid']] = $val['location_update'];
				$near_by_location_longitude[$val['uid']] = $val['longitude'];
				$near_by_location_latitude[$val['uid']] = $val['latitude'];
			}

		}

		if ($near_by_uids)
		{
			if ($near_by_users = $this->model('account')->get_user_info_by_uids($near_by_uids))
			{
				foreach ($near_by_users AS $key => $val)
				{
					$near_by_users[$key]['location_update'] = $near_by_location_update[$val['uid']];

					$near_by_users[$key]['distance'] = $this->model('geo')->get_distance($longitude, $latitude, $near_by_location_longitude[$val['uid']], $near_by_location_latitude[$val['uid']]);
				}
			}
		}

		return $near_by_users;
	}
}