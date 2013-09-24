function get_hot_question(day)
{
	$('#hot_question_list').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
	
	$.get(G_BASE_URL + '/question/ajax/discuss/sort_type-hot__feature_id-' + FEATURE_ID + '__day-' + day + '__per_page-5', function (response)
	{
		if ($.trim(response) != '')
		{
			$('#hot_question_list').html(response);
		}
		else
		{
			$('#hot_question_list').html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
		}
	});
}

$(document).ready(function () {
	$('#feature_dynamic a').click(function ()
	{
		if ($(this).attr('href') == '#unresponsive')
		{
			bp_more_load(G_BASE_URL + '/question/ajax/discuss/sort_type-unresponsive__per_page-10__feature_id-' + FEATURE_ID, $('#bp_all_more'), $('#c_all_list'), 1);
		}
		else
		{
			bp_more_load(G_BASE_URL + '/question/ajax/discuss/sort_type-new__feature_id-' + FEATURE_ID, $('#bp_all_more'), $('#c_all_list'), 1);
		}
	});

	$('#hot_question_control li.active a').click();
	$('#feature_dynamic li.active a').click();
	
	$.get(G_BASE_URL + '/topic/ajax/question_list/type-best__feature_id-' + FEATURE_ID, function (result) {
		if ($.trim(result) != '')
		{
			$('#c_best_list').html(result);
		}
	});
});