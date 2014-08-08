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

class menu_class extends AWS_MODEL
{
	public function add_nav_menu($title, $description, $type = 'custom', $type_id = 0, $link = null)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->insert('nav_menu', array(
			'title' => $title,
			'description' => $description,
			'type' => $type,
			'type_id' => $type_id,
			'link' => $link,
			'icon' => '',
			'sort' => 99,
		));
	}

	public function process_child_menu_links($data, $app)
	{
		if (!$data)
		{
			return false;
		}

		switch ($app)
		{
			case 'explore':
				$url_prefix = '';

				$url_mobile_prefix = 'm/explore/';
			break;

			case 'article':
				$url_prefix = 'article/';

				$url_mobile_prefix = 'm/article/';
			break;
		}

		foreach ($data AS $key => $val)
		{
			if (!$val['url_token'])
			{
				$val['url_token'] = $val['id'];
			}

			if (defined('IN_MOBILE'))
			{
				$data[$key]['link'] = $url_mobile_prefix . 'category-' . $val['id'];
			}
			else
			{
				$data[$key]['link'] = $url_prefix . 'category-' . $val['url_token'];
			}

			$data[$key]['child'] = $this->process_child_menu_links($this->model('system')->fetch_category($val['type'], $val['id']), $app);
		}

		return $data;
	}

	public function get_nav_menu_list($app = null)
	{
		if (!$nav_menu_data = AWS_APP::cache()->get('nav_menu_list'))
		{
			$nav_menu_data = $this->fetch_all('nav_menu', null, 'sort ASC');

			AWS_APP::cache()->set('nav_menu_list', $nav_menu_data, get_setting('cache_level_low'), 'nav_menu');
		}

		if ($nav_menu_data)
		{
			$category_info = $this->model('system')->get_category_list('question');

			switch ($app)
			{
				case 'explore':
					$url_prefix = 'explore/';

					$url_mobile_prefix = 'm/';
				break;

				case 'question':
					$url_prefix = 'question/';

					$url_mobile_prefix = 'm/';
				break;

				case 'article':
					$url_prefix = 'article/';

					$url_mobile_prefix = 'm/article/';
				break;
			}

			foreach ($nav_menu_data as $key => $val)
			{
				switch ($val['type'])
				{
					case 'category':
						if (defined('IN_MOBILE'))
						{
							$nav_menu_data[$key]['link'] = $url_mobile_prefix . 'category-' . $category_info[$val['type_id']]['id'];
						}
						else
						{
							$nav_menu_data[$key]['link'] = $url_prefix . 'category-' . $category_info[$val['type_id']]['url_token'];

							$nav_menu_data[$key]['child'] = $this->process_child_menu_links($this->model('system')->fetch_category($category_info[$val['type_id']]['type'], $val['type_id']), $app);
						}
					break;
				}

				$nav_menu_data['category_ids'][] = $val['type_id'];
			}

			if (defined('IN_MOBILE'))
			{
				$nav_menu_data['base']['link'] = $url_mobile_prefix;
			}
			else
			{
				$nav_menu_data['base']['link'] = $url_prefix;
			}
		}

		return $nav_menu_data;
	}

	public function update_nav_menu($nav_menu_id, $data)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->update('nav_menu', $data, 'id = ' . intval($nav_menu_id));
	}

	public function remove_nav_menu($nav_menu_id)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->delete('nav_menu', 'id = ' . intval($nav_menu_id));
	}
}