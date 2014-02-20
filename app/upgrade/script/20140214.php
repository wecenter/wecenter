<?php

if (!defined('IN_ANWSION'))
{
	die;
}

if (!$_GET['page'])
{
	$_GET['page'] = 1;
}

switch ($_GET['post_type'])
{
	default:
	case 'question':
		if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], 300))
		{
			foreach ($questions_list as $key => $val)
			{
				$this->model('posts')->set_posts_index($val['question_id'], 'question', array(
					'add_time' => $val['add_time'],
					'update_time' => $val['update_time'],
					'category_id' => $val['category_id'],
					'is_recommend' => $val['is_recommend'],
					'view_count' => $val['view_count'],
					'anonymous' => $val['anonymous'],
					'popular_value' => $val['popular_value'],
					'uid' => $val['published_uid'],
					'lock' => $val['lock'],
					'agree_count' => $val['agree_count'],
					'answer_count' => $val['answer_count']
				));
			}
			
			H::redirect_msg(AWS_APP::lang()->_t('正在升级问题数据库') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/upgrade/script/post_type-question__page-' . ($_GET['page'] + 1));
			die;
		}
		else
		{
			HTTP::redirect('?/upgrade/script/post_type-article__page-1');
			die;
		}
	break;
	
	case 'article':
		if ($articles_list = $this->model('question')->fetch_page('article', null, 'id ASC', $_GET['page'], 300))
		{
			foreach ($articles_list as $key => $val)
			{
				$this->model('posts')->set_posts_index($val['id'], 'article', array(
					'add_time' => $val['add_time'],
					'update_time' => $val['add_time'],
					'category_id' => $val['category_id'],
					'view_count' => $val['views'],
					'anonymous' => 0,
					'uid' => $val['uid'],
					'agree_count' => $val['votes'],
					'answer_count' => $val['comments'],
					'lock' => $val['lock'],
					'is_recommend' => $val['is_recommend'],
				));
			}
			
			H::redirect_msg(AWS_APP::lang()->_t('正在升级文章数据库') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '?/upgrade/script/post_type-article__page-' . ($_GET['page'] + 1));
			die;
		}
	break;
}