var AW_MOBILE_TEMPLATE = {
	'publish' : 
		'<div class="modal fade alert-publish">'+
		    '<div class="modal-dialog">'+
				'<div class="modal-content">'+
				    '<form action="' + G_BASE_URL + '/publish/ajax/publish_question/" method="post" id="quick_publish" onsubmit="return false">'+
				    	'<div class="modal-header">'+
					    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					    	'<h3 class="modal-title" id="myModalLabel">' + _t('发起问题') + '</h3>'+
					    '</div>'+
					    '<div class="modal-body clearfix">'+
					    	'<div id="quick_publish_error" class="alert alert-danger alert-error hide error_message"><em></em></div>'+
					    	
							'<input type="hidden" id="quick_publish_category_id" name="category_id" value="{{category_id}}" />'+
							'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
							'<input type="hidden" name="ask_user_id" value="{{ask_user_id}}" />'+
					    	'<textarea class="form-control" name="question_content" placeholder="' + _t('写下你的问题') + '..." id="quick_publish_question_content" onkeydown="if (event.keyCode == 13) { return false; }" rows="2"></textarea>'+
					    	'<textarea class="form-control" name="question_detail" placeholder="' + _t('问题背景、条件等详细信息') + '..." rows="4"></textarea>'+
					    	'<div class="aw-topic-edit-box" id="quick_publish_topic_chooser">'+
					    		'<div class="aw-topic-box"><a class="aw-add-topic-box">' + _t('编辑话题') + '</a></div>'+
						    '</div>'+
						    
						    '<div class="aw-publish-title clearfix" id="quick_publish_category_chooser">'+
							    '<div class="aw-publish-dropdown">'+
							    	'<p data-toggle="dropdown" class="dropdown-toggle">'+
							    		'<span class="pull-left aw-text-color-999">' + _t('选择分类') + '</span>'+
							    		'<i class="pull-left"></i>'+
							    	'</p>'+
							    '</div>'+
					    	'</div>'+
					    	
						    '<div class="aw-verify hide" id="quick_publish_captcha">'+
								'<input id="seccode_verify" name="seccode_verify" placeholder="' + _t('验证码') + '" type="text" onfocus="$(\'#qp_captcha\').click();" />'+
								'<img id="qp_captcha" class="verify_code" onclick="this.src = \'' +G_BASE_URL + '/account/captcha/\' + Math.floor(Math.random() * 10000);" src="'+ G_BASE_URL +'/account/captcha/" />'+
						    '</div>'+
						'</div>'+
					    '<div class="modal-footer">'+
					    	'<a class="pull-left" href="'+ G_BASE_URL +'/m/publish/">高级模式</a>'+
					    	'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
					    	'<button class="btn btn-primary btn-primary" onclick="ajax_post($(\'#quick_publish\'), _quick_publish_processer); return false;">' + _t('发起') + '</button>'+
					    '</div>'+
				    '</form>'+
				'</div>'+
			'</div>'+
	    '</div>',

	'redirect' : 
		'<div class="modal fade alert-redirect">'+
		    '<div class="modal-dialog">'+
				'<div class="modal-content">'+
				    '<form>'+
				    	'<div class="modal-header">'+
					    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					    	'<h3 class="modal-title" id="myModalLabel">' + _t('问题重定向至') + '</h3>'+
					    '</div>'+
					    '<div class="modal-body">'+
					    	'<p>' + _t('将问题跳转至') + '</p>'+
					    	'<input type="text" class="aw-redirect-input form-control" data-id="{{data-id}}">'+
					    	'<div class="dropdown-list"><ul></ul></div>'+
					    '</div>'+
					    '<div class="modal-footer">'+
					    	'<a data-dismiss="modal" aria-hidden="true" class="btn btn-primary">' + _t('取消') + '</a>'+
					    '</div>'+
				    '</form>'+
				'</div>'+
			'</div>'+
	    '</div>',

	'message' : 
		'<div class="modal fade alert-message">'+
		   '<div class="modal-dialog">'+
				'<div class="modal-content">'+
				   '<form id="publish" action="' + G_BASE_URL + '/inbox/ajax/send/" method="post" id="quick_publish" onsubmit="return false">'+
				   		'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
				    	'<div class="modal-header">'+
					    	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					    	'<h3 class="modal-title" id="myModalLabel">' + _t('发送私信') + '</h3>'+
					    '</div>'+
					    '<div class="modal-body">'+
					    	'<input type="text" name="recipient" class="aw-message-input form-control" placeholder="' + _t('搜索用户...') + '" value="{{data-name}}">'+
					    	'<div class="dropdown-list"><ul></ul></div>'+
					    	'<textarea class="form-control" name="message" placeholder="' + _t('私信内容...') + '" rows="4"></textarea>'+
					    	'<input type="hidden" name="return_url" value="/m/inbox/" />'+
					    '</div>'+
					    '<div class="modal-footer">'+
					    	'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
					    	'<button class="btn btn-primary" onclick="ajax_post($(\'#publish\'))">' + _t('发送') + '</button>'+
					    '</div>'+
				    '</form>'+
				'</div>'+
			'</div>'+
	    '</div>',
	    
	'commentBox' : 
		'<div class="aw-comment-box" id="{{comment_form_id}}">'+
			'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
			'<form action="{{comment_form_action}}" method="post" onsubmit="return false">'+
				'<div class="aw-comment-box-main clearfix">'+
					'<textarea class="aw-comment-txt form-control" name="message" placeholder="' + _t('评论一下') + '..."></textarea>'+
					'<a href="javascript:;" class="btn btn-mini btn-default close-comment-box pull-right">' + _t('取消') + '</a>'+
					'<a href="javascript:;" class="btn btn-mini btn-primary pull-right" onclick="save_comment(this);">' + _t('评论') + '</a>'+
				'</div>'+
			'</form>'+
		'</div>',

	'commentBoxClose' : 
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'<i class="i-dropdown-triangle"></i>'+
			'</div>',

	'articleCommentBox' :
		'<div class="aw-comment-box" id="{{comment_form_id}}">'+
			'<form action="'+ G_BASE_URL +'/article/ajax/save_comment/" method="post" onsubmit="return false">'+
				'<div class="aw-comment-box-main clearfix">'+
					'<input type="hidden" name="at_uid" value="{{at_uid}}">'+
					'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
					'<input type="hidden" name="article_id" value="{{article_id}}" />'+
					'<textarea class="aw-comment-txt form-control" name="message" placeholder="' + _t('评论一下') + '..."></textarea>'+
					'<a href="javascript:;" class="btn btn-mini btn-default close-comment-box pull-right">' + _t('取消') + '</a>'+
					'<a href="javascript:;" class="btn btn-mini btn-primary pull-right" onclick="ajax_post($(this).parents(\'form\'));">' + _t('评论') + '</a>'+
				'</div>'+
			'</form>'+
		'</div>',

	'topic_edit_box' :
		'<div class="aw-topic-box-selector">'+
			'<input type="text" placeholder="' + _t('创建或搜索添加新话题...') + '" class="aw-topic-input">'+
			'<div class="dropdown-list"><ul></ul></div>'+
			'<a class="btn add btn-success">' + _t('添加') + '</a><a class="btn cancel btn-default">' + _t('取消') + '</a>'+
		'</div> ',

	'dropdownList' : 
		'<ul class="dropdown-menu">'+
			'{{#items}}'+
				'<li><a data-value="{{id}}">{{title}}</a></li>'+
			'{{/items}}'+
		'</ul>',
	'editCommentBox' :
		'<div class="modal fade alert-commentEdit">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<div class="modal-header">'+
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
						'<h3 class="modal-title" id="myModalLabel">' + _t('编辑回复') + '</h3>'+
					'</div>'+
					'<form action="' + G_BASE_URL + '/question/ajax/update_answer/answer_id-{{answer_id}}" method="post" onsubmit="return false" id="answer_edit">'+
					'<div class="modal-body">'+
						'<input type="hidden" name="attach_access_key" value="{{attach_access_key}}" />'+
						'<textarea name="answer_content" id="editor_reply" class="form-control textarea_content" rows="5"></textarea>'+
						'<div class="aw-file-upload-box">'+
							'<span id="file_uploader_answer_edit"></span>'+
						'</div>'+
					'</div>'+
					'<div class="modal-footer">'+
						'<span><input id="aw-do-delete" type="checkbox" value="1" name="do_delete" /><label for="aw-do-delete">&nbsp;' + _t('删除回复') + '</label></span>'+
						'<button class="btn btn-large btn-success" onclick="ajax_post($(\'#answer_edit\'), _ajax_post_alert_processer);return false;">' + _t('确定') + '</button>'+
					'</div>'+
					'</form>'+
				'</div>'+
			'</div>'+
		'</div>'
}