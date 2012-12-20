jQuery.fn.extend({
	highText : function (searchWords, htmlTag, tagClass) {
		return this.each(function() {
			$(this).html(function high(replaced, search, htmlTag, tagClass) {
				var pattarn = search.replace(/\b(\w+)\b/g, "($1)").replace(/\s+/g, "|");
				
				return replaced.replace(new RegExp(pattarn, "ig"), function(keyword) {
					return $("<" + htmlTag + " class=" + tagClass + ">" + keyword + "</" + htmlTag + ">").outerHTML();
				});
			}($(this).text(), searchWords, htmlTag, tagClass));
		});
	},
	outerHTML : function(s) {
		return (s) ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
	}
});

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

var _list_view_pages = new Array();

function load_list_view(url, list_view, ul_button, start_page, callback_func)
{	
	if (!ul_button.attr('id'))
	{
		return false;
	}
	
	if (!start_page)
	{
		start_page = 0
	}
	
	_list_view_pages[ul_button.attr('id')] = start_page;
	
	ul_button.unbind('click');
	
	ul_button.bind('click', function () {
		var _this = this;
			
		$.mobile.loading('show');
	
		$(_this).addClass('ui-disabled');
			
		$.get(url + '__page-' + _list_view_pages[ul_button.attr('id')], function (response)
		{			
			if ($.trim(response) != '')
			{
				if (_list_view_pages[ul_button.attr('id')] == start_page)
				{
					list_view.html(response);
				}
				else
				{
					list_view.append(response);
				}
				
				if (list_view.hasClass('ui-listview'))
				{
					list_view.listview('refresh');
				}
					
				_list_view_pages[ul_button.attr('id')]++; 
				
				$(_this).removeClass('ui-disabled');
			}
			else
			{
				if ($.trim(list_view.html()) == '')
				{
					list_view.append('<p class="empty_message">没有相关内容</p>');
				}
							
				$(_this).unbind('click').bind('click', function () { return false; });
			}
				
			$.mobile.loading('hide');
			
			if (callback_func != null)
			{
				callback_func();
			}
			
			$(_this).removeClass('ui-btn-active');
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
	if (el.attr('data-theme') == 'b')
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
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
	if (el.attr('data-theme') == 'b')
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
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
	if (el.attr('data-theme') == 'b')
	{
		text_el.html(_t('取消关注'));
		
		el.removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
		el.addClass('ui-btn-up-c');
		el.attr('data-theme', 'c');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-c').removeClass('ui-btn-hover-c');
		
		el.addClass('ui-btn-up-b');
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

function answer_user_rate(answer_id, type, element)
{
	$.mobile.loading('show');
	
	$.post(G_BASE_URL + '/question/ajax/question_answer_rate/', 'type=' + type + '&answer_id=' + answer_id, function (result) {
		
		$.mobile.loading('hide');
		
		if (result.errno != 1)
		{
			alert(result.err);
		}
		else if (result.errno == 1)
		{
			switch (type)
			{
				case 'thanks':
					if (result.rsm.action == 'add')
					{
						$(element).find('span.ui-btn-text').html(_t('已感谢'));
						$(element).removeAttr('onclick');
					}
					else
					{
						$(element).html(_t('感谢'));
					}
				break;
				
				case 'uninterested':
					if (result.rsm.action == 'add')
					{
						$(element).find('span.ui-btn-text').html(_t('撤消没有帮助'));
					}
					else
					{
						$(element).find('span.ui-btn-text').html(_t('没有帮助'));
					}
				break;
			}
		}
	}, 'json');
}

function answer_vote(element, answer_id, val)
{
	var data_theme = element.attr('data-theme');
	
	$('.ui-dialog').dialog('close');
	
	$.mobile.loading('show');
	
	$.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=' + val, function (result) {
		$.mobile.loading('hide');
		
		if (data_theme == 'd')
		{
			$('#answer_vote_button').removeClass('ui-btn-up-d').removeClass('ui-btn-hover-d');
		
			$('#answer_vote_button').addClass('ui-btn-up-b');
			$('#answer_vote_button').attr('data-theme', 'b');
			
			if (parseInt(val) > 0)
			{
				$('#answer_vote_button').find('span.ui-btn-text').html((parseInt($('#answer_vote_button').find('span.ui-btn-text').text()) + parseInt(val)));
			}
		}
		else
		{
			$('#answer_vote_button').removeClass('ui-btn-up-b').removeClass('ui-btn-hover-b');
		
			$('#answer_vote_button').addClass('ui-btn-up-d');
			$('#answer_vote_button').attr('data-theme', 'd');
			
			if (parseInt(val) > 0)
			{
				$('#answer_vote_button').find('span.ui-btn-text').html((parseInt($('#answer_vote_button').find('span.ui-btn-text').text()) - parseInt(val)));
			}
		}
	});
}