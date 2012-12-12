$(document).ready(function () {
	$('#feature_dynamic > a').click(function ()
	{
		$('#feature_dynamic > a').removeClass('cur');

		$(this).addClass('cur');

		if ($(this).attr('rel') == 'unresponsive')
		{
			bp_more_load(G_BASE_URL + '/question/ajax/discuss/sort_type-unresponsive__per_page-10__feature_id-' + FEATURE_ID, $('#bp_all_more'), $('#c_all_list'), 1);
		}
		else
		{
			bp_more_load(G_BASE_URL + '/topic/ajax/question_list/feature_id-' + FEATURE_ID, $('#bp_all_more'), $('#c_all_list'));
		}
	});

	$('#hot_question_control a.cur').click();
	$('#feature_dynamic a.cur').click();
	
	$.get(G_BASE_URL + '/topic/ajax/question_list/type-best__feature_id-' + FEATURE_ID, function (result) {
		if ($.trim(result) != '')
		{
			$('#c_best_list').html(result);
			$('#c_best').show();
		}
	});
});


function get_hot_question(el, day)
{
	$('#hot_question_control a').removeClass('cur');

	el.addClass('cur');

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