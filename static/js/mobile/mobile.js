var _ul_buttons = new Array();
var _bp_more_pages = new Array();

function load_list_view(url, ul_button, start_page, callback_func)
{
	if (!ul_button.attr('id'))
	{
		return false;
	}
	
	if (!start_page)
	{
		start_page = 0
	}
	
	_bp_more_pages[ul_button.attr('id')] = start_page;
	
	_ul_buttons[ul_button.attr('id')] = ul_button.html();
	
	ul_button.unbind('click');
	
	ul_button.bind('click', function () {
		var _this = this;
			
		$.mobile.loading('show');
	
		$(_this).addClass('ui-disabled');
			
		$.get(url + '__page-' + _bp_more_pages[ul_button.attr('id')], function (response)
		{
			if ($.trim(response) != '')
			{
				if (_bp_more_pages[ul_button.attr('id')] == start_page)
				{
					$('#listview').html(response);
				}
				else
				{
					$('#listview').append(response);
				}
				
				$('#listview').listview('refresh');
							
				_bp_more_pages[ul_button.attr('id')]++; 
				
				$(_this).removeClass('ui-disabled');
			}
			else
			{				
				$(_this).unbind('click').bind('click', function () { return false; });
			}
				
			$.mobile.loading('hide');
			
			if (callback_func != null)
			{
				callback_func();
			}
		});
			
		return false;
	});
	
	ul_button.click();
}