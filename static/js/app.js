var document_title;

$(window).on('hashchange', function() {
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
});

$(document).ready(function ()
{
    document_title = document.title;

    // fix form bug...
    $("form[action='']").attr('action', window.location.href);

    $('img#captcha').attr('src', G_BASE_URL + '/account/captcha/');
    
    $('.autosize').autosize();

    //编辑器初始化
    if (typeof (myMarkdownSettings) != 'undefined' && $('.advanced_editor'))
    {
        $('.advanced_editor').markItUp(myMarkdownSettings);

        $.setEditorPreview();
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

    if (typeof (G_NOTIFICATION_INTERVAL) != 'undefined')
    {
        check_notifications();

        setInterval('check_notifications()', G_NOTIFICATION_INTERVAL);
    }

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
	
    //文章页添加评论, 话题添加 绑定事件
    init_article_comment_box('.aw-article-detail .aw-article-comment');
    init_topic_edit_box('.aw-edit-topic');
	
    /*用户头像提示box*/
    show_card_box('.aw-user-name, .aw-user-img', 'user');
    show_card_box('.aw-topic-name, .aw-topic-img', 'topic');
    
    //小卡片mouseover
    $(document).on('mouseover', '#aw-card-tips', function ()
    {
        clearTimeout(cardBoxTimeout);
        
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
       
       $.each(cashUserData, function (i, a)
       {
           //存在缓存
           if (a.match('data-id="' + uid + '"'))
           {
               if (cashUserData.length == 1)
               {
                   cashUserData = [];
               }
               else
               {
                   cashUserData[i] = '';
               }
           }
       });

    });

    //话题小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-topic .focus', function ()
    {
        var topic_id = $(this).parents('.aw-card-tips').find('.name').attr('data-id');
        
        $.each(cashTopicData, function (i, a)
        {
            //存在缓存
            if (a.match('data-id="' + topic_id + '"'))
            {
                if (cashTopicData.length == 1)
                {
                    cashTopicData = [];
                }
                else
                {
                    cashTopicData[i] = '';
                }
            }
        });
    });
    
    /*icon tooltips提示*/
    $(document).on('mouseover', '.voter, .fa-check, .icon-ok-sign , .fa-thumbs-o-up , .fa-thumbs-o-down  , .aw-icon-thank-tips, .invite-list-user', function ()
    {
        $(this).tooltip('show');
    });
	
    //话题编辑下拉菜单mouseover click事件动态绑定
    $(document).on('mouseover', '.aw-edit-topic-box .aw-dropdown-list li', function ()
    {
        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
    });
    
    $(document).on('click', '.aw-edit-topic-box .aw-dropdown-list li', function ()
    {
        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
        $(this).parents('.aw-edit-topic-box').find('.submit-edit').click();
        $(this).parents('.aw-edit-topic-box').find('.aw-dropdown').hide();
    });

    //分享私信用户下拉点击事件动态绑定
    $(document).on('click','.aw-share-box .aw-dropdown-list li a, .aw-inbox .aw-dropdown-list li a',function() {
    	$('.alert-box #quick_publish input.form-control').val($(this).text());
    	$(this).parents('.aw-dropdown').hide();
    });

    //搜索下拉
    bind_dropdown_list('#aw-search-query', 'search');

    //话题删除按钮
    $(document).on('click', '.aw-topic-name .aw-close',  function()
    {
        var data_type = $(this).parents('.aw-topic-editor').attr('data-type'),
            data_id = $(this).parents('.aw-topic-editor').attr('data-id');
        switch (data_type)
        {
            case 'question':
                $.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', 'type=question&item_id=' + data_id + '&topic_id=' + $(this).parents('.aw-topic-name').attr('data-id'),function(){
                    $('#aw-ajax-box').empty();
                });
                break;

            case 'topic':
                $.get(G_BASE_URL + '/topic/ajax/remove_related_topic/related_id-' + $(this).parents('.aw-topic-name').attr('data-id') + '__topic_id-' + data_id);
                break;

            case 'favorite':
                $.post(G_BASE_URL + '/favorite/ajax/remove_favorite_tag/', 'answer_id=' + data_id + '&tags=' + $(this).parents('.aw-topic-name').text().substring(0, $(this).parents('.aw-topic-name').text().length - 1));
                break;

            case 'article':
                $.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', 'type=article&item_id=' + data_id + '&topic_id=' + $(this).parents('.aw-topic-name').attr('data-id'),function(){
                    $('#aw-ajax-box').empty();
                });
                break;

        }

        $(this).parents('.aw-topic-name').remove();

        return false;
    });
	
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