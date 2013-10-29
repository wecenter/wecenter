
$(document).ready(function () {
	$.each($('.aw-article-list .aw-item .aw-mod-body .content'), function (i, e)
	{
		if ($(this).innerHeight() > parseInt($(this).parents('.content-wrap').css('max-height')))
		{
			$(this).parents('.aw-content').find('.more').show();
		}
	});
});

