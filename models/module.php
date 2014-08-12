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

class module_class extends AWS_MODEL
{
	public function recommend_users_topics($uid)
	{
		if (!$recommend_users = $this->model('account')->get_user_recommend_v2($uid, 20))
		{
			return false;
		}

		if (! $recommend_topics = $this->model('topic')->get_user_recommend_v2($uid, 20))
		{
			return array_slice($recommend_users, 0, get_setting('recommend_users_number'));
		}

		if ($recommend_topics)
		{
			shuffle($recommend_topics);

			$recommend_topics = array_slice($recommend_topics, 0, intval(get_setting('recommend_users_number') / 2));
		}

		if ($recommend_users)
		{
			shuffle($recommend_users);

			$recommend_users = array_slice($recommend_users, 0, (get_setting('recommend_users_number') - count($recommend_topics)));
		}

		if (! is_array($recommend_users))
		{
			$recommend_users = array();
		}

		return array_merge($recommend_users, $recommend_topics);
	}

	public function sidebar_hot_topics($category_id = 0)
	{
		return $this->model('topic')->get_hot_topics($category_id, 5, 'week');
	}

	public function sidebar_hot_users($uid = 0, $limit = 5)
	{
		if ($users_list = $this->fetch_all('users', 'uid <> ' . intval($uid) . ' AND last_active > ' . (time() - (60 * 60 * 24 * 7)), 'answer_count DESC', ($limit * 4)))
		{
			foreach($users_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$users_list[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}

		shuffle($users_list);

		return array_slice($users_list, 0, $limit);
	}

	public function feature_list()
	{
		return $this->model('feature')->get_enabled_feature_list('id DESC', 1, 5);
	}
}