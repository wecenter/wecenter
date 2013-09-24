$(document).ready(function () {
    QUESTION_ID = document.getElementById('question_id').value;
    
    if (ATTACH_ACCESS_KEY != '')
    {
	    init_fileuploader('file_uploader_question', G_BASE_URL + '/publish/ajax/attach_upload/id-question__attach_access_key-' + ATTACH_ACCESS_KEY);
    }

    if (QUESTION_ID && G_UPLOAD_ENABLE == 'Y' && ATTACH_ACCESS_KEY != '')
    {
        if ($("#file_uploader_question ._ajax_upload-list").length) {
            $.post(G_BASE_URL + '/publish/ajax/question_attach_edit_list/', 'question_id=' + QUESTION_ID, function (data) {
                if (data['err']) {
                    return false;
                } else {
                    $.each(data['rsm']['attachs'], function (i, v) {
                        _ajax_uploader_append_file('#file_uploader_question ._ajax_upload-list', v);
                    });
                }
            }, 'json');
        }
    }
    
    // 自动保存草稿
	if ($('textarea#advanced_editor').length)
	{
		$('textarea#advanced_editor').bind('blur', function() {
			if ($(this).val() != '')
			{
				$.post(G_BASE_URL + '/account/ajax/save_draft/item_id-1__type-question', 'message=' + $(this).val(), function (result) {
					$('#question_detail_message').html(result.err + ' <a href="#" onclick="$(\'textarea#advanced_editor\').attr(\'value\', \'\'); delete_draft(1, \'question\'); $(this).parent().html(\' \'); return false;">' + _t('删除草稿') + '</a>');
				}, 'json');
			}
		});
		
		$('#publish_submit').click(function () {
			$('textarea#advanced_editor').unbind('blur');
		});
	}
});