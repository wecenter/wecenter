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

$(window).on('hashchange', function() {
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
});

$(document).ready(function () {
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
	
	init_comment_box('.aw-add-comment');
	
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

	//邀请回答按钮
	$('.aw-invite-replay-btn').click(function()
	{
		if ($(this).parents('.aw-question-detail').find('.aw-invite-replay').is(':visible'))
		{
			$(this).parents('.aw-question-detail').find('.aw-invite-replay').hide();
		}else
		{
			$(this).parents('.aw-question-detail').find('.aw-invite-replay').show();
		}
	});

	/* 点击下拉菜单外得地方隐藏　*/
	$(document).click(function(e)
	{
		var target = $(e.target);
		if (target.closest('#aw-top-nav-notic, #aw-top-nav-profile, .aw-top-nav-popup, .dropdown-list').length == 0)
		{
			$('.aw-top-nav-popup, .dropdown-list').hide();
		}
	});
	
	/* 私信 */
	$('.aw-message').click(function()
	{
		alert_box('message');
	});

	/* 话题编辑删除按钮 */
	$(document).on('click', '.aw-question-detail .aw-topic-edit-box .aw-topic-box i', function()
	{
		var _this = $(this);
		$.post(G_BASE_URL + '/question/ajax/delete_topic/?question_id', {'question_id' : $(this).parents('.aw-topic-edit-box').attr('data-id'), 'topic_id' : $(this).parents('.aw-topic-name').attr('data-id')} , function(result)
		{
			if (result.errno == 1) 
			{
				_this.parents('.aw-topic-name').detach();
			}else
			{
				alert(result.err);
			}
		}, 'json');
		return false;
	});

	dropdown_list('.aw-search-input','search');
	dropdown_list('.aw-invite-input','invite');
	add_topic_box('.aw-add-topic-box','question');

});

