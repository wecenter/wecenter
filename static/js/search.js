var cur_page = 0;
var bp_more_inner_o = '';
var search_query = '';
var split_query = '';

function reload_list()
{
	cur_page = 0;
	
	$('#search_result').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
	
	$('#bp_more').html(bp_more_inner_o);
	
	$('#bp_more').click();
}

$(document).ready(function()
{
	$('#list_nav a').click(function () {
		$('#list_nav a').removeClass('cur');
		
		$(this).addClass('cur');
		
		window.location.hash = $(this).attr('href').replace(/#/g, '');
		
		$('#search_type').html($(this).text());
		
		reload_list();
		
		return false;
	});
	
	bp_more_inner_o = $('#bp_more').html();
	
	$('#bp_more').click(function()
	{
		var _this = this;
		
		switch (window.location.hash)
		{
			default:
				var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-all__q-' + encodeURIComponent(search_query) + '__page-' + cur_page;
			break;
			
			case '#questions':
			case '#topics':
			case '#users':
				var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-' +  window.location.hash.replace(/#/g, '') + '__q-' + encodeURIComponent(search_query) + '__page-' + cur_page;
			break;
		}
		
		$(this).addClass('loading');
		$(this).find('a').html(_t('正在载入') + '...');
		
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
				
				$('#search_result li p.keyword a').highText(split_query, 'span', 'i_red');
				
				/*$.each($('#search_result li'), function (i, e) {
					if ($(this).attr('focus') == 'true')
					{
						$('#hide_items_list').append('<li class="' + e.className + '">' + $(e).html() + '</li>');
			
						$(e).remove();
					}
				});
				
				if ($('#hide_items_list li').length > 0)
				{
					$('#hide_items_control span.items_count').html($('#hide_items_list li').length);
					$('#hide_items_control').fadeIn();
				}*/
					
				cur_page++;
				
				$(_this).html(bp_more_inner_o);
			}
			else
			{
				if (cur_page == 0)
				{
					$('#search_result').html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
				}
					
				$(_this).addClass('disabled');
				
				$(_this).find('a').html(_t('没有更多了'));
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
	
	//$('#bp_more').click();
});