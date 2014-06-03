var document_title = document.title;

$(document).ready(function () {

	if (typeof (G_NOTIFICATION_INTERVAL) != 'undefined')
    {
        AWS.Message.check_notifications();

        //setInterval('check_notifications()', G_NOTIFICATION_INTERVAL);
    }

	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
	
	$('a[rel=lightbox]').fancybox(
    {
        openEffect: 'none',
        closeEffect: 'none',

        prevEffect: 'none',
        nextEffect: 'none',

        closeBtn: false,

        helpers:
        {
            buttons:
            {
                position: 'bottom'
            }
        },

        afterLoad: function ()
        {
            this.title = '第 ' + (this.index + 1) + ' 张, 共 ' + this.group.length + ' 张' + (this.title ? ' - ' + this.title : '');
        }
    });

	$('.aw-mod-publish .aw-publish-title textarea').autosize();
	
	AWS.Init.init_comment_box('.aw-add-comment');
	AWS.Init.init_article_comment_box('.aw-article-comment');

	$('.autosize').autosize();
	
	$('img#captcha').attr('src', G_BASE_URL + '/account/captcha/');
	
	$('#aw-top-nav-profile').click(function(){
		$('.aw-top-nav-popup').hide();
		$('.aw-top-nav-profile').show();
	});

	$('#aw-top-nav-notic').click(function()
	{
		$('.aw-top-nav-popup').hide();
		if (G_USER_ID)
		{
			$.get(G_BASE_URL + '/notifications/ajax/list/flag-0__limit-10', function (data) {
				if (data)
				{
					$('#notifications_list').html(data);
				}
				else
				{
					$('#notifications_list').html('<li><p align="center">暂无新通知</p></li>');
				}
				$('.aw-top-nav-notic').show();
			});
		}
	});

	/* 点击下拉菜单外得地方隐藏　*/
	$(document).click(function(e)
	{
		var target = $(e.target);
		if (target.closest('#aw-top-nav-profile, #aw-top-nav-notic').length == 0)
		{
			$('.aw-top-nav-popup, .dropdown-list').hide();
		}
	});

	/* 话题编辑删除按钮 */
	$(document).on('click', '.aw-question-detail-title .aw-topic-edit-box .aw-topic-box i', function()
	{
		var _this = $(this);
		$.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', {'type':$(this).parents('.aw-topic-edit-box').attr('data-type'), 'item_id' : $(this).parents('.aw-topic-edit-box').attr('data-id'), 'topic_id' : $(this).parents('.aw-topic-name').attr('data-id')} , function(result)
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

	AWS.Dropdown.bind_dropdown_list('.aw-search-input','search');
	AWS.Dropdown.bind_dropdown_list('.aw-invite-input','invite');
	AWS.Init.init_topic_edit_box('.aw-question-detail-title .aw-add-topic-box', 'question');
	AWS.Init.init_topic_edit_box('.aw-mod-publish .aw-add-topic-box','publish');

	//邀请回答按钮
	$('.aw-invite-replay').click(function()
	{
		if ($(this).parents('.aw-question-detail-title').find('.aw-invite-box').is(':visible'))
		{
			$(this).parents('.aw-question-detail-title').find('.aw-invite-box').hide();
		}else
		{
			$(this).parents('.aw-question-detail-title').find('.aw-invite-box').show();
		}
	});
	//邀请初始化
    $('.aw-question-detail-title .aw-invite-box ul li').hide();
    for (var i = 0; i < 3; i++)
    {
    	$('.aw-question-detail-title .aw-invite-box ul li').eq(i).show();
    }
    //长度小于3翻页隐藏
    if ($('.aw-question-detail-title .aw-invite-box ul li').length <=3 )
    {
    	$('.aw-question-detail-title .aw-invite-box .aw-mod-footer').hide();
    }
	//邀请上一页
    $('.aw-question-detail-title .aw-invite-box .prev').click(function()
    {
    	if (!$(this).hasClass('active'))
    	{
    		var attr = [],li_length = $('.aw-question-detail-title .aw-invite-box ul li').length;
	    	$.each($('.aw-question-detail-title .aw-invite-box ul li'), function (i, e)
	    	{
	    		if ($(this).is(':visible') == true)
	    		{
	    			attr.push($(this).index());
	    		}
	    	});
	    	$('.aw-question-detail-title .aw-invite-box ul li').hide();
	    	$.each(attr, function (i, e)
	    	{
				if (attr.join('') == '123' || attr.join('') == '234')
				{
					$('.aw-question-detail-title .aw-invite-box ul li').eq(0).show();
					$('.aw-question-detail-title .aw-invite-box ul li').eq(1).show();
					$('.aw-question-detail-title .aw-invite-box ul li').eq(2).show();
				}
				else
				{
	    			$('.aw-question-detail-title .aw-invite-box ul li').eq(e-3).show();
				}
	    		
	    		if (e-3 == 0)
	    		{
	    			$('.aw-question-detail-title .aw-invite-box .prev').addClass('active');
	    		}
	    	});
	    	$('.aw-question-detail-title .aw-invite-box .next').removeClass('active');
    	}
    });

    //邀请下一页
    $('.aw-question-detail-title .aw-invite-box .next').click(function()
    {
    	if (!$(this).hasClass('active'))
    	{
			var attr = [], li_length = $('.aw-question-detail-title .aw-invite-box ul li').length;
	    	$.each($('.aw-question-detail-title .aw-invite-box ul li'), function (i, e)
	    	{
	    		if ($(this).is(':visible') == true)
	    		{
	    			attr.push($(this).index());
	    		}
	    	});
	    	$.each(attr, function (i, e)
	    	{
	    		if (e+3 < li_length)
	    		{
	    			$('.aw-question-detail-title .aw-invite-box ul li').eq(e).hide();
	    			$('.aw-question-detail-title .aw-invite-box ul li').eq(e+3).show();
	    		}
	    		if (e+4 == $('.aw-question-detail-title .aw-invite-box ul li').length)
	    		{
	    			$('.aw-question-detail-title .aw-invite-box .next').addClass('active');
	    		}
	    	});
	    	$('.aw-question-detail-title .aw-invite-box .prev').removeClass('active');
    	}
    });



});