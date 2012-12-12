function eventsMouseM() {}

$(document).ready(function(){
	
	$(".top .backbtn  .s_open").toggle(function(){
		 
		$(".search").css({"display":"block",})
		$(this).addClass("search_hover")
	},function(){
		 
		$(".search").css({"display":"none",})
		$(this).removeClass("search_hover")
		
	}); 
  	$(".contents .re .head .order").click(function(){ 
		$(".contents .re .head .order").css({"color":"#999",})
		$(this).css({ "color":"#333",  });
	}); 
	
		$(".top .backbtn .set").click(function(){ 
		var x = $(".menu").css('display');
		
		if( x == "none" ){$(".menu").css({ "display":"block"});}
		else {$(".menu").css({ "display":"none"}); };
		}); 
	$('.top').siblings().click(function(){
		
		$(".menu").css({ "display":"none"});
		
		});
	$('.menu').click(function(){
		
		$(".menu").css({ "display":"block"});
		
		});



});

var loading_timer;
var loading_bg_count = 12;

function show_loading()
{
	$('#loading').fadeIn();
	
	loading_timer = setInterval(function () {
		loading_bg_count = loading_bg_count - 1;
		
		$('#loading_box').css('background-position', '0px ' + loading_bg_count * 40 + 'px');
		
		if (loading_bg_count == 1)
		{
			loading_bg_count = 12;
		}
	}, 100)
}

function hide_loading()
{
	$('#loading').fadeOut();
	
	clearInterval(loading_timer);
}

function mobile_ajax_post(formEl, processer)
{
	show_loading();

	if (!processer)
	{
		processer = _ajax_post_mobile_processer;
	}
	
	ajax_post(formEl, processer);
}

function _mobile_tips_form_processer(result)
{
	if (typeof(result.errno) == 'undefined')
	{
		alert(result);
	}
	else if (result.errno != 1)
	{		
		if (typeof(result.rsm) == 'undefined')
		{
			if (document.getElementById('tip_error_message'))
			{
				$('#tip_error_message').html(result.err).show();
			}	
			else
			{
				$.alert(result.err);
			}
		}
		else if (result.rsm)
		{	
			var selecter = 'input[name=' + result.rsm.input + '], select[name=' + result.rsm.input + ']';
			
			if (document.getElementById('tip_' + result.rsm.tips_id))
			{
				$('#tip_' + result.rsm.tips_id).html(result.err).show();
			}
			else if ($('#tip_' + $(selecter).attr('id')).attr('id'))
			{
				if (!$('#tip_' + $(selecter).attr('id')).hasClass('default_err') && !$('#tip_' + $(selecter).attr('id')).hasClass('all_err_tips'))
				{
					$('#tip_' + $(selecter).attr('id')).removeClass().addClass('err').html(result.err).show();
				}
				else
				{
					$('#tip_' + $(selecter).attr('id')).html(result.err).show();
				}
			}
			else if (document.getElementById('tip_error_message'))
			{
				$('#tip_error_message').html(result.err).show();
			}		
			else
			{
				$.alert(result.err);
			}
		}
		else
		{
			if (document.getElementById('tip_error_message'))
			{
				$('#tip_error_message').html(result.err).show();
			}	
			else
			{
				$.alert(result.err);
			}
		}
	}
	else
	{
		if (result.rsm && result.rsm.url)
		{
			window.location = decodeURIComponent(result.rsm.url);
		}
		else if (result.err && document.getElementById('tip_success_message'))
		{
			$.scrollTo(0, 800, {queue:true});
			
			$('#tip_success_message').html(result.err).fadeIn();
			
			setTimeout(function () {
				$('#tip_success_message').fadeOut();
			}, 3000);
		}
		else
		{
			window.location.reload();
		}
	}
	
	hide_loading();
}

var _mobile_more_o_inners = new Array();
var _mobile_more_pages = new Array();

