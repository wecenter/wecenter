$(document).ready(function() {
	if ($('.tabbable').length)
	{		
		bp_more_load(G_BASE_URL + '/question/ajax/discuss/sort_type-new__topic_id-' + CONTENTS_TOPIC_ID, $('#c_question_more'), $('#c_question_list'), 2);
		
		bp_more_load(G_BASE_URL + '/topic/ajax/question_list/type-best__topic_id-' + CONTENTS_TOPIC_ID, $('#bp_best_question_more'), $('#c_best_question_list'), 2);
		
		bp_more_load(G_BASE_URL + '/topic/ajax/question_list/type-favorite__topic_title-' + encodeURIComponent(CONTENTS_TOPIC_TITLE), $('#bp_favorite_more'), $('#c_favorite_list'), 0, function () { if ($('#c_favorite_list a').attr('id')) { $('#i_favorite').show() } });
	}

	
	if ($('#focus_users').attr('id'))
	{
		$.get(G_BASE_URL + '/topic/ajax/get_focus_users/topic_id-' + TOPIC_ID, function (data) {
			$.each(data, function (i, d) {		
				$('#focus_users').append('<a href="' + d['url'] + '"><img src="' + d['avatar_file'] + '" alt="' + d['user_name'] + '" /></a> ');
			});
		}, 'json');
	}

	
	if ($('#topic_pic_uploader').attr('id'))
	{
		init_img_uploader(G_BASE_URL + '/topic/ajax/upload_topic_pic/topic_id-' + TOPIC_ID, 'topic_pic', $('#topic_pic_uploader'), $('#uploading_status'), $('#topic_pic'));
	}
});


/*$.editor_topic_x = function(flg){
	$(flg).hide();
	$('#editor_input_handle').removeClass('i_hide');
	$('#i_PublicTopic >a').each(function(index, element) {
		var flgs = $(this);
        if(flgs.find('em').length >0 ){
			flgs.addClass('i_prl pd').find('em').show();
		}else{
			$('<em/>').addClass('handle i_pas')
			.attr('onclick','$.delete_topicx(this,event)')
			.html('×')
			.appendTo(flgs.addClass('i_prl pd'));
		}
    });
}

$.establish_topicx = function(){
	if($('#editor_input').val() == _t('创建添加相关话题') + '...' || $.trim($('#editor_input').val()).length == 0){
		$('#tips_err').show().html(_t('请输入话题标题'))
		$('#editor_input').val('').focus();
		return ;
	}else if($.trim($('#editor_input').val()).length == 1){
		$('#tips_err').show().html(_t('请输入两个以上相关话题'))
		$('#editor_input').val('').focus();
		return ;
	}else{
		$.ajax({
			type:'POST',
			url:G_BASE_URL+"/topic/ajax/save_related_topic/topic_id-"+TOPIC_ID,
			data:'topic_title='+$.trim($('#editor_input').val()),
			dataType:"json",
			success: function(s){
				if(s.rsm != null ){
				$('<a/>').addClass('i_glotopic user_msg')
				.attr('data-message','&uid='+s.rsm.related_id+'&card=topic')
				.html($.trim($('#editor_input').val()))
				.attr('href',G_BASE_URL+'/topic/'+s.rsm.related_id).appendTo($('#i_PublicTopic'));
				
				$.exit_topic();
			}
				if(s.errno == -1){
					$('#tips_err').show().html(s.err)
				}
			}
		})
	}
}

//删除
$.delete_topicx = function(flg,e){
	var uid = $(flg).parents('a').attr('data-message').split('&')[1].split('uid=')[1];
	if(e){
		e.preventDefault();
		e.stopPropagation();
		$(flg).parents('a').hide('slow',function(){
			$.ajax({
				type:'GET',
				url:G_BASE_URL+"/topic/ajax/remove_related_topic/related_id-"+uid+"__topic_id-"+TOPIC_ID,
				dataType:"json",
				success: function(s){
					$(this).remove();
					$('#tips_err').show().html(_t('话题已删除'))
					setTimeout(function(){
						$('#tips_err').hide('slow').html('')
					},1000)
				}
			})
		})
  }
}

$.exit_topic = function(){
	
	$('#editor_input_handle').addClass('i_hide');
	$('#editor_topicBtns').show();
	$('#editor_input').val('').focus();
	$('#i_PublicTopic >a').each(function(index, element) {
		var flg = $(this);
		if(flg.find('em').length >0 ){
			flg.removeClass('i_prl pd').find('em').hide();
		}
	})
}*/
