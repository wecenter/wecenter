var document_title = document.title;

$(document).ready(function () {

	// 滚动指定位置
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}

	// 验证码自动点击
	$('#captcha').click();

	// 导航条小箭头位置修复
	$('.nav .triangle').css('left', $('.nav li').innerWidth()/2 - 8);

	// 导航条按钮
	$('.nav ul li .user').click(function()
	{
		$(this).parents('.nav').find('.aw-popover.more').hide();
		$('.nav ul li .more .triangle').hide();
		$('.nav ul li .more').removeClass('active');

		if ($(this).parents('li').find('.triangle').css('display') == 'none')
		{
			$(this).parents('li').find('.triangle').show();
			$(this).parents('.nav').find('.aw-popover.user').show();
			$(this).addClass('active');
		}
		else
		{
			$(this).parents('li').find('.triangle').hide();
			$(this).parents('.nav').find('.aw-popover.user').hide();
			$(this).removeClass('active');
		}
	});

	$('.nav ul li .more').click(function()
	{
		$(this).parents('.nav').find('.aw-popover.user').hide();
		$('.nav ul li .user .triangle').hide();
		$('.nav ul li .user').removeClass('active');

		if ($(this).parents('li').find('.triangle').css('display') == 'none')
		{
			$(this).parents('li').find('.triangle').show();
			$(this).parents('.nav').find('.aw-popover.more').show();
			$(this).addClass('active');
		}
		else
		{
			$(this).parents('li').find('.triangle').hide();
			$(this).parents('.nav').find('.aw-popover.more').hide();
			$(this).removeClass('active');
		}
	});
	
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

	// textarea自动增加高度
	$('.autosize').autosize();
	
	// 问题评论box
	AWS.Init.init_comment_box('.aw-add-comment');

	// 文章评论box
	AWS.Init.init_article_comment_box('.aw-article-comment');

	// 话题编辑box
	AWS.Init.init_topic_edit_box('.aw-topic-bar .icon-inverse');

	// 搜索下拉菜单
	AWS.Dropdown.bind_dropdown_list('.aw-search-bar input','search');

	// 邀请下拉菜单
	AWS.Dropdown.bind_dropdown_list('.aw-invite-box input','invite');

	// 话题编辑删除按钮
	$(document).on('click', '.aw-topic-bar .tag-bar .topic-tag i', function()
	{
		var _this = $(this);
		$.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', {'type':$(this).parents('.aw-topic-bar').attr('data-type'), 'item_id' : $(this).parents('.aw-topic-bar').attr('data-id'), 'topic_id' : $(this).parents('.topic-tag').attr('data-id')} , function (result)
		{
			if (result.errno == 1) 
			{
				_this.parents('.topic-tag').detach();
			}else
			{
				alert(result.err);
			}
		}, 'json');
		return false;
	});

	//邀请回答按钮
	$('.aw-invite-replay').click(function()
	{
		if ($(this).parents('.aw-question-detail').find('.aw-invite-box').is(':visible'))
		{
			$(this).parents('.aw-question-detail').find('.aw-invite-box').hide();
			$(this).removeClass('active');
		}else
		{
			$(this).parents('.aw-question-detail').find('.aw-invite-box').show();
			$(this).addClass('active');
		}
	});
	
});