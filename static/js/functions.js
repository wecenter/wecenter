/* 2.5js兼容 */

function ajax_request(url, params)
{

    return AWS.ajax_request(url, params);
}

function ajax_post(formEl, processer) // 表单对象，用 jQuery 获取，回调函数名
{
    if (typeof (processer) != 'function')
    {
        processer = _ajax_post_processer;

        AWS.loading('show');
    }

    var custom_data = {
        _post_type: 'ajax'
    };

    formEl.ajaxSubmit(
    {
        dataType: 'json',
        data: custom_data,
        success: processer,
        error: function (error)
        {
            if ($.trim(error.responseText) != '')
            {
            	AWS.loading('hide');

                alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
            }
        }
    });
}

function _ajax_post_processer(result)
{
	AWS.loading('hide');

    if (typeof (result.errno) == 'undefined')
    {
        AWS.alert(result);
    }
    else if (result.errno != 1)
    {
        AWS.alert(result.err);
    }
    else
    {
        if (result.rsm && result.rsm.url)
        {
            window.location = decodeURIComponent(result.rsm.url);
        }
        else
        {
            window.location.reload();
        }
    }
}

function _ajax_post_modal_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        alert(result.err);
    }
    else
    {
        if (result.rsm && result.rsm.url)
        {
            window.location = decodeURIComponent(result.rsm.url);
        }
        else
        {
            $('#aw-ajax-box div.modal').modal('hide');
        }
    }
}

function _ajax_post_alert_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        alert(result.err);
    }
    else
    {
        if (result.rsm && result.rsm.url)
        {
            window.location = decodeURIComponent(result.rsm.url);
        }
        else
        {
            window.location.reload();
        }
    }
}

function _ajax_post_background_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        AWS.alert(result.err);
    }
}

function _ajax_post_confirm_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        if (!confirm(result.err))
        {
            return false;
        }
    }

    if (result.errno == 1 && result.err)
    {
        alert(result.err);
    }

    if (result.rsm && result.rsm.url)
    {
        window.location = decodeURIComponent(result.rsm.url);
    }
    else
    {
        window.location.reload();
    }
}

function _error_message_form_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
    	if (!$('.error_message').length)
    	{
	    	alert(result.err);
    	}
    	else if ($('.error_message em').length)
    	{
	    	$('.error_message em').html(result.err);
    	}
    	else
    	{
	    	 $('.error_message').html(result.err);
    	}

    	if ($('.error_message').css('display') != 'none')
    	{
	    	shake($('.error_message'));
    	}
    	else
    	{
	    	$('.error_message').fadeIn();
    	}
    }
    else
    {
        if (result.rsm && result.rsm.url)
        {
            window.location = decodeURIComponent(result.rsm.url);
        }
        else
        {
            window.location.reload();
        }
    }
}

function _comments_form_processer(result)
{
    $.each($('a._save_comment.disabled'), function (i, e)
    {

        $(e).attr('onclick', $(this).attr('_onclick')).removeAttr('_onclick').removeClass('disabled').removeClass('_save_comment');
    });

    if (result.errno != 1)
    {
        $.alert(result.err);
    }
    else
    {
        reload_comments_list(result.rsm.item_id, result.rsm.item_id, result.rsm.type_name);

        $('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form input').val('');
        $('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form textarea').val('');
    }
}

function _quick_publish_processer(result)
{
    if (typeof (result.errno) == 'undefined')
    {
        alert(result);
    }
    else if (result.errno != 1)
    {
        $('#quick_publish_error em').html(result.err);
        $('#quick_publish_error').fadeIn();
    }
    else
    {
        if (result.rsm && result.rsm.url)
        {
            window.location = decodeURIComponent(result.rsm.url);
        }
        else
        {
            window.location.reload();
        }
    }
}

function shake(element)
{
    return AWS.shake(element);
}

function focus_question(el, question_id)
{
    return AWS.User.follow(el, 'question', question_id);
}

function focus_topic(el, topic_id)
{
    return AWS.User.follow(el, 'topic', topic_id);
}

function follow_people(el, uid)
{
    return AWS.User.follow(el, 'user', uid);
}

function check_notifications()
{
    return AWS.Message.check_notifications();
}

function read_notification(notification_id, el, reload)
{
    return AWS.Message.read_notification(notification_id, el, reload);
}

function notification_show(length)
{
    return AWS.Message.notification_show(length);
}

function ajax_load(url, target)
{
    $(target).html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');

    $.get(url, function (response)
    {
        if (response.length)
        {
            $(target).html(response);
        }
        else
        {
            $(target).html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
        }
    });
}

