jQuery.fn.extend({
    highText: function (searchWords, htmlTag, tagClass)
    {
        return this.each(function ()
        {
            $(this).html(function high(replaced, search, htmlTag, tagClass)
            {
                var pattarn = search.replace(/\b(\w+)\b/g, "($1)").replace(/\s+/g, "|");

                return replaced.replace(new RegExp(pattarn, "ig"), function (keyword)
                {
                    return $("<" + htmlTag + " class=" + tagClass + ">" + keyword + "</" + htmlTag + ">").outerHTML();
                });
            }($(this).text(), searchWords, htmlTag, tagClass));
        });
    },
    outerHTML: function (s)
    {
        return (s) ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
    }
});

$(function() {
	$('img#captcha').attr('src', G_BASE_URL + '/account/captcha/');
	
	$('#aw-top-nav-profile').click(function(){
		$('.aw-top-nav-popup').hide();
		$('.aw-top-nav-profile').show();
	});

	$('#aw-top-nav-notic').click(function()
	{
		$('.aw-top-nav-popup').hide();
		$('.aw-top-nav-notic').toggle();
	})

	//关闭按钮
	$('.slide-close').click(function()
	{
		$(this).parents('.aw-top-nav-popup').hide();
	});

	//点击下拉菜单外得地方隐藏
	$(document).click(function(e)
	{
		var target = $(e.target);
		if (target.closest('#aw-top-nav-notic, #aw-top-nav-profile, .aw-top-nav-popup, .dropdown-list').length == 0)
		{
			$('.aw-top-nav-popup, .dropdown-list').hide();
		}
	});

	$('.aw-publish').click(function()
	{
		alert_box('publish');
	});
	$('.aw-message').click(function()
	{
		alert_box('message');
	});

	search_dropdown('.aw-search-input');
	
});

/* 弹窗 */
function alert_box(type , data)
{
	var template;
	switch (type)
	{
		case 'publish' : 
			template = Hogan.compile(AW_MOBILE_TEMPLATE.publish).render({
		
			});
		break;

		case 'redirect' : 
			template = Hogan.compile(AW_MOBILE_TEMPLATE.redirect).render({
				'data-id' : data
			});
		break;

		case 'message' :
			template = Hogan.compile(AW_MOBILE_TEMPLATE.message).render({
				'data-name' : data
			});
		break;
	}
	if (template)
	{
		$('#aw-ajax-box').html('').append(template);
		switch (type)
		{
			case 'message' :
				message_dropdown('.aw-message-input');
			break;
		}
	}
	
	$('.alert-' + type).modal('show');
}

/* 
*	** 搜索下拉 ** 
*	aw_search_interval 定时器
*	aw_search_flag 是否已经开始发送请求标识
*/
var aw_search_interval,aw_search_flag = 0;
function search_dropdown(element)
{
	var ul = $(element).next().find('ul');
	$(element).keydown(function()
	{
		if (aw_search_flag == 0)
		{
			aw_search_interval = setInterval(function()
			{
				if ($(element).val().length >= 2)
				{
					$.get(G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent($(element).val()) + '&limit=5',function(result)
					{
						if (result.length > 0)
						{
							ul.html('');
							// type1 : 问题 , type2 : 话题 best_answer最佳回答, type3 : 用户
							for (var i=0; i < result.length; i++)
							{
								switch(parseInt(result[i].type))
								{
									case 1 :
										ul.append('<li><a href="?/m/' + decodeURIComponent(result[i].url) + '">' + result[i].name + '<span class="num">' + result[i].detail.answer_count + ' 个回答</span></a></li>');
										break;
									case 2 :
										ul.append('<li><a class="aw-topic-name" href="?/m/' + decodeURIComponent(result[i].url) + '">' + result[i].name  + '</a><span class="num">' + result[i].detail.discuss_count + ' 个问题</span></li>');
										break;

									case 3 :
										ul.append('<li><a href="?/m/' + decodeURIComponent(result[i].url) + '"><img src="' + result[i].detail.avatar_file + '"><span>' + result[i].name + '</span></a></li>');
										break;
								}
							}
							$(element).next().show();
						}else
						{
							$(element).next().hide();
						}
					},'json');
				}
				else
				{
					$(element).next().hide();
				}
			},1000);
			aw_search_flag = 1;
			return aw_search_interval;
		}
	});
	$(element).blur(function()
	{
		clearInterval(aw_search_interval);
		aw_search_flag = 0;
	});
}

/* 
*	** 私信用户下拉 ** 
*	aw_message_interval 定时器
*	aw_message_flag 是否已经开始发送请求标识
*/
var aw_message_interval,aw_message_flag = 0;
function message_dropdown(element)
{
	var ul = $(element).next().find('ul');
	$(element).keydown(function()
	{
		if (aw_message_flag == 0)
		{
			aw_message_interval = setInterval(function()
			{
				if ($(element).val().length >= 2)
				{
					$.get(G_BASE_URL + '/search/ajax/search/?type-user__q-' + encodeURIComponent($(element).val()) + '__limit-10',function(result)
					{
						if (result.length > 0)
						{
							ul.html('');
							$.each(result ,function(i, e)
							{
								ul.append('<li><a><img src="' + result[i].detail.avatar_file + '"><span>' + result[i].name + '</span></a></li>')
							});	
							$('.alert-message .dropdown-list ul li').click(function()
							{
								$(element).val($(this).find('span').html());
								$(element).next().hide();
							});		
							$(element).next().show();
						}else
						{
							$(element).next().hide();
						}
					},'json');
				}
				else
				{
					$(element).next().hide();
				}
			},1000);
			aw_message_flag = 1;
			return aw_message_interval;
		}
	});
	$(element).blur(function()
	{
		clearInterval(aw_message_interval);
		aw_message_flag = 0;
	});
}




