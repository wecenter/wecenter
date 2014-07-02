var document_title = document.title;

$(document).ready(function ()
{
    // fix form bug...
    $("form[action='']").attr('action', window.location.href);

    // 验证码
    $('img#captcha').attr('src', G_BASE_URL + '/account/captcha/');
    
    // 输入框自动增高
    $('.autosize').autosize();

    //编辑器初始化
    if (typeof (myMarkdownSettings) != 'undefined' && $('.advanced_editor'))
    {
        $('.advanced_editor').markItUp(myMarkdownSettings);

        AWS.Editor.set_editor_preview();
    }
    else if ($('.markItUpPreviewFrame'))
    {
        $('.markItUpPreviewFrame').hide();
    }

    //响应式导航条效果
    $('.aw-top-nav .navbar-toggle').click(function()
    {
        if ($(this).parents('.aw-top-nav').find('.navbar-collapse').hasClass('active'))
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').removeClass('active');
        }
        else
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').addClass('active');
        }
    });

    //检测通知
    if (typeof (G_NOTIFICATION_INTERVAL) != 'undefined')
    {
        AWS.Message.check_notifications();
        AWS.G.notification_timer = setInterval('AWS.Message.check_notifications()', G_NOTIFICATION_INTERVAL);
    }

    //文章列表样式调整
    $.each($('.aw-common-list .aw-item.article'), function (i, e)
    {
        if ($(this).find('img').length > 1)
        {
            $(this).find('.img.pull-right').show();
            $(this).find('.aw-comment-upload-img-list').next().detach();
            $(this).find('img').next().detach().end().prev().detach();
            $(this).find('.markitup-box img').eq(0).css({'z-index':'999'});
        }
    });

    $('a[rel=lightbox]').fancybox(
    {
        openEffect: 'none',
        closeEffect: 'none',
        prevEffect: 'none',
        nextEffect: 'none',
        centerOnScroll : true,
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
	
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
	
    /*用户头像提示box*/
    AWS.show_card_box('.aw-user-name, .aw-user-img', 'user');

    AWS.show_card_box('.aw-topic-name, .aw-topic-img', 'topic');
    
    //文章页添加评论, 话题添加 绑定事件
    AWS.Init.init_article_comment_box('.aw-article-content .aw-article-comment');

    AWS.Init.init_topic_edit_box('.aw-edit-topic');
	
    //小卡片mouseover
    $(document).on('mouseover', '#aw-card-tips', function ()
    {
        clearTimeout(AWS.G.card_box_hide_timer);
        
        $(this).show();
    });

    //小卡片mouseout
    $(document).on('mouseout', '#aw-card-tips', function ()
    {
        $(this).hide();
    });

    //用户小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-user .focus', function ()
    {
        var uid = $(this).parents('.aw-card-tips').find('.name').attr('data-id');
       
        $.each(AWS.G.cashUserData, function (i, a)
        {
            if (a.match('data-id="' + uid + '"'))
            {
                if (AWS.G.cashUserData.length == 1)
                {
                    AWS.G.cashUserData = [];
                }
                else
                {
                    AWS.G.cashUserData[i] = '';
                }
            }
        });
    });

    //话题小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-topic .focus', function ()
    {
        var topic_id = $(this).parents('.aw-card-tips').find('.name').attr('data-id');
        
        $.each(AWS.G.cashTopicData, function (i, a)
        {
            if (a.match('data-id="' + topic_id + '"'))
            {
                if (AWS.G.cashTopicData.length == 1)
                {
                    AWS.G.cashTopicData = [];
                }
                else
                {
                    AWS.G.cashTopicData[i] = '';
                }
            }
        });
    });
    
    /*icon tooltips提示*/
    $(document).on('mouseover', '.voter, .fa-check, .fa-thumbs-o-up , .fa-thumbs-o-down, .aw-icon-thank-tips, .invite-list-user', function ()
    {
        $(this).tooltip('show');
    });

    //搜索下拉
    AWS.Dropdown.bind_dropdown_list('#aw-search-query', 'search');
	
    //ie浏览器下input,textarea兼容
    if (document.all)
    {
        $('input,textarea').each(function ()
        {
            if (typeof ($(this).attr("placeholder")) != "undefined")
            {
                if ($(this).val() == '')
                {
	                $(this).addClass('aw-placeholder').val($(this).attr("placeholder"));
                }

                $(this).focus(function () {
                    if ($(this).val() == $(this).attr('placeholder'))
                    {
                        $(this).removeClass('aw-placeholder').val('');
                    }
                });

                $(this).blur(function () {
                    if ($(this).val() == '')
                    {
                        $(this).addClass('aw-placeholder').val($(this).attr('placeholder'));
                    }
                });
            }
        });
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

$(window).scroll(function ()
{
    if ($('.aw-back-top').length)
    {
        if ($(window).scrollTop() > ($(window).height() / 2))
        {
            $('.aw-back-top').fadeIn();
        }
        else
        {
            $('.aw-back-top').fadeOut();
        }
    }
});