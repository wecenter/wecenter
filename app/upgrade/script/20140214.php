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
				$this->model('posts')->set_posts_index($val['question_id'], 'question', $val);
			}
			
			H::redirect_msg(AWS_APP::lang()->_t('正在升级问题数据库') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/script/post_type-question__page-' . ($_GET['page'] + 1));
			die;
		}
		else
		{
			HTTP::redirect('/upgrade/script/post_type-article__page-1');
			die;
		}
	break;
	
	case 'article':
		if ($articles_list = $this->model('question')->fetch_page('article', null, 'id ASC', $_GET['page'], 300))
		{
			foreach ($articles_list as $key => $val)
			{
				$this->model('posts')->set_posts_index($val['id'], 'article', $val);
			}
			
			H::redirect_msg(AWS_APP::lang()->_t('正在升级文章数据库') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/script/post_type-article__page-' . ($_GET['page'] + 1));
			die;
		}
		else
		{
			if (get_setting('weixin_app_id'))
			{
				$this->model('setting')->set_vars(array(
					'weixin_account_role' => 'service'
				));
			}
		}
	break;
}