function bp_more_load(url, bp_more_o_inner, target_el, start_page, callback_func)
{
    return AWS.load_list_view(url, bp_more_o_inner, target_el, start_page, callback_func);
}

function content_switcher(hide_el, show_el)
{
    return AWS.content_switcher(hide_el, show_el);
}

function hightlight(el, class_name)
{
    return AWS.hightlight(el, class_name);
}

function nl2br(str)
{
    return str.replace(new RegExp("\r\n|\n\r|\r|\n", "g"), "<br />");
}

function init_img_uploader(upload_url, upload_name, upload_element, upload_status_elememt, perview_element)
{
    return AWS.Init.init_img_uploader(upload_url, upload_name, upload_element, upload_status_elememt, perview_element);
}

function init_avatar_uploader(upload_element, upload_status_elememt, avatar_element)
{
    return init_img_uploader(G_BASE_URL + '/account/ajax/avatar_upload/', 'user_avatar', upload_element, upload_status_elememt, avatar_element);
}

function init_fileuploader(element_id, action_url)
{
    return AWS.Init.init_fileuploader(element_id, action_url);
}

function htmlspecialchars(text)
{
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function delete_draft(item_id, type)
{
    return AWS.User.delete_draft(item_id, type);
}

function agree_vote(answer_id, value)
{
    return AWS.User.agree_vote(answer_id, value);
}

function question_uninterested(el, question_id)
{
    return AWS.User.question_uninterested(el, question_id);
}

function question_invite_delete(block_el, question_invite_id)
{
    return AWS.User.question_invite_delete(block_el, question_invite_id);
}

function reload_comments_list(item_id, element_id, type_name)
{
    return AWS.reload_comments_list(item_id, element_id, type_name);
}

function save_comment(save_button_el)
{
    return AWS.User.save_comment(save_button_el);
}

function remove_comment(el, type, comment_id)
{
	return AWS.User.remove_comment(el, type, comment_id);
}

function insert_attach(element, attach_id, attach_tag)
{
    return AWS.Editor.insert_attach(element, attach_id, attach_tag);
}

function question_thanks(question_id, element)
{
    return AWS.User.question_thanks(question_id, element);
}

function answer_user_rate(answer_id, type, element)
{
    return AWS.User.answer_user_rate(element, type, answer_id);
}

function init_comment_box(selector)
{
    return AWS.Init.init_comment_box(selector);
}

function init_article_comment_box(selector)
{
    return AWS.Init.init_article_comment_box(selector);
}

function insertVoteBar(data)
{
    return AWS.Init.init_vote_bar(data);
}

function agreeVote(element, user_name, answer_id)
{
	return AWS.User.agree_vote(element, user_name, answer_id);
}

function disagreeVote(element, user_name, answer_id)
{
    return AWS.User.disagree_vote(element, user_name, answer_id);
}

function init_topic_edit_box(selector) //selector -> .aw-edit-topic
{
    return AWS.Init.init_topic_edit_box(selector);
}

function show_card_box(selector, type, time) //selector -> .aw-user-name/.aw-topic-name
{
    return AWS.show_card_box(selector, type, time);
}

function invite_user(obj, img)
{
	return AWS.User.invite_user(obj, img);
}

function disinvite_user(obj)
{
    return AWS.User.disinvite_user(obj);
}

function add_dropdown_list(selector, data, selected)
{
    return AWS.Dropdown.set_dropdown_list(selector, data, selected);
}

function bind_dropdown_list(selector, type)
{
    return AWS.Dropdown.bind_dropdown_list(selector, type);
}

function get_dropdown_list(selector, type, data)
{
    return AWS.Dropdown.get_dropdown_list(selector, type, data);
}

function at_user_lists(selector) {
    return AWS.at_user_lists(selector);
}

function article_vote(element, article_id, rating)
{
	return AWS.User.article_vote(element, article_id, rating);
}

function comment_vote(element, comment_id, rating)
{
	return AWS.User.article_comment_vote(element, comment_id, rating);
}

$.extend(
    {
        //警告弹窗
        alert : function (text)
        {
            if ($('.alert-box').length)
            {
                $('.alert-box').remove();
            }

            $('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.alertBox).render(
            {
                message: text
            }));

            $(".alert-box").modal('show');
        },

        /**
         *  公共弹窗
         *  alert       : 普通问题提示
         *  publish     : 发起
         *  shareOut    : 站外分享
         *  redirect    : 问题重定向
         *  imageBox    : 插入图片
         *  videoBox    : 插入视频
         *  linkbox     : 插入链接
         *  commentEdit : 评论编辑
         *  favorite    : 评论添加收藏
         *  inbox       : 私信
         *  report      : 举报问题
         */
        dialog : function (type, data)
        {
            return AWS.dialog(type, data);
        }

    });