/* 弹窗 */
function alert_box(type , data)
{
	var template;
	
	switch (type)
	{
		case 'publish' : 
			template = Hogan.compile(AW_MOBILE_TEMPLATE.publish).render({
	            'category_id': data.category_id,
	            'ask_user_id': data.ask_user_id
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
		$('#aw-ajax-box').empty().append(template);
		
		switch (type)
		{
			case 'message' :
				dropdown_list('.aw-message-input','message');
			break;
			
			case 'redirect' : 
				dropdown_list('.aw-redirect-input','redirect');
			break;

			case 'publish' :
				if (parseInt(data.category_enable) == 1)
	        	{
		        	$.get(G_BASE_URL + '/publish/ajax/fetch_question_category/', function (result)
		            {
		                add_dropdown_list('.aw-publish-title-dropdown', eval(result), data.category_id);
		
		                $('.aw-publish-title-dropdown li a').click(function ()
		                {
		                    $('#quick_publish_category_id').val($(this).attr('data-value'));
		                });
		            });
		            
		            $('#quick_publish_topic_chooser').hide();
	        	}
	        	else
	        	{
		        	$('#quick_publish_category_chooser').hide();
	        	}
	
	            if ($('#aw-search-query').val() && $('#aw-search-query').val() != $('#aw-search-query').attr('placeholder'))
	            {
		            $('#quick_publish_question_content').val($('#aw-search-query').val());
	            }
				
	            $('#quick_publish .aw-edit-topic').click();
	            
	            if (G_QUICK_PUBLISH_HUMAN_VALID)
	            {
		            $('#quick_publish_captcha').show();
		            $('#captcha').click();
	            }
				
				add_topic_box('.alert-publish .aw-topic-edit-box .aw-add-topic-box', 'publish');
			break;
		}
	}
	
	$('.alert-' + type).modal('show');
}

/* 下拉列表 */
var aw_dropdown_list_interval, aw_dropdown_list_flag = 0;
function dropdown_list(element, type)
{
	var ul = $(element).next().find('ul');
	$(element).keydown(function()
	{
		if (aw_dropdown_list_flag == 0)
		{
			aw_dropdown_list_interval = setInterval(function()
			{
				if ($(element).val().length >= 2)
				{
					switch (type)
					{
						case 'search' : 
							$.get(G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent($(element).val()) + '&limit=5',function(result)
							{
								if (result.length > 0)
								{
									ul.html('');
									// type1 : 问题 , type2 : 话题 best_answer最佳回答, type3 : 用户
									$.each(result, function(i, e)
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
									});
									$(element).next().show();
								}else
								{
									$(element).next().hide();
								}
							},'json');
						break;

						case 'message' :
							$.get(G_BASE_URL + '/search/ajax/search/?type-user__q-' + encodeURIComponent($(element).val()) + '__limit-10',function(result)
							{
								if (result.length > 0)
								{
									ul.html('');
									$.each(result ,function(i, e)
									{
										ul.append('<li><a><img src="' + result[i].detail.avatar_file + '"><span>' + result[i].name + '</span></a></li>')
									});	
									$(element).next().show();
								}else
								{
									$(element).next().hide();
								}
							},'json');
						break;

						case 'invite' : 
							$.get(G_BASE_URL + '/search/ajax/search/?type-user__q-' + encodeURIComponent($(element).val()) + '__limit-10',function(result)
							{
								if (result.length > 0)
								{
									ul.html('');
									$.each(result ,function(i, e)
									{
										ul.append('<li><a data-id="' + result[i].uid + '"><img src="' + result[i].detail.avatar_file + '"><span>' + result[i].name + '</span></a></li>')
									});	
									$('.aw-invite-replay .dropdown-list ul li a').click(function()
									{
										$.post(G_BASE_URL + '/question/ajax/save_invite/',
									    {
									        'question_id': QUESTION_ID,
									        'uid': $(this).attr('data-id')
									    },function(result)
									    {
									    	if (result.errno == -1)
									    	{
									    		alert(result.err);
									    	}else
									    	{
									    		location.reload();
									    	}
									    }, 'json');
									});
									$(element).next().show();
								}else
								{
									$(element).next().hide();
								}
							},'json');
						break;

						case 'redirect' :
							$.get(G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent($(element).val()) + '&type=question&limit-30',function(result)
							{
								if (result.length > 0)
								{
									ul.html('');
									$.each(result ,function(i, e)
									{
										ul.append('<li><a onclick="ajax_request(' + "'" + G_BASE_URL + "/question/ajax/redirect/', 'item_id=" + $(element).attr('data-id') + "&target_id=" + result[i].sno + "'" +')">' + result[i].name +'</a></li>')
									});	
									$(element).next().show();
								}else
								{
									$(element).next().hide();
								}
							},'json');
						break;

						case 'topic' :
							$.get(G_BASE_URL + '/search/ajax/search/?type-topic__q-' + encodeURIComponent($(element).val()) + '__limit-10',function(result)
							{
								if (result.length > 0)
								{
									ul.html('');
									$.each(result ,function(i, e)
									{
										ul.append('<li><a>' + result[i].name +'</a></li>')
									});	
									$('.aw-topic-edit-box .dropdown-list ul li').click(function()
									{
										var _this = $(this);
										$.post(G_BASE_URL + '/question/ajax/save_topic/question_id-' + $(this).parents('.aw-topic-edit-box').attr('data-id'), 'topic_title=' + $(this).text(), function(result)
										{
											if (result.errno == 1)
											{
												$(element).parents('.aw-topic-edit-box').find('.aw-topic-box').prepend('<a class="aw-topic-name" data-id="' + result.rsm.topic_id + '">' + _this.text() + '<i>X</i></a>');
												$(element).val('');
												$(element).next().hide();
											}else
											{
												alert(result.err);
											}
										}, 'json');
									});
									$(element).next().show();
								}else
								{
									$(element).next().hide();
								}
							},'json');
						break;
					}
				}
				else
				{
					$(element).next().hide();
				}
			},1000);

			switch (type)
			{
				case 'message' :
					$('.alert-message .dropdown-list ul li').click(function()
					{
						$(element).val($(this).find('span').html());
						$(element).next().hide();
					});
				break;

				case 'invite' : 
				break;
			}
			aw_dropdown_list_flag = 1;
			return aw_dropdown_list_interval;
		}
	});
	$(element).blur(function()
	{
		clearInterval(aw_dropdown_list_interval);
		aw_dropdown_list_flag = 0;
	});
}

/* 话题编辑 */
function add_topic_box(element, type)
{
	$(element).click(function()
	{
		var data_id = $(this).parents('.aw-topic-edit-box').attr('data-id');
		$(element).hide();
		$(element).parents('.aw-topic-edit-box').append(AW_MOBILE_TEMPLATE.topic_edit_box);
		$.each($(element).parents('.aw-topic-edit-box').find('.aw-topic-name'), function(i, e)
		{
			if (!$(e).has('i')[0])
			{

				$(e).append('<i>X</i>');
			}
		});
		dropdown_list('.aw-topic-box-selector .aw-topic-input','topic');
		/* 话题编辑添加按钮 */
		$('.aw-topic-box-selector .add').click(function()
		{
			switch (type)
			{
				case 'publish' :
					$(this).parents('.aw-topic-edit-box').find('.aw-topic-box').prepend('<a class="aw-topic-name">' + $(this).parents('.aw-topic-box-selector').find('.aw-topic-input').val() + '<i onclick="$(this).parents(\'.aw-topic-name\').detach();">X</i></a>');
					$(this).parents('.aw-topic-edit-box').find('.aw-topic-input').val('');
					$(this).parents('.aw-topic-edit-box').find('.dropdown-list').hide();
				break;
				case 'question' :
					var _this = $(this);
					$.post(G_BASE_URL + '/question/ajax/save_topic/question_id-' + data_id, 'topic_title=' + $(this).parents('.aw-topic-box-selector').find('.aw-topic-input').val(), function(result)
					{
						if (result.errno == 1)
						{
							_this.parents('.aw-topic-edit-box').find('.aw-topic-box').prepend('<a class="aw-topic-name" data-id="'+ result.rsm.topic_id +'">' + _this.parents('.aw-topic-box-selector').find('.aw-topic-input').val() + '<i>X</i></a>');
							_this.parents('.aw-topic-edit-box').find('.aw-topic-input').val('');
							_this.parents('.aw-topic-edit-box').find('.dropdown-list').hide();
						}else
						{
							alert(result.err);
						}
					}, 'json');
				break;
			}
			
		});
		/* 话题编辑取消按钮 */
		$('.aw-topic-box-selector .cancel').click(function()
		{
			$(this).parents('.aw-topic-edit-box').find('.aw-add-topic-box').show();
			$.each($(this).parents('.aw-topic-edit-box').find('.aw-topic-name'), function(i, e)
			{
				if ($(e).has('i')[0])
				{
					$(e).find('i').detach();
				}
			});
			$(this).parents('.aw-topic-box-selector').detach();
		});
	});
}

/*取消邀请*/
function disinvite_user(obj, uid)
{
    $.get(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + QUESTION_ID + "__recipients_uid-" + uid);
}

function _quick_publish_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        $('#quick_publish_error em').html(result.err);
        $('#quick_publish_error').fadeIn();
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

function init_comment_box(selecter)
{
    $(document).on('click', selecter, function ()
    {
        if (!$(this).attr('data-type') || !$(this).attr('data-id'))
        {
            return true;
        }

        var comment_box_id = '#aw-comment-box-' + $(this).attr('data-type') + '-' + 　$(this).attr('data-id');
		
        if ($(comment_box_id).length > 0)
        {
            if ($(comment_box_id).css('display') == 'none')
            {
                $(comment_box_id).fadeIn();
            }
            else
            {
                $(comment_box_id).fadeOut();
            }
        }
        else
        {
            // 动态插入commentBox
            switch ($(this).attr('data-type'))
            {
	            case 'question':
	                var comment_form_action = G_BASE_URL + '/question/ajax/save_question_comment/question_id-' + $(this).attr('data-id');
	                var comment_data_url = G_BASE_URL + '/question/ajax/get_question_comments/question_id-' + $(this).attr('data-id');
	                break;
	
	            case 'answer':
	                var comment_form_action = G_BASE_URL + '/question/ajax/save_answer_comment/answer_id-' + $(this).attr('data-id');
	                var comment_data_url = G_BASE_URL + '/question/ajax/get_answer_comments/answer_id-' + $(this).attr('data-id');
	                break;
            }

            if (G_USER_ID && $(this).attr('data-close') != 'true')
            {
                $(this).parents('.aw-mod-footer').append(Hogan.compile(AW_MOBILE_TEMPLATE.commentBox).render(
                {
                    'comment_form_id': comment_box_id.replace('#', ''),
                    'comment_form_action': comment_form_action
                }));
				
                $(comment_box_id).find('.aw-comment-txt').bind(
                {
                    focus: function ()
                    {
                        $(this).css('height', parseInt($(this).css('line-height')) * 5);

                        $(comment_box_id).find('.aw-comment-box-btn').show();
                    },

                    blur: function ()
                    {
                        if ($(this).val() == '')
                        {
                            $(this).css('height', parseInt($(this).css('line-height')));

                            $(comment_box_id).find('.aw-comment-box-btn').hide();
                        }
                    }
                });

                $(comment_box_id).find('.close-comment-box').click(function ()
                {
                    $(comment_box_id).fadeOut();
                    $(comment_box_id).find('.aw-comment-txt').css('height', $(this).css('line-height'));
                });
            }
            else
            {
                $(this).parent().parent().append(Hogan.compile(AW_TEMPLATE.commentBoxClose).render(
                {
                    'comment_form_id': comment_box_id.replace('#', ''),
                    'comment_form_action': comment_form_action
                }));
            }

            //判断是否有评论数据
            $.get(comment_data_url, function (result)
            {
                if (!result)
                {
                    result = '<div align="center" class="aw-padding10">' + _t('暂无评论') + '</div>';
                }

                $(comment_box_id).find('.aw-comment-list').html(result);
            });

            var left = $(this).width()/2 + $(this).prev().width();
            /*给三角形定位*/
            $(comment_box_id).find('.i-comment-triangle').css('left', $(this).width() / 2 + $(this).prev().width() + 15);
        }
    });
}

function save_comment(save_button_el)
{
    $(save_button_el).attr('_onclick', $(save_button_el).attr('onclick')).addClass('disabled').removeAttr('onclick').addClass('_save_comment');

    ajax_post($(save_button_el).parents('form'), _comments_form_processer);
}

function _comments_form_processer(result)
{
    $.each($('a._save_comment.disabled'), function (i, e)
    {

        $(e).attr('onclick', $(this).attr('_onclick')).removeAttr('_onclick').removeClass('disabled').removeClass('_save_comment');
    });

    if (result.errno != 1)
    {
        $.alert(result.err);
    }
    else
    {
        reload_comments_list(result.rsm.item_id, result.rsm.item_id, result.rsm.type_name);

        $('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form input').val('');
        $('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form').fadeOut();
    }
}

function remove_comment(el, type, comment_id)
{
    $(el).parents('li').fadeOut('slow', function ()
    {
        $(this).remove();

        $.get(G_BASE_URL + '/question/ajax/remove_comment/type-' + type + '__comment_id-' + comment_id);
    });
}

function reload_comments_list(item_id, element_id, type_name)
{
    $('#aw-comment-box-' + type_name + '-' + element_id + ' .aw-comment-list').html('<p align="center" class="aw-padding10"><i class="aw-loading"></i></p>');

    $.get(G_BASE_URL + '/question/ajax/get_' + type_name + '_comments/' + type_name + '_id-' + item_id, function (data)
    {
        $('#aw-comment-box-' + type_name + '-' + element_id + ' .aw-comment-list').html(data);
    });
}

function question_thanks(question_id, element)
{
    $.post(G_BASE_URL + '/question/ajax/question_thanks/', 'question_id=' + question_id, function (result)
    {
        if (result.errno != 1)
        {
            $.alert(result.err);
        }
        else if (result.rsm.action == 'add')
        {
            $(element).html($(element).html().replace(_t('感谢'), _t('已感谢')));
            $(element).removeAttr('onclick');
        }
        else
        {
            $(element).html($(element).html().replace(_t('已感谢'), _t('感谢')));
        }
    }, 'json');
}