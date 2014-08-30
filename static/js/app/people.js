$(document).ready(function () {	
	AWS.load_list_view(G_BASE_URL + '/people/ajax/user_actions/uid-' + PEOPLE_USER_ID + '__actions-201', $('#bp_user_actions_answers_more'), $('#contents_user_actions_answers'));	// 参与的问题
			  
	AWS.load_list_view(G_BASE_URL + '/people/ajax/user_actions/uid-' + PEOPLE_USER_ID + '__actions-101', $('#bp_user_actions_questions_more'), $('#contents_user_actions_questions'));	// 发起的问题
	
	AWS.load_list_view(G_BASE_URL + '/people/ajax/user_actions/uid-' + PEOPLE_USER_ID + '__actions-501', $('#bp_user_actions_articles_more'), $('#contents_user_actions_articles'));	// 发起的文章
		
	AWS.load_list_view(G_BASE_URL + '/people/ajax/user_actions/uid-' + PEOPLE_USER_ID + '__actions-' + ACTIVITY_ACTIONS, $('#bp_user_actions_more'), $('#contents_user_actions'));	// 个人动态
		
	AWS.load_list_view(G_BASE_URL + '/people/ajax/follows/type-follows__uid-' + PEOPLE_USER_ID, $('#bp_user_follows_more'), $('#contents_user_follows'));	// 关注
	
	AWS.load_list_view(G_BASE_URL + '/people/ajax/follows/type-fans__uid-' + PEOPLE_USER_ID, $('#bp_user_fans_more'), $('#contents_user_fans'));	// 粉丝
		
	AWS.load_list_view(G_BASE_URL + '/people/ajax/topics/uid-' + PEOPLE_USER_ID, $('#bp_user_topics_more'), $('#contents_user_topics'));	// 话题

	AWS.load_list_view(G_BASE_URL + '/account/ajax/integral_log/', $('#bp_user_integral'), $('#contents_user_integral'));	// 积分
	
	if (window.location.hash)
	{
		if (document.getElementById(window.location.hash.replace('#', '')))
		{
			document.getElementById(window.location.hash.replace('#', '')).click();
		}
	}
	
	$('.aw-tabs li').click(function() {
		$(this).addClass('active').siblings().removeClass('active');
		
		$('#focus .aw-user-center-follow-mod').eq($(this).index()).show().siblings().hide();
	});
});