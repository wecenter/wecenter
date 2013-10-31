$(document).ready(function () {
	$('.aw-article-list .aw-item .aw-mod-body .content-wrap').css('height',parseInt($('.aw-article-list .aw-item .aw-mod-body .content').css('line-height'))*10);
	$.each($('.aw-article-list .aw-item .aw-mod-body .content'), function (i, e)
	{
		// console.log($(this).css('line-height'));
		if ($(this).innerHeight() > parseInt($(this).parents('.content-wrap').css('max-height')))
		{
			$(this).parents('.aw-content').find('.more').show();
		}
	});
});

