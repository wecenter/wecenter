$(function(){
	$('#aw-top-nav-profile').click(function(){
		$('.aw-top-nav-popup').hide();
		$('.aw-top-nav-profile').toggle();
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
		if (target.closest('#aw-top-nav-notic, #aw-top-nav-profile, .aw-top-nav-popup').length == 0)
		{
			$('.aw-top-nav-popup').hide();
		}
	});

	$('.aw-publish').click(function()
	{
		alert_box('publish');
	});
	$('.aw-question-redirect').click(function()
	{
		alert_box('redirect');
	});
	$('.aw-message').click(function()
	{
		alert_box('message');
	});
	$('.aw-report').click(function()
	{
		alert_box('report');
	});

	search_dropdown('.search-input');

});

/* 弹窗 */
function alert_box(type)
{
	var template;
	switch (type)
	{
		case 'publish' : 
			template = AW_MOBILE_TEMPLATE.publish;
		break;

		case 'redirect' : 
			template = AW_MOBILE_TEMPLATE.redirect;
		break;

		case 'report' :
			template = AW_MOBILE_TEMPLATE.report;
		break;

		case 'message' :
			template = AW_MOBILE_TEMPLATE.message;
		break;
	}
	$('#aw-ajax-box').html('').append(Hogan.compile(template).render({
		
	}));
	$('.alert-' + type).modal('show');
}

var auto_search;
/* 搜索下拉 */
function search_dropdown(element)
{
	$(element).keydown(function()
	{
		auto_search = setTimeout(function()
		{
			if ($(element).val().length >= 2)
			{
				$.get(G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent($(element).val()) + '&limit=' + 5,function(result)
				{
					if (result.length > 0)
					{
						$(element).next().find('ul').html('');
						// type1 : 问题 , type2 : 话题 best_answer最佳回答, type3 : 用户
						for (var i=0; i < result.length; i++)
						{
							switch(parseInt(result[i].type))
							{
								case 1 :
									$(element).next().find('ul').append('<li><a>' + result[i].name + '<span class="num">' + result[i].detail.answer_count + ' 个回答</span></a></li>');
									break;
								case 2 :
									$(element).next().find('ul').append('<li><a class="aw-topic-name" href="' + result[i].url + '">' + result[i].name  + '</a><span class="num">' + result[i].detail.discuss_count + ' 个问题</span></li>');
									break;

								case 3 :
									$(element).next().find('ul').append('<li><a><img src="' + result[i].detail.avatar_file + '"><span>' + result[i].name + '</span></a></li>');
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
		},500);
	});
	$(element).blur(function()
	{
		clearTimeout(auto_search);
	});
}