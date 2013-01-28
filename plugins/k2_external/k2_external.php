<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class k2_external_class extends AWS_MODEL
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
				
				$output .='<div class="item"><div class="user"><ul><li class="u"><a href="' . get_js_url('/people/' . $val['user_info']['url_token']) . '">' . $val['user_info']['user_name'] . '</a></li><li class="r">' . $val['answer_count'] . ' 条回复</li><li class="t">' . date_friendly($val['update_time'], 2592000) . '</li></ul></div><div class="cnt"><h3><a href="' . get_js_url('/question/' . $val['question_id']) . '">' . $val['question_content'] . '</a></h3><dl class="inf">';
				
				if ($val['category_info']['title'])
				{
					$output .= '<dt class="cate"><a href="' . get_js_url('home/explore/category-' . $val['category_info']['url_token']) . '">' . $val['category_info']['title'] . '</a></dt>';
				}
				
				$output .= '<dd class="info"> • ';
				
				if ($val['answer_count'] > 0)
				{
					$output .= '<a class="u" href="' . get_js_url('/people/' . $val['answer']['user_info']['url_token']) . '">' . $val['answer']['user_info']['user_name'] . '</a> 回复了问题';
				}
				else
				{
					$output .= '<a class="u" href="' . get_js_url('/people/' . $val['user_info']['url_token']) . '">' . $val['user_info'] . '</a> 发起了问题';
					
				}
				
				$output .= ' • ' . date_friendly($val['update_time']) . ' • ' . $val['focus_count'] . ' 人关注 • ' . $val['view_count'] . ' 次浏览</dd></dl></div><!-- .cnt --></div><!-- .item -->';
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