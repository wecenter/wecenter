$(document).ready(function () {
	$.each($('.aw-article-list .aw-item .aw-mod-body .content'), function (i, e)
	{
		if ($(this).innerHeight() > parseInt($(this).css('line-height'))*10)
		{
			$(this).parents('.aw-content').find('.more').show();
			$(this).parents('.content-wrap').css('height',parseInt($(this).css('line-height'))*10);
		}
	});
});

