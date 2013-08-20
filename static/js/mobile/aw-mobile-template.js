var AW_MOBILE_TEMPLATE = {
	'publish' : 
		'<div class="modal hide fade alert-publish" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		    '<form>'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">发起问题</h3>'+
			    '</div>'+
			    '<div class="modal-body clearfix">'+
			    	'<textarea name="question_content" placeholder="写下你的问题..." rows="2"></textarea>'+
			    	'<textarea name="question_detail" placeholder="问题背景、条件等详细信息..." rows="4"></textarea>'+
			    	'<div class="aw-topic-edit-box">'+
			    		'<div class="aw-topic-box"><a class="aw-add-topic-box">编辑话题</a></div>'+
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
			    	'<input type="text" class="aw-redirect-input" data-id="{{data-id}}">'+
			    	'<div class="dropdown-list"><ul></ul></div>'+
			    '</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true" class="btn btn-primary">' + _t('取消') + '</a>'+
			    '</div>'+
		    '</form>'+
	    '</div>',

	'message' : 
		'<div class="modal hide fade alert-message" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		   '<form id="publish" action="' + G_BASE_URL + '/inbox/ajax/send/" method="post" id="quick_publish" onsubmit="return false">'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">' + _t('发送私信') + '</h3>'+
			    '</div>'+
			    '<div class="modal-body">'+
			    	'<input type="text" name="recipient" class="aw-message-input" placeholder="' + _t('搜索用户...') + '" value="{{data-name}}">'+
			    	'<div class="dropdown-list"><ul></ul></div>'+
			    	'<textarea name="message" placeholder="' + _t('私信内容...') + '" rows="4"></textarea>'+
			    	'<input type="hidden" name="return_url" value="/m/inbox/" />'+
			    '</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
			    	'<button class="btn btn-primary" onclick="ajax_post($(\'#publish\'))">' + _t('发送') + '</button>'+
			    '</div>'+
		    '</form>'+
	    '</div>',
	    
	'commentBox' : 
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'<form action="{{comment_form_action}}" method="post" onsubmit="return false">'+
					'<div class="aw-comment-box-main clearfix">'+
						'<textarea class="aw-comment-txt" name="message" placeholder="' + _t('评论一下') + '..."></textarea>'+
						'<a href="javascript:;" class="btn btn-mini close-comment-box pull-right">' + _t('取消') + '</a>'+
						'<a href="javascript:;" class="btn btn-mini btn-primary pull-right" onclick="save_comment(this);">' + _t('评论') + '</a>'+
					'</div>'+
				'</form>'+
				'<i class="aw-icon i-comment-triangle"></i>'+
			'</div>',

	'topic_edit_box' :
		'<div class="aw-topic-box-selector">'+
			'<input type="text" placeholder="' + _t('创建或搜索添加新话题...') + '" class="aw-topic-input">'+
			'<div class="dropdown-list"><ul></ul></div>'+
			'<a class="btn add">' + _t('添加') + '</a><a class="btn cancel">' + _t('取消') + '</a>'+
		'</div> '
}