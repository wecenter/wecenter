function _t(string, replace)
{	
	if (typeof(aws_lang) == 'undefined')
	{
		if (replace)
		{
			string = string.replace('%s', replace);
		}
		
		return string;
	}
	
	if (aws_lang[string])
	{
		string = aws_lang[string];
		
		if (replace)
		{
			string = string.replace('%s', replace);
		}
		
		return string;
	}	
}

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
				
				if ($('#listview').hasClass('ui-listview'))
				{
					$('#listview').listview('refresh');
				}
					
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

function ajax_post(formEl, processer)	// 表单对象，用 jQuery 获取，回调函数名
{	
	if (typeof(processer) != 'function')
	{
		processer = _ajax_post_processer;
	}
	
	var custom_data = {
		_post_type:'ajax',
		_is_mobile:'true'
	};
	
	$.mobile.loading('show');
	
	formEl.ajaxSubmit({
		dataType: 'json',
		data: custom_data,
		success: processer,
		error:	function (error) { if ($.trim(error.responseText) != '') { $.mobile.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } }
	});
}

function _ajax_post_processer(result)
{
	$.mobile.loading('hide');
	
	if (typeof(result.errno) == 'undefined')
	{
		alert(result);
	}
	else if (result.errno != 1)
	{
		alert(result.err);
	}
	else
	{		
		if (result.rsm && result.rsm.url)
		{
			window.location = decodeURIComponent(result.rsm.url);
		}
		else
		{
			window.location.reload();
		}
	}
}

function ajax_request(url, params)
{
	$.mobile.loading('show');
	
	if (params)
	{
		$.post(url, params, function (result) {
			$.mobile.loading('hide');
			
			if (result.err)
			{
				alert(result.err);
			}
			else if (result.rsm && result.rsm.url)
			{
				window.location = decodeURIComponent(result.rsm.url);
			}
			else
			{
				window.location.reload();
			}
		}, 'json').error(function (error) { if ($.trim(error.responseText) != '') {  $.mobile.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } });
	}
	else
	{
		$.get(url, function (result) {
			$.mobile.loading('hide');
			
			if (result.err)
			{
				alert(result.err);
			}
			else if (result.rsm && result.rsm.url)
			{
				window.location = decodeURIComponent(result.rsm.url);
			}
			else
			{
				window.location.reload();
			}
		}, 'json').error(function (error) { if ($.trim(error.responseText) != '') { $.mobile.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } });
	}
	
	return false;
}

function focus_question(el, text_el, question_id)
{
	if (el.find('span.ui-icon').hasClass('ui-icon-check'))
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.find('span.ui-icon').removeClass('ui-icon-check');
		el.find('span.ui-icon').addClass('ui-icon-delete');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
		el.find('span.ui-icon').removeClass('ui-icon-delete');
		el.find('span.ui-icon').addClass('ui-icon-check');
		el.attr('data-theme', 'b');
	}
	
	$.mobile.loading('show');
	
	$.get(G_BASE_URL + '/question/ajax/focus/question_id-' + question_id, function (data)
	{
		$.mobile.loading('hide');
		
		if (data.errno != 1)
		{
			if (data.err)
			{
				alert(data.err);
			}
			
			if (data.rsm.url)
			{
				window.location = decodeURIComponent(data.rsm.url);
			}
		}
	}, 'json');
}

function focus_topic(el, text_el, topic_id)
{
	if (el.find('span.ui-icon').hasClass('ui-icon-check'))
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.find('span.ui-icon').removeClass('ui-icon-check');
		el.find('span.ui-icon').addClass('ui-icon-delete');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
		el.find('span.ui-icon').removeClass('ui-icon-delete');
		el.find('span.ui-icon').addClass('ui-icon-check');
		el.attr('data-theme', 'b');
	}
	
	$.mobile.loading('show');
	
	$.get(G_BASE_URL + '/topic/ajax/focus_topic/topic_id-' + topic_id, function (data)
	{
		$.mobile.loading('hide');
		
		if (data.errno != 1)
		{
			if (data.err)
			{
				alert(data.err);
			}
			
			if (data.rsm.url)
			{
				window.location = decodeURIComponent(data.rsm.url);
			}
		}
	}, 'json');
}

function follow_people(el, text_el, uid)
{
	if (el.find('span.ui-icon').hasClass('ui-icon-check'))
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.find('span.ui-icon').removeClass('ui-icon-check');
		el.find('span.ui-icon').addClass('ui-icon-delete');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
		el.find('span.ui-icon').removeClass('ui-icon-delete');
		el.find('span.ui-icon').addClass('ui-icon-check');
		el.attr('data-theme', 'b');
	}
	
	$.mobile.loading('show');
	
	$.get(G_BASE_URL + '/follow/ajax/follow_people/uid-' + uid, function (data)
	{
		$.mobile.loading('hide');
		
		if (data.errno != 1)
		{
			if (data.err)
			{
				alert(data.err);
			}
			
			if (data.rsm.url)
			{
				window.location = decodeURIComponent(data.rsm.url);
			}
		}
	}, 'json');
}