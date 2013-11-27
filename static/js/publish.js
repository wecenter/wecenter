$(document).ready(function () {
	$('.aw-edit-topic').click();
	
	if (document.getElementById('question_id'))
	{
		ITEM_ID = document.getElementById('question_id').value;
	}
	else if (document.getElementById('article_id'))
	{
		ITEM_ID = document.getElementById('article_id').value;
	}
    
    if (ATTACH_ACCESS_KEY != '')
    {
	    init_fileuploader('file_uploader_question', G_BASE_URL + '/publish/ajax/attach_upload/id-' + PUBLISH_TYPE + '__attach_access_key-' + ATTACH_ACCESS_KEY);
    }

    if (ITEM_ID && G_UPLOAD_ENABLE == 'Y' && ATTACH_ACCESS_KEY != '')
    {
        if ($("#file_uploader_question ._ajax_upload-list").length) {
            $.post(G_BASE_URL + '/publish/ajax/' + PUBLISH_TYPE + '_attach_edit_list/', PUBLISH_TYPE + '_id=' + ITEM_ID, function (data) {
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
				$.post(G_BASE_URL + '/account/ajax/save_draft/item_id-1__type-' +　PUBLISH_TYPE, 'message=' + $(this).val(), function (result) {
					$('#question_detail_message').html(result.err + ' <a href="#" onclick="$(\'textarea#advanced_editor\').attr(\'value\', \'\'); delete_draft(1, \'' + PUBLISH_TYPE + '\'); $(this).parent().html(\' \'); return false;">' + _t('删除草稿') + '</a>');
				}, 'json');
			}
		});
		
		$('#publish_submit').click(function () {
			$('textarea#advanced_editor').unbind('blur');
		});
	}
	
	if ($('#category_id').length)
	{
		var category_data = '';
		
		$.each($('#category_id option').toArray(), function (i, field) {
			if (i > 0)
			{
				if (i > 1)
				{
					category_data += ',';
				}
				
				category_data += "{'title':'" + $(field).text() + "', 'id':'" + $(field).val() + "'}";
			}
		});
		
		add_dropdown_list('.aw-publish-title .aw-publish-title-dropdown', eval('[' + category_data + ']'), CATEGORY_ID);
		
		$('.aw-publish-title .aw-publish-title-dropdown li a').click(function() {
			$('#category_id').val($(this).attr('data-value'));
		});
		
		if (CATEGORY_ID)
		{
			$('#quick_publish_category_id').val(CATEGORY_ID);
		}
	}

	if ($('.aw-publish-title .aw-publish-title-dropdown').length)
	{
		$.each($('.aw-publish-title .aw-publish-title-dropdown .aw-dropdown-menu li a'),function(i, e)
		{
			if ($(e).attr('data-value') == $('#category_id').val())
			{
				$('#aw-topic-tags-select').html($(e).html());
				return;
			}
		});
	}
});