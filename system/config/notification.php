<?php

$config['action_details'][notify_class::TYPE_PEOPLE_FOCUS] = array(
	'user_setting' => 1,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('有人关注了我')
);

$config['action_details'][notify_class::TYPE_NEW_ANSWER] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我关注的问题有了新的回复')
);

$config['action_details'][notify_class::TYPE_INVITE_QUESTION] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有人邀请我回复问题')
);

$config['action_details'][notify_class::TYPE_QUESTION_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的问题被评论')
);

$config['action_details'][notify_class::TYPE_ANSWER_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的问题评论被回复')
);

$config['action_details'][notify_class::TYPE_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有问题评论提到我')
);

$config['action_details'][notify_class::TYPE_ANSWER_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有回答评论提到我')
);

$config['action_details'][notify_class::TYPE_ANSWER_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有回答提到我')
);

$config['action_details'][notify_class::TYPE_ANSWER_AGREE] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的回复收到赞同')
);

$config['action_details'][notify_class::TYPE_ANSWER_THANK] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的回复收到感謝')
);

$config['action_details'][notify_class::TYPE_QUESTION_THANK] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我发布的问题收到感謝')
);

$config['action_details'][notify_class::TYPE_MOD_QUESTION] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的问题被编辑')
);

$config['action_details'][notify_class::TYPE_REMOVE_ANSWER] = array(
	'user_setting' => 0,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我发表的回复被删除')
);

$config['action_details'][notify_class::TYPE_REDIRECT_QUESTION] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我发布的问题被重定向')
);

$config['action_details'][notify_class::TYPE_CONTEXT] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('文字通知')
);

$config['action_details'][notify_class::TYPE_ARTICLE_NEW_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的文章被评论')
);

$config['action_details'][notify_class::TYPE_ARTICLE_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有文章评论提到我')
);

$config['action_details'][notify_class::TYPE_ARTICLE_APPROVED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('文章通过审核')
);

$config['action_details'][notify_class::TYPE_ARTICLE_REFUSED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('文章未通过审核')
);

$config['action_details'][notify_class::TYPE_QUESTION_APPROVED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('问题通过审核')
);

$config['action_details'][notify_class::TYPE_QUESTION_REFUSED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('问题未通过审核')
);

$config['action_details'][notify_class::TYPE_TICKET_REPLIED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('我的工单被回复')
);

$config['action_details'][notify_class::TYPE_TICKET_CLOSED] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('我的工单被关闭')
);
