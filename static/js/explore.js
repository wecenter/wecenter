var cur_page = 1;
var cur_day = 30;
var cur_sort_type = 'new';
var bp_more_inner_o;
var cur_category_id = '';
var cur_load_page = 1;
var cur_feature_id = 0;

$(document).ready(function()
{
	if ($('#question_list').html() == '')
	{
		$('#question_list').html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
		$('#bp_more').addClass('disabled');
		$('#bp_more').find('a').html(_t('没有更多了'));
	}
	
	bp_more_inner_o = $('#bp_more').html();
	
	$('#bp_more').click(function()
	{
		var _this = this;
		
		cur_page++;
		
		$(this).addClass('loading');
		$(this).find('a').html(_t('正在载入') + '...');
		
		$.get(G_BASE_URL + '/question/ajax/discuss/sort_type-' + cur_sort_type + '__page-' + cur_page  + '__category-' + cur_category_id + '__day-' + cur_day + '__feature_id-' + cur_feature_id, function (response)
			{
				if (response.length)
				{
					if (cur_load_page > 2)
					{
						return window.location = $(_this).find('a').attr('href');
					}
					
					if (cur_page == 1)
					{
						$('#question_list').html(response);
					}
					else
					{
						$('#question_list').append(response);
					}
						
					//cur_page++;
					cur_load_page++;
					
					$(_this).html(bp_more_inner_o);
					$(_this).find('a').attr('href', G_BASE_URL + '/home/explore/sort_type-' + cur_sort_type + '__page-' + (cur_page + 1)  + '__category-' + cur_category_id + '__day-' + cur_day);
				}
				else
				{
					if (cur_page == 1)
					{
						$('#question_list').html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
					}
						
					$(_this).addClass('disabled');
					
					$(_this).find('a').html(_t('没有更多了'));
				}
			
			$(_this).removeClass('loading');
		});
		
		return false;
	});
});