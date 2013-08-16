var AW_MOBILE_TEMPLATE = {
	'publish' : 
		'<div class="modal hide fade alert-publish" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		    '<form>'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">发起问题</h3>'+
			    '</div>'+
			    '<div class="modal-body">'+
			    	'<textarea placeholder="写下你的问题..." rows="2"></textarea>'+
			    	'<textarea placeholder="问题背景、条件等详细信息..." rows="4"></textarea>'+
			    	'<div class="aw-topic-box">'+
			    		'<a class="aw-topic-name">bug</a>'+
			    	'</div>'+
			    	'<div class="aw-topic-box-selector">'+
			    		'<input type="text" placeholder="创建或搜索添加新话题..." class="pull-left"><a class="btn btn-primary pull-left">添加</a>'+
			    	'</div>'+
			   	'</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
			    	'<button class="btn btn-primary">发起</button>'+
			    '</div>'+
		    '</form>'+
	    '</div>',

	'redirect' : 
		'<div class="modal hide fade alert-redirect" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		    '<form>'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">' + _t('问题重定向至') + '</h3>'+
			    '</div>'+
			    '<div class="modal-body">'+
			    	'<p>' + _t('将问题跳转至') + '</p>'+
			    	'<input type="text" data-id="{{data-id}}">'+
			    '</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true" class="btn btn-primary">' + _t('取消') + '</a>'+
			    '</div>'+
		    '</form>'+
	    '</div>',

	// 'report' : 
	// 	'<div class="modal hide fade alert-report" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
	// 	    '<form>'+
	// 	    	'<div class="modal-header">'+
	// 		    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
	// 		    	'<h3 id="myModalLabel">举报问题</h3>'+
	// 		    '</div>'+
	// 		    '<div class="modal-body">'+
	// 		    	'<textarea placeholder="请填写举报理由..." rows="3"></textarea>'+
	// 		    '</div>'+
	// 		    '<div class="modal-footer">'+
	// 		    	'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
	// 		    	'<button class="btn btn-primary">发起</button>'+
	// 		    '</div>'+
	// 	    '</form>'+
	//     '</div>',

	'message' : 
		'<div class="modal hide fade alert-message" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		   '<form action="" method="post" id="quick_publish" onsubmit="return false">'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">' + _t('发送私信') + '</h3>'+
			    '</div>'+
			    '<div class="modal-body">'+
			    	'<input type="text" placeholder="' + _t('搜索用户...') + '" value="{{data-name}}">'+
			    	'<textarea placeholder="' + _t('私信内容...') + '" rows="4"></textarea>'+
			    '</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
			    	'<button class="btn btn-primary">' + _t('发送') + '</button>'+
			    '</div>'+
		    '</form>'+
	    '</div>'
}