var aw_loading_timer;
var aw_loading_bg_count = 12;

$.loading = function (s) {
	if ($('#aw-loading').length == 0)
    {
        $('#aw-ajax-box').append('<div id="aw-loading" class="hide"><div id="aw-loading-box"></div></div>');
    }
    
	if (s == 'show')
	{
		$('#aw-loading').fadeIn();
	
		aw_loading_timer = setInterval(function () {
			aw_loading_bg_count = aw_loading_bg_count - 1;
			
			$('#aw-loading-box').css('background-position', '0px ' + aw_loading_bg_count * 40 + 'px');
			
			if (aw_loading_bg_count == 1)
			{
				aw_loading_bg_count = 12;
			}
		}, 100);
	}
	else
	{
		$('#aw-loading').fadeOut();
	
		clearInterval(aw_loading_timer);
	}
};

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
			
		$.loading('show');
	
		$(_this).addClass('disabled');
			
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
				
				_list_view_pages[ul_button.attr('id')]++; 
				
				$(_this).removeClass('disabled');
			}
			else
			{
				if ($.trim(list_view.html()) == '')
				{
					list_view.append('<p class="empty_message">没有相关内容</p>');
				}
							
				$(_this).unbind('click').bind('click', function () { return false; });
			}
				
			$.loading('hide');
			
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
	
	$.loading('show');
	
	formEl.ajaxSubmit({
		dataType: 'json',
		data: custom_data,
		success: processer,
		error:	function (error) { if ($.trim(error.responseText) != '') { $.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } }
	});
}

function _ajax_post_processer(result)
{
	$.loading('hide');
	
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
	$.loading('show');
	
	if (params)
	{
		$.post(url, params, function (result) {
			$.loading('hide');
			
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
		}, 'json').error(function (error) { if ($.trim(error.responseText) != '') {  $.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } });
	}
	else
	{
		$.get(url, function (result) {
			$.loading('hide');
			
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
		}, 'json').error(function (error) { if ($.trim(error.responseText) != '') { $.loading('hide'); alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText); } });
	}
	
	return false;
}

function focus_question(el, text_el, question_id)
{
	if (el.hasClass('aw-active'))
	{
		text_el.html(_t('关注'));
		
		el.removeClass('aw-active');
	}
	else
	{
		text_el.html(_t('取消关注'));
		
		el.addClass('aw-active');
	}
	
	$.loading('show');
	
	$.get(G_BASE_URL + '/question/ajax/focus/question_id-' + question_id, function (data)
	{
		$.loading('hide');
		
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
	if (el.hasClass('aw-active'))
	{
		text_el.html(_t('关注'));
		
		el.removeClass('aw-active');
	}
	else
	{
		text_el.html(_t('取消关注'));
		
		el.addClass('aw-active');
	}
	
	$.loading('show');
	
	$.get(G_BASE_URL + '/topic/ajax/focus_topic/topic_id-' + topic_id, function (data)
	{
		$.loading('hide');
		
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
		
		el.addClass('ui-btn-up-d');
		el.attr('data-theme', 'd');
	}
	else
	{
		text_el.html(_t('关注'));
		
		el.removeClass('ui-btn-up-d').removeClass('ui-btn-hover-d');
		
		el.addClass('ui-btn-up-b');
		el.attr('data-theme', 'b');
	}
	
	$.loading('show');
	
	$.get(G_BASE_URL + '/follow/ajax/follow_people/uid-' + uid, function (data)
	{
		$.loading('hide');
		
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
	$.loading('show');
	
	$.post(G_BASE_URL + '/question/ajax/question_answer_rate/', 'type=' + type + '&answer_id=' + answer_id, function (result) {
		
		$.loading('hide');
		
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

function _ajax_post_confirm_processer(result)
{
	$.loading('hide');
	
	if (typeof(result.errno) == 'undefined')
	{
		alert(result);
	}
	else if (result.errno != 1)
	{
		if (!confirm(result.err))
		{
			return false;	
		}
	}
	
	if (result.errno == 1 && result.err)
	{
		alert(result.err);
	}
	
	if (result.rsm && result.rsm.url)
	{
		window.location = decodeURIComponent(result.rsm.url);
	}
	else
	{
		window.location.reload();
	}
}

function answer_vote(element, answer_id, val)
{
	var data_theme = element.attr('data-theme');
	
	$('.ui-dialog').dialog('close');
	
	$.loading('show');
	
	$.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=' + val, function (result) {
		$.loading('hide');
		
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