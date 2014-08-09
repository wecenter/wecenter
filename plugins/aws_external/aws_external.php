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

class aws_external_class extends AWS_MODEL
{
	public function format_js_question_ul_output($ul_class, $data)
	{
		$output = '<ul class="' . $ul_class . '">';

		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				if ($val['title'])
				{
					$val['question_content'] = $val['title'];
				}

				/***
					提问者头像调用：get_avatar_url($val['published_uid'], 'mid')
					回复者头像调用：get_avatar_url($val['answer_info']['user_info']['uid'], 'mid')

					$val['question_content'] - 问题标题
					$val['question_detail'] - 问题说明
					$val['published_uid'] - 发布用户UID
					$val['answer_count'] - 回答数量
					$val['answer_users'] - 回答人数
					$val['view_count'] - 浏览次数
					$val['focus_count'] - 关注数
					$val['comment_count'] - 评论数
					$val['category_id'] - 分类 ID
					$val['agree_count'] - 回复赞同数总和
					$val['against_count'] - 回复反对数总和
					$val['best_answer'] - 最佳回复 ID
					$val['has_attach'] - 是否存在附件
					$val['lock'] - 是否锁定
					$val['thanks_count'] - 感谢数
				***/

				$output .= '<li><a href="' . get_js_url('/question/' . $val['question_id']) . '" target="_blank">' . $val['question_content'] . '</a></li>';
			}
		}

		$output .= '</ul>';

		return "document.write('" . addcslashes($output, "'") . "');";
	}

	public function format_js_users_ul_output($ul_class, $data)
	{
		$output = '<ul class="' . $ul_class . '">';

		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				/***
					头像调用：get_avatar_url($val['uid'], 'mid')

					$val['signature'] - 个人介绍
				***/

				$output .= '<li><a href="' . get_js_url('/people/' . $val['url_token']) . '" target="_blank">' . $val['user_name'] . '</a></li>';
			}
		}

		$output .= '</ul>';

		return "document.write('" . addcslashes($output, "'") . "');";
	}

	public function format_js_topics_ul_output($ul_class, $data)
	{
		$output = '<ul class="' . $ul_class . '">';

		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				/***
					话题图片调用：get_topic_pic_url($val['uid'], $val['topic_pic'])

					$val['topic_description'] - 话题简介
					$val['discuss_count'] - 讨论数量
				***/

				$output .= '<li><a href="' . get_js_url('/topic/' . $val['url_token']) . '" target="_blank">' . $val['topic_title'] . '</a></li>';
			}
		}

		$output .= '</ul>';

		return "document.write('" . addcslashes($output, "'") . "');";
	}
}