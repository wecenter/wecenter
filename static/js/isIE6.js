(function(){
	if ($.browser.msie && $.browser.version == "6.0" && !$.support.style){
		// IE 6....
		
		$('<div/>').css({
			width:380,
			height:150,
			background:'#fff url(' + G_BASE_URL + '/static/css/default/img/forIE6_tips.png) center 10px no-repeat',
			margin:'0 auto 0 -300px',
			left:'50%',
			top:'130px',
			position:'absolute',
			border:'5px solid #ddd',
			padding:'150px 0 10px 220px',
			zIndex:9999
			
		}).html('<p style="color:#888;padding-right:30px;padding-bottom:10px;">' + _t('您的浏览器版本非常旧, 存在诸多安全和体验问题! 建议<a href="http://windows.microsoft.com/zh-CN/internet-explorer/downloads/ie-9/worldwide-languages">更新</a>或者使用其他浏览器来访问, 如果您使用的是搜狗、360、遨游等双核浏览器, 请切换到极速模式以获得更好的体验') + '</p><p>' + _t('<a href="http://j.union.ijinshan.com/jump.php?u_key=136711">￮ 金山猎豹浏览器</a>') + '</p><p>' + _t('<a href="http://www.google.cn/intl/zh-CN/chrome/browser">￮ 谷歌浏览器</a>') + '</p><p>' + _t('<a href="http://firefox.com.cn">￮ Firefox 浏览器</a>') + '</p>')
		.appendTo($(document.body).html(''));
	}
})();