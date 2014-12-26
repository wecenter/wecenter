<?php

if (!defined('IN_ANWSION'))
{
    die;
}

if (!is_digits($_GET['page']))
{
    $_GET['page'] = 1;
}

$user_list = $this->model('account')->fetch_page('users', null, 'uid ASC', $_GET['page'], 300);

if ($user_list)
{
    foreach ($user_list AS $user_info)
    {
        $article_count = $this->model('article')->count('article', 'uid = ' . $user_info['uid']);

        $this->model('account')->update('users', array('article_count' => $article_count), 'uid = ' . $user_info['uid']);
    }

    H::redirect_msg(AWS_APP::lang()->_t('正在升级数据库') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/upgrade/script/page-' . ($_GET['page'] + 1));
}
