(function(){
	if ($.browser.msie && $.browser.version == "6.0" && !$.support.style){
		window.location = G_BASE_URL + '/home/browser_not_support/';
	}
})();