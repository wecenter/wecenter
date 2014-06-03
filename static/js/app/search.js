var cur_page = 1;
var search_query = '';
var split_query = '';
var ajax_template = '';
$(function()
{
	$('#list_nav a').click(function ()
	{		
		window.location.hash = $(this).attr('href').replace(/#/g, '');
		
		$('#aw-search-type').html($(this).text());
		
		cur_page = 1;
	
		$('#search_result').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
		
		$('#search_result_more').click();
	});
	
	$('#search_result_more').click(function()
	{
		var _this = this;
				
		var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-' +  window.location.hash.replace(/#/g, '') + '__q-' + encodeURIComponent(search_query) + '__template-' + ajax_template + '__page-' + cur_page;
		
		$(this).addClass('loading');
		
		$.get(request_url, function (response)
		{
			if (response.length)
			{
				if (cur_page == 1)
				{
					$('#search_result').html(response);
				}
				else
				{
					$('#search_result').append(response);
				}
				
				$('#search_result .aw-title a').highText(split_query, 'span', 'aw-text-color-red');
					
				cur_page++;
				
			}
			else
			{
				if (cur_page == 1)
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
		case '#questions':
		case '#topics':
		case '#users':
			$("#list_nav a[href='" + window.location.hash + "']").click();
		break;

		default:
			$("#list_nav a[href='#all']").click();
		break;
	}
});