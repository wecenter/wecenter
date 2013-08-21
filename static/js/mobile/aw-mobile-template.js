var AW_MOBILE_TEMPLATE = {
	'publish' : 
		'<div class="modal hide fade alert-publish" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
		    '<form action="' + G_BASE_URL + '/publish/ajax/publish_question/" method="post" id="quick_publish" onsubmit="return false">'+
		    	'<div class="modal-header">'+
			    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
			    	'<h3 id="myModalLabel">' + _t('发起问题') + '</h3>'+
			    '</div>'+
			    '<div class="modal-body clearfix">'+
			    	'<div id="quick_publish_error" class="error-message alert  alert-error hide"><em></em></div>'+
			    	
					'<input type="hidden" id="quick_publish_category_id" name="category_id" value="{{category_id}}" />'+
					'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
					'<input type="hidden" name="ask_user_id" value="{{ask_user_id}}" />'+
			    	'<textarea name="question_content" placeholder="' + _t('写下你的问题') + '..." id="quick_publish_question_content" onkeydown="if (event.keyCode == 13) { return false; }" rows="2"></textarea>'+
			    	'<textarea name="question_detail" placeholder="' + _t('问题背景、条件等详细信息') + '..." rows="4"></textarea>'+
			    	'<div class="aw-topic-edit-box" id="quick_publish_topic_chooser">'+
			    		'<div class="aw-topic-box"><a class="aw-add-topic-box">' + _t('编辑话题') + '</a></div>'+
				    '</div>'+
				    
				    '<div class="aw-publish-title clearfix" id="quick_publish_category_chooser">'+
					    '<div class="aw-publish-dropdown">'+
					    	'<p data-toggle="dropdown" class="dropdown-toggle">'+
					    		'<span class="pull-left num">' + _t('选择分类') + '</span>'+
					    		'<i class="pull-left"></i>'+
					    	'</p>'+
						    '<ul class="dropdown-menu">'+
						    '</ul>'+
					    '</div>'+
			    	'</div>'+
			    	
				    '<div class="aw-verify hide" id="quick_publish_captcha">'+
						'<input id="seccode_verify" name="seccode_verify" placeholder="验证码" type="text" />'+
						'<img id="captcha" class="verify_code" onclick="this.src = \'' +G_BASE_URL + '/account/captcha/\' + Math.floor(Math.random() * 10000);" src="'+ G_BASE_URL +'/account/captcha/" />'+
				    '</div>'+
				'</div>'+
			    '<div class="modal-footer">'+
			    	'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
			    	'<button class="btn btn-primary btn-primary" onclick="ajax_post($(\'#quick_publish\'), _quick_publish_processer); return false;">' + _t('发起') + '</button>'+
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
			'</div>',

	'topic_edit_box' :
		'<div class="aw-topic-box-selector">'+
			'<input type="text" placeholder="' + _t('创建或搜索添加新话题...') + '" class="aw-topic-input">'+
			'<div class="dropdown-list"><ul></ul></div>'+
			'<a class="btn add">' + _t('添加') + '</a><a class="btn cancel">' + _t('取消') + '</a>'+
		'</div> ',

	'dropdownList' : 
		'<ul class="dropdown-menu">'+
			'{{#items}}'+
				'<li><a data-value="{{id}}">{{title}}</a></li>'+
			'{{/items}}'+
		'</ul>'
}