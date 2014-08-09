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

class aws_offical_external_class extends AWS_MODEL
{
	public function format_js_question_output($data)
	{
		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				if ($val['title'])
				{
					$val['question_content'] = $val['title'];
				}

				$output .= '<div class="q_mainElem i_prl"><span class="q_reply"><em>' . $val['answer_count'] . '</em>回复</span>';

				if ($val['answer_count'] > 0)
				{
					$output .= '<a class="q_user i_alpHover" href="' . get_js_url('/people/' . $val['answer_info']['user_info']['url_token']) . '"><img src="' . get_avatar_url($val['answer_info']['user_info']['uid'], 'mid') . '" class="user_msg" /></a>';
				}
				else
				{
					$output .= '<a class="q_user i_alpHover" href="' . get_js_url('/people/' . $val['user_info']['url_token']) . '"><img src="' . get_avatar_url($val['user_info']['uid'], 'mid') . '" class="user_msg" /></a>';
				}

				$output .= '<h4><a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a></h4><p class="q_elems">';

				if ($val['category_info']['title'])
				{
					$output .= '<span class="q_banner i_line"><em class="q_ex i_line i_gray"><a href="' . get_js_url('home/explore/category-' . $val['category_info']['url_token']) . '">' . $val['category_info']['title'] . '</a></em></span> • ';
				}

				if ($val['answer_count'] > 0)
				{
					$output .= '<a class="user_msg" href="' . get_js_url('/people/' . $val['answer_info']['user_info']['url_token']) . '">' . $val['answer_info']['user_info']['user_name'] . '</a> 回复了问题';
				}
				else
				{
					$output .= '<a class="user_msg" href="' . get_js_url('/people/' . $val['user_info']['url_token']) . '">' . $val['user_info']['user_name'] . '</a> 发起了问题';

				}

				$output .= ' • ' . date_friendly($val['update_time']) . ' • ' . $val['focus_count'] . ' 人关注 • ' . $val['view_count'] . ' 次浏览</p></div>';
			}
		}

		return "document.write('" . addcslashes($output, "'") . "');";
	}

	public function format_js_users_output($data)
	{
		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				$output .= '<div class="item"><dl class="inf"><dt><a href="' . get_js_url('/people/' . $val['url_token']) . '">' . $val['user_name'] . '</a></dt><dd>回复了 ' . $val['answer_count'] . ' 个问题</dd><dd>获得 ' . $val['agree_count'] . ' 个赞同</dd></dl><div class="avatar"><a href=""><img src="' . get_avatar_url($val['uid'], 'mid') . '" /></a></div></div><!-- .item -->';
			}
		}

		return "document.write('" . addcslashes($output, "'") . "');";
	}

	public function k2_hot_users($uid = 0, $limit = 5)
	{
		if ($users_list = $this->fetch_all('users', 'uid <> ' . intval($uid) . ' AND last_active > ' . (time() - (60 * 60 * 24 * 30)), 'answer_count DESC', ($limit * 4)))
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
}