function mobile_more_load(url, mobile_more_o_inner, target_el, start_page, callback_func)
{
	if (!mobile_more_o_inner.attr('id'))
	{
		return false;
	}
	
	if (!start_page)
	{
		start_page = 0
	}
	
	_mobile_more_pages[mobile_more_o_inner.attr('id')] = start_page;
	
	_mobile_more_o_inners[mobile_more_o_inner.attr('id')] = mobile_more_o_inner.html();
	
	mobile_more_o_inner.unbind('click');
	
	mobile_more_o_inner.bind('click', function () {
		var _this = this;
		
		show_loading();
		
		$(this).addClass('loading');
		
		$(this).find('a').html('正在载入...');
			
		$.get(url + '__page-' + _mobile_more_pages[mobile_more_o_inner.attr('id')], function (response)
		{
			if ($.trim(response) != '')
			{
				if (_mobile_more_pages[mobile_more_o_inner.attr('id')] == start_page)
				{
					target_el.html(response);
				}
				else
				{
					target_el.append(response);
				}
							
				_mobile_more_pages[mobile_more_o_inner.attr('id')]++; 
				
				$(_this).html(_mobile_more_o_inners[mobile_more_o_inner.attr('id')]);
			}
			else
			{
				if (_mobile_more_pages[mobile_more_o_inner.attr('id')] == start_page)
				{
					target_el.html('<p style="padding: 15px 0" align="center">没有内容</p>');
				}
							
				$(_this).addClass('disabled').unbind('click').bind('click', function () { return false; });
						
				$(_this).html('没有更多了');
			}
				
			$(_this).removeClass('loading');
			
			if (callback_func != null)
			{
				callback_func();
			}
			
			hide_loading();
		});
			
		return false;
	});
	
	mobile_more_o_inner.click();
}

function _ajax_post_mobile_processer(result)
{
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
	
	hide_loading();
}

function mobile_header_message(message)
{
	$('#header_message').append('<div class="jump">' + message + '</div>');
}

function mobile_comments(item_id, type_name, el)
{
	if ($('#' + type_name + '_comments_' + item_id).css('display') == 'none')
	{
		if($('#' + type_name + '_comments_' + item_id + ' div[name=comments_list]').html() == '')
		{
			reload_mobile_comments_list(item_id, type_name, el);
		}

		$('#' + type_name + '_comments_' + item_id).find(".triangle-up").css({left:el.offset().left-36+"px"});

		$('#' + type_name + '_comments_' + item_id).fadeIn();
	}
	else
	{
		$('#' + type_name + '_comments_' + item_id).fadeOut();
	}
	
}

function reload_mobile_comments_list(item_id, type_name, el)
{
	$('#' + type_name + '_comments_' + item_id + ' div[name=comments_list]').html('<p style="padding:0" align="center"><img src="' + G_STATIC_URL + '/common/load.gif" alt="" /></p>');
	
	$.get(G_BASE_URL + '/mobile/ajax/get_' + type_name + '_comments/' + type_name + '_id-' + item_id, function (data) {

		$('#' + type_name + '_comments_' + item_id + ' div[name=comments_list]').html(data)

		if(el != null)
		{
			$('#' + type_name + '_comments_' + item_id + ' div[name=comments_list]').find(".triangle-up").css({left:el.offset().left-36+"px"});
		}
	});
}

function _comments_form_processer(result)
{
	if (typeof(result.errno) == 'undefined')
	{
		alert(result);
	}
	else if(result.errno == 1)
	{
		reload_mobile_comments_list(result.rsm.item_id, result.rsm.type_name);
	}
	else if(result.errno == -1)
	{
		$.alert(result.err);
	}

	hide_loading();
}

function mobile_read_notification(notification_id, el)
{
	if(notification_id == 'all')
	{
		$("#bp_contents li").removeClass('unread');
		read_type = 0;
	}
	else
	{
		el.removeClass('unread');
		read_type = 1;
	}

	$.get(G_BASE_URL + '/notifications/ajax/read_notification/notification_id-' + notification_id + '__read_type-' + read_type);
}

$.alert = function (message) {
	alert(message);
}