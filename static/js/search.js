var cur_page = 0;
var search_result_more_inner_o = '';
var search_query = '';
var split_query = '';
var ajax_template = '';

function reload_list()
{
	cur_page = 0;
	
	$('#search_result').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
	
	$('#search_result_more').html(search_result_more_inner_o);
	
	$('#search_result_more').click();
}

$(document).ready(function()
{
	$('#list_nav a').click(function () {		
		window.location.hash = $(this).attr('href').replace(/#/g, '');
		
		$('#aw-search-type').html($(this).text());
		
		reload_list();
	});
	
	search_result_more_inner_o = $('#search_result_more').html();
	
	$('#search_result_more').click(function()
	{
		var _this = this;
		
		switch (window.location.hash)
		{
			default:
				var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-all__q-' + encodeURIComponent(search_query) + '__template-' + ajax_template + '__page-' + cur_page;
			break;
			
			case '#questions':
			case '#topics':
			case '#users':
				var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-' +  window.location.hash.replace(/#/g, '') + '__q-' + encodeURIComponent(search_query) + '__template-' + ajax_template + '__page-' + cur_page;
			break;
		}
		
		$(this).addClass('loading');
		$(this).find('span').html(_t('正在载入') + '...');
		
		$.get(request_url, function (response)
		{
			if (response.length)
			{
				if (cur_page == 0)
				{
					$('#search_result').html(response);
				}
				else
				{
					$('#search_result').append(response);
				}
				
				$('#search_result .aw-title a').highText(split_query, 'span', 'aw-text-color-red');
					
				cur_page++;
				
				$(_this).html(search_result_more_inner_o);
			}
			else
			{
				if (cur_page == 0)
				{
					$('#search_result').html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
				}
					
				$(_this).addClass('disabled');
				
				$(_this).find('span').html(_t('没有更多了'));
			}
			
			$(_this).removeClass('loading');
			
		});
		
		return false;
	});
	
	switch (window.location.hash)
	{
		default:
			$("#list_nav a[href='#all']").click();
		break;
		
		case '#questions':
		case '#topics':
		case '#users':
			$("#list_nav a[href='" + window.location.hash + "']").click();
		break;
	}
});