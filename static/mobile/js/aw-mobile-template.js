var AW_MOBILE_TEMPLATE = {
	'loadingBox':
		'<div id="aw-loading" class="hide">'+
			'<div id="aw-loading-box"></div>'+
		'</div>',
	    
	'commentBox' : 
		'<div class="aw-comment-box" id="{{comment_form_id}}">'+
			'<form action="{{comment_form_action}}" method="post" onsubmit="return false">'+
				'<div class="mod-head">'+
					'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'</div>'+
				'<div class="mod-body">'+
					'<i></i>'+
					'<i class="active"></i>'+
					'<textarea name="message" id="" rows="2" class="form-control"></textarea>'+
				'</div>'+
				'<div class="mod-footer">'+
					'<a class="btn btn-gray btn-mini cancel">' + _t('取消') + '</a>'+
					'<a class="btn btn-success btn-mini pull-right" onclick="AWS.User.save_comment($(this));">' + _t('评论') + '</a>'+
				'</div>'+
			'</form>'+
		'</div>',

	'commentBoxClose' : 
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p class="text-center margin-0"><i class="aw-loading"></i></p></div>'+
				'<i class="i-dropdown-triangle"></i>'+
			'</div>',

	'articleCommentBox' :
		'<div class="aw-comment-box" id="{{comment_form_id}}">'+
			'<form action="'+ G_BASE_URL +'/article/ajax/save_comment/" method="post" onsubmit="return false">'+
				'<input type="hidden" name="at_uid" value="{{at_uid}}">'+
				'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
				'<input type="hidden" name="article_id" value="{{article_id}}" />'+
				'<div class="mod-body">'+
					'<i></i>'+
					'<i class="active"></i>'+
					'<textarea name="message" id="" rows="2" class="form-control"></textarea>'+
				'</div>'+
				'<div class="mod-footer">'+
					'<a class="btn btn-gray btn-mini cancel">' + _t('取消') + '</a>'+
					'<a class="btn btn-success btn-mini pull-right" onclick="AWS.ajax_post($(this).parents(\'form\'));">' + _t('评论') + '</a>'+
				'</div>'+
			'</form>'+
		'</div>',

	'topic_edit_box' :
		'<div class="editor clearfix">'+
            '<div class="mod-head">'+
                '<div class="form-group">'+
                    '<input type="text" name="" class="form-control topic-text" placeholder="' + _t('选择话题') + '">'+
                    '<i class="icon icon-search"></i>'+
                    '<div class="aw-dropdown-list hide">'+
						'<ul></ul>'+
					'</div>'+
                '</div>'+
                '<a class="btn btn-gray btn-normal cancel">取消</a>'+
                '<a class="btn btn-success btn-normal add">添加</a>'+
            '</div>'+
        '</div>',

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
							'<div class="aw-upload-box">'+
								'<a class="btn btn-default">上传附件</a>'+
								'<div class="upload-container"></div>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<div class="modal-footer">'+
						'<span><input id="aw-do-delete" type="checkbox" value="1" name="do_delete" /><label for="aw-do-delete">&nbsp;' + _t('删除回复') + '</label></span> &nbsp;&nbsp;'+
						'<button class="btn btn-large btn-success" onclick="AWS.ajax_post($(\'#answer_edit\'), AWS.ajax_processer);return false;">' + _t('确定') + '</button>'+
					'</div>'+
					'</form>'+
				'</div>'+
			'</div>'+
		'</div>'
}