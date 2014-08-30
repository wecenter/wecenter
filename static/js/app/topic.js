$(function()
{
	if ($('.tabbable').length)
	{
		AWS.load_list_view(G_BASE_URL + '/explore/ajax/list/sort_type-new__topic_id-' + CONTENTS_RELATED_TOPIC_IDS, $('#c_all_more'), $('#c_all_list'), 2, function(){check_related_topic($('#c_all_list .aw-item'))});
		
		AWS.load_list_view(G_BASE_URL + '/explore/ajax/list/sort_type-new__is_recommend-1__topic_id-' + CONTENTS_RELATED_TOPIC_IDS, $('#c_recommend_more'), $('#c_recommend_list'), 2, function(){check_related_topic($('#c_recommend_list .aw-item'))});
		
		AWS.load_list_view(G_BASE_URL + '/topic/ajax/question_list/type-best__topic_id-' + CONTENTS_TOPIC_ID, $('#bp_best_question_more'), $('#c_best_question_list'), 2);
		
		AWS.load_list_view(G_BASE_URL + '/explore/ajax/list/post_type-question__sort_type-new__topic_id-' + CONTENTS_TOPIC_ID, $('#c_question_more'), $('#c_question_list'), 2);
		
		AWS.load_list_view(G_BASE_URL + '/explore/ajax/list/post_type-article__sort_type-new__topic_id-' + CONTENTS_TOPIC_ID, $('#bp_articles_more'), $('#c_articles_list'), 2);
		
		AWS.load_list_view(G_BASE_URL + '/topic/ajax/question_list/type-favorite__topic_title-' + encodeURIComponent(CONTENTS_TOPIC_TITLE), $('#bp_favorite_more'), $('#c_favorite_list'), 0, function () { if ($('#c_favorite_list a').attr('id')) { $('#i_favorite').show() } });
	}
	
	if ($('#focus_users').length)
	{
		$.get(G_BASE_URL + '/topic/ajax/get_focus_users/topic_id-' + TOPIC_ID, function (data) {
			if (data != null)
			{
				$.each(data, function (i, e) {		
					$('#focus_users').append('<a href="' + e['url'] + '"><img src="' + e['avatar_file'] + '" alt="' + e['user_name'] + '" /></a> ');
				});
			}
		}, 'json');
	}
	
	//问题添加评论
    AWS.Init.init_comment_box('.aw-add-comment');

	//侧边栏话题编辑记录收缩
	$('.topic-edit-notes .icon-down').click(function() {
		if (!$(this).parents('.topic-edit-notes').find('.mod-body').is(':visible'))
		{
			$(this).parents('.topic-edit-notes').find('.mod-body').fadeIn();
			$(this).addClass('active');
		}
		else
		{
			$(this).parents('.topic-edit-notes').find('.mod-body').fadeOut();	
			$(this).removeClass('active');
		}
	});

	//话题问题搜索下拉绑定
	AWS.Dropdown.bind_dropdown_list($('.aw-search-bar #question-input'), 'topic_question');

	check_related_topic($('#c_all_list .aw-item'));
	check_related_topic($('#c_recommend_list .aw-item'));

	$('.aw-topic-recommend-list #aw-more-recommend').click(function()
	{
		$('.aw-topic-list-mod .nav-tabs li:eq(2) a').click();
	})

	function check_related_topic (selector)
	{
		var contents_topic = CONTENTS_TOPIC_ID.split(',');
		$.each(selector, function (i, e)
		{
			var arr = $(this).attr('data-topic-id').split(','), _this = $(this), count = 0;
			$.each(arr, function (i, e)
			{
				if ($.inArray(e, contents_topic) != -1)
				{
					count += 1;
				}
			});
			if (count == 0)
			{
				_this.find('.related-topic').show();
			}
		});
	}
});