$(document).ready(function() {
	if ($('.tabbable').length)
	{		
		bp_more_load(G_BASE_URL + '/question/ajax/discuss/sort_type-new__topic_id-' + CONTENTS_TOPIC_ID, $('#c_question_more'), $('#c_question_list'), 2);
		
		bp_more_load(G_BASE_URL + '/topic/ajax/question_list/type-best__topic_id-' + CONTENTS_TOPIC_ID, $('#bp_best_question_more'), $('#c_best_question_list'), 2);
		
		bp_more_load(G_BASE_URL + '/topic/ajax/question_list/type-favorite__topic_title-' + encodeURIComponent(CONTENTS_TOPIC_TITLE), $('#bp_favorite_more'), $('#c_favorite_list'), 0, function () { if ($('#c_favorite_list a').attr('id')) { $('#i_favorite').show() } });
	}

	
	if ($('#focus_users').length)
	{
		$.get(G_BASE_URL + '/topic/ajax/get_focus_users/topic_id-' + TOPIC_ID, function (data) {
			$.each(data, function (i, d) {		
				$('#focus_users').append('<a href="' + d['url'] + '"><img src="' + d['avatar_file'] + '" alt="' + d['user_name'] + '" /></a> ');
			});
		}, 'json');
	}

	
	if ($('#topic_pic_uploader').length)
	{
		init_img_uploader(G_BASE_URL + '/topic/ajax/upload_topic_pic/topic_id-' + TOPIC_ID, 'topic_pic', $('#topic_pic_uploader'), $('#uploading_status'), $('#topic_pic'));
	}
});