var AWS =  
{
	//全局loading
	loading: function (type)
	{
		if (!$('#aw-loading').length)
	    {
	        $('#aw-ajax-box').append(AW_TEMPLATE.loadingBox);
	    }
		    
		if (type == 'show')
		{
			if ($('#aw-loading').css('display') == 'block')
		    {
			    return false;
		    }
			
			$('#aw-loading').fadeIn();
		
			AWS.G.loading_timer = setInterval(function () 
			{
				AWS.G.loading_bg_count -= 1;
				
				$('#aw-loading-box').css('background-position', '0px ' + AWS.G.loading_bg_count * 40 + 'px');
				
				if (AWS.G.loading_bg_count == 1)
				{
					AWS.G.loading_bg_count = 12;
				}
			}, 100);
		}
		else
		{
			$('#aw-loading').fadeOut();
		
			clearInterval(AWS.G.loading_timer);
		}
	},

	ajax_request: function(url, params)
	{
		AWS.loading('show');
		
	    if (params)
	    {
	        $.post(url, params + '&_post_type=ajax', function (result)
	        {
	        	_callback(result);
	        }, 'json').error(function (error)
	        {
	        	_error(error);
	        });
	    }
	    else
	    {
	        $.get(url, function (result)
	        {
	        	_callback(result);
	        }, 'json').error(function (error)
	        {
	        	_error(error);
	        });
	    }

	    function _callback (result)
	    {
	    	AWS.loading('hide');
	        	
        	if (!result)
        	{
	        	return false;
        	}
        	
            if (result.err)
            {
                AWS.alert(result.err);
            }
            else if (result.rsm && result.rsm.url)
            {
                window.location = decodeURIComponent(result.rsm.url);
            }
            else
            {
                window.location.reload();
            }
	    }

	    function _error (error)
	    {
	    	AWS.loading('hide');
	        	
            if ($.trim(error.responseText) != '')
            {
                alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
            }
	    }

	    return false;
	},

	ajax_post: function(formEl, processer, type) // 表单对象，用 jQuery 获取，回调函数名
	{
	    if (typeof (processer) != 'function')
	    {
	        var processer = AWS.ajax_processer;
	        
	        AWS.loading('show');
	    }

	    if (!type)
	    {
	    	var type = 'default';
	    }

	    var custom_data = {
	        _post_type: 'ajax'
	    };

	    formEl.ajaxSubmit(
	    {
	        dataType: 'json',
	        data: custom_data,
	        success: function (result)
	        {
	        	processer(type, result);
	        },
	        error: function (error)
	        {
	            if ($.trim(error.responseText) != '')
	            {
	            	AWS.loading('hide');
	            	
	                alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
	            }
	        }
	    });
	},

	// ajax提交callback
	ajax_processer: function (type, result)
	{
		if (type == 'default')
		{
			AWS.loading('hide');
		}
		if (typeof (result.errno) == 'undefined')
		{
			AWS.alert(result);
		}
		else if (result.errno != 1)
		{
			switch (type)
			{
				case 'default':
				case 'comments_form':
					AWS.alert(result.err);
				break;

				case 'ajax_post_alert':
				case 'ajax_post_modal':
				case 'error_message':
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
				    	AWS.shake($('.error_message'));
			    	}
			    	else
			    	{
				    	$('.error_message').fadeIn();
			    	}

			    	if ($('#captcha').length)
			    	{
			    		$('#captcha').click();
			    	}
				break;
			}
		}
		else
		{
			if (type == 'comments_form')
			{
				AWS.reload_comments_list(result.rsm.item_id, result.rsm.item_id, result.rsm.type_name);
	        	$('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form input').val('');
	        	$('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form textarea').val('');
	        	$('.aw-comment-box-btn .btn-success').removeClass('disabled');
			}

			if (result.rsm && result.rsm.url)
	        {
	            window.location = decodeURIComponent(result.rsm.url);
	        }
	        else
	        {
	        	switch (type)
	        	{
	        		case 'default':
					case 'ajax_post_alert':
					case 'error_message':
						window.location.reload();
					break;

					case 'ajax_post_modal':
						$('#aw-ajax-box div.modal').modal('hide');
					break;
	        	}
	        }
		}
	},

	// 加载更多
	load_list_view: function(url, selector, container, start_page, callback)
	{
		if (!selector.attr('id'))
	    {
	        return false;
	    }

	    if (!start_page)
	    {
	        start_page = 0
	    }

	    // 把页数绑定在元素上面
	    if (selector.attr('data-page') == undefined)
		{
			selector.attr('data-page', start_page);
		}
		else
		{
			selector.attr('data-page', parents(selector.attr('data-page')) + 1);
		}
	    
	    selector.bind('click', function ()
	    {
	    	var _this = this;

	    	$(this).addClass('loading');

	    	$.get(url + '__page-' + $(_this).attr('data-page'), function (result)
	    	{
	    		$(_this).removeClass('loading');

	    		if ($.trim(result) != '')
	    		{
	    			if ($(_this).attr('data-page') == start_page && $(_this).attr('auto-load') != 'false')
	                {
	                    container.html(result);
	                }
	                else
	                {
	                    container.append(result);
	                }

	                // 页数增加1
	                $(_this).attr('data-page', parseInt($(_this).attr('data-page')) + 1);
	    		}
	    		else
	    		{
	    			//没有内容
	    			if ($(_this).attr('data-page') == start_page && $(_this).attr('auto-load') != 'false')
	                {
	                    container.html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
	                }

	                $(_this).addClass('disabled').unbind('click').bind('click', function () { return false; });

	                $(_this).find('span').html(_t('没有更多了'));
	    		}

	            if (callback != null)
	            {
	                callback();
	            }
	    	});

	    	return false;
	    });

	    // 自动加载
	    if (selector.attr('auto-load') != 'false')
	    {
	        selector.click();
	    }
	},

	// 重新加载评论列表
	reload_comments_list: function(item_id, element_id, type_name)
	{
	    $('#aw-comment-box-' + type_name + '-' + element_id + ' .aw-comment-list').html('<p align="center" class="aw-padding10"><i class="aw-loading"></i></p>');

	    $.get(G_BASE_URL + '/question/ajax/get_' + type_name + '_comments/' + type_name + '_id-' + item_id, function (data)
	    {
	        $('#aw-comment-box-' + type_name + '-' + element_id + ' .aw-comment-list').html(data);
	    });
	},

	// 警告弹窗
	alert: function (text)
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
	 *	公共弹窗
	 *	publish     : 发起
	 *	shareOut    : 站外分享
	 *	redirect    : 问题重定向
	 *	imageBox    : 插入图片
	 *	videoBox    : 插入视频
	 *  linkbox     : 插入链接
	 *	commentEdit : 评论编辑
	 *  favorite    : 评论添加收藏
	 *	inbox       : 私信
	 *  report      : 举报问题
	 */
	dialog: function (type, data)
	{
	    switch (type)
	    {
		    case 'alertImg':
		    	var template = Hogan.compile(AW_TEMPLATE.alertImg).render(
		    	{
		    		'hide': data.hide,
		    		'url': data.url,
		    		'message': data.message
		    	});
		    break;
		
		    case 'publish':
		        var template = Hogan.compile(AW_TEMPLATE.publishBox).render(
		        {
		            'category_id': data.category_id,
		            'ask_user_id': data.ask_user_id
		        });
		    break;
		
		    case 'shareOut':
		        var template = Hogan.compile(AW_TEMPLATE.shareBox).render(
		        {
		            'items': AW_TEMPLATE.shareList
		        });
		    break;
		
		    case 'redirect':
		        var template = Hogan.compile(AW_TEMPLATE.questionRedirect).render(
		        {
		            'data_id': data
		        });
		    break;
		
		    case 'imageBox':
		        var template = Hogan.compile(AW_TEMPLATE.imagevideoBox).render(
		        {
		            'title': _t('插入图片'),
		            'url': 'imgsUrl',
		            'tips': 'imgsAlt',
		            'type': "'img'",
		            'upload' : ''
		        });
		    break;
		
		    case 'videoBox':
		        var template = Hogan.compile(AW_TEMPLATE.imagevideoBox).render(
		        {
		            'title': _t('插入视频'),
		            'url': 'videoUrl',
		            'tips': 'videoTitle',
		            'type_tips' : _t('我们目前支持: 优酷、酷六、土豆、56、新浪播客、乐视、Youtube 与 SWF 文件'),
		            'type': "'video'",
		            'upload' : 'hide'
		        });
		    break;

		    case 'linkbox':
		    	var template = Hogan.compile(AW_TEMPLATE.linkBox).render(
		        {
		            'title': _t('插入链接'),
		            'text' : 'linkText',
		            'url'  : 'linkUrl',
		            'type' : "'link'"
		        });
		    break;
		
		    case 'commentEdit':
		        var template = Hogan.compile(AW_TEMPLATE.editCommentBox).render(
		        {
		            'answer_id': data.answer_id,
		            'attach_access_key': data.attach_access_key
		        });
		    break;
		
		    case 'favorite':
		        var template = Hogan.compile(AW_TEMPLATE.favoriteBox).render(
		        {
		            'answer_id': data
		        });
		    break;
		
		    case 'inbox':
		        var template = Hogan.compile(AW_TEMPLATE.inbox).render(
		        {
		            'recipient': data
		        });
		    break;
		
		    case 'report':
		        var template = Hogan.compile(AW_TEMPLATE.reportBox).render(
		        {
		            'item_type': data.item_type,
		            'item_id': data.item_id
		        });
		    break;
		        
		    case 'topicEditHistory':
		        var template = AW_TEMPLATE.ajaxData.replace('{{title}}', _t('编辑记录')).replace('{{data}}', data);
			break;
				
			case 'ajaxData':
				var template = AW_TEMPLATE.ajaxData.replace('{{title}}', data.title).replace('{{data}}', '<div id="aw_dialog_ajax_data"></div>');
			break;
				
			case 'imagePreview':
				var template = AW_TEMPLATE.ajaxData.replace('{{title}}', _t('图片预览')).replace('{{data}}', '<p align="center"><img src="' + data.image + '" alt="" style="max-width:520px" /></p>');
			break;

			case 'confirm':
				var template = Hogan.compile(AW_TEMPLATE.confirmBox).render(
				{
					'message': data.message
				});
			break;
	    }

	    if (template)
	    {
	        if ($('.alert-box').length)
	        {
	            $('.alert-box').remove();
	        }

	        $('#aw-ajax-box').html(template);

	        switch (type)
	        {
	        	case 'redirect' : 
	        		AWS.Dropdown.bind_dropdown_list($('.aw-question-redirect-box #question-input'), 'redirect');
	        	break;

	        	case 'inbox' :
	        		AWS.Dropdown.bind_dropdown_list($('.aw-inbox #invite-input'), 'inbox');
   					//私信用户下拉点击事件
	        		$(document).on('click','.aw-inbox .aw-dropdown-list li a',function() {
    					$('.alert-box #quick_publish input.form-control').val($(this).text());
    					$(this).parents('.aw-dropdown').hide();
    				});
	        	break;

		        case 'publish':
		        	AWS.Dropdown.bind_dropdown_list($('.aw-publish-box #quick_publish_question_content'), 'publish');
		        	AWS.Dropdown.bind_dropdown_list($('.aw-publish-box #aw_edit_topic_title'), 'topic');
		        	if (parseInt(data.category_enable) == 1)
		        	{
			        	$.get(G_BASE_URL + '/publish/ajax/fetch_question_category/', function (result)
			            {
			                AWS.Dropdown.set_dropdown_list('.aw-publish-box .dropdown', eval(result), data.category_id);
			
			                $('.aw-publish-title .dropdown li a').click(function ()
			                {
			                    $('.aw-publish-box #quick_publish_category_id').val($(this).attr('data-value'));
			                    $('.aw-publish-box #aw-topic-tags-select').html($(this).text());
			                });
			            });
		        	}

		        	if (data.ask_user_id != '' && data.ask_user_id != undefined)
					{
						$('.aw-publish-box .modal-title').html('向 ' + data.ask_user_name + ' 提问');
					}
		
		            if ($('#aw-search-query').val() && $('#aw-search-query').val() != $('#aw-search-query').attr('placeholder'))
		            {
			            $('#quick_publish_question_content').val($('#aw-search-query').val());
		            }
		
		            AWS.Init.init_topic_edit_box('#quick_publish .aw-edit-topic');
		            
		            $('#quick_publish .aw-edit-topic').click();
		
		            if (data.topic_title)
		            {
		                $('#quick_publish .aw-edit-topic').parents('.aw-topic-editor').prepend('<a href="javascript:;" class="aw-topic-name"><span>' + data.topic_title + '</span><input type="hidden" value="' + data.topic_title + '" name="topics[]" /></a>')
		            }
		            
		            if (typeof(G_QUICK_PUBLISH_HUMAN_VALID) != 'undefined')
		            {
			            $('#quick_publish_captcha').show();
			            $('#captcha').click();
		            }
		            break;
				break;

		        case 'shareIn':
		        case 'shareOut':
		        	AWS.Dropdown.bind_dropdown_list($('.aw-share-box #invite-input'), 'inbox');
		
					switch (data.item_type)
					{
						case 'question':
						case 'answer':
						case 'article':
							var request_uri = G_BASE_URL + '/question/ajax/fetch_share_data/type-' + data.item_type + '__item_id-' + data.item_id;
						break;
					}
					
		            $.get(request_uri, function (result)
		            {
		            	$('#share_out_content').val(result.rsm.share_txt.message);
		
		                $('#bdshare').attr('data', '{text:\'' + result.rsm.share_txt.message.replace(' ' + result.rsm.share_txt.url, '') + '\', url:\'' + result.rsm.share_txt.url + '\', \'bdPic\': \'\'}');
		            }, 'json');
		            
		            break;
				break;

		        case 'favorite':
		            $.get(G_BASE_URL + '/favorite/ajax/get_favorite_tags/', function (result)
		            {
		                if (result.length > 0)
		                {
		                    $('#add_favorite_my_tags').show();
		                }
		
		                $.each(result, function (i, a)
		                {
		                    $('#add_favorite_my_tags').append('<a href="javascript:;" onclick="$(\'#add_favorite_tags\').val($(\'#add_favorite_tags\').val() + \'' + a['title'] + ',\');" class="aw-topic-name"><span>' + a['title'] + '</span></a> ');
		                });
		            }, 'json');
		            break;
				break;

		        case 'report':
		            $('.aw-report-box select option').click(function ()
		            {
		                $('.aw-report-box textarea').text($(this).attr('value'));
		            });
		            break;
				break;

		        case 'commentEdit':
		            $.get(G_BASE_URL + '/question/ajax/fetch_answer_data/' + data.answer_id, function (result)
		            {
		                $('#editor_reply').html(result.answer_content.replace('&amp;', '&'));
		            }, 'json');
					
		            AWS.Init.init_fileuploader('file_uploader_answer_edit', G_BASE_URL + '/publish/ajax/attach_upload/id-answer__attach_access_key-' + ATTACH_ACCESS_KEY);
		            
		            if ($("#file_uploader_answer_edit ._ajax_upload-list").length) {
			            $.post(G_BASE_URL + '/publish/ajax/answer_attach_edit_list/', 'answer_id=' + data.answer_id, function (data) {
			                if (data['err']) {
			                    return false;
			                } else {
			                    $.each(data['rsm']['attachs'], function (i, v) {
			                        _ajax_uploader_append_file('#file_uploader_answer_edit ._ajax_upload-list', v);
			                    });
			                }
			            }, 'json');
			        }
		            break;
		        break;

		        case 'ajaxData':
			    	$.get(data.url, function (result) {
						$('#aw_dialog_ajax_data').html(result);
					});
		    	break;
	        }

	        $(".alert-box").modal('show');
	    }
	},

	// 回复背景高亮
	hightlight: function(selector, class_name)
	{
	    if (selector.hasClass(class_name))
	    {
	        return true;
	    }

	    var hightlight_timer_front = setInterval(function ()
	    {
	        selector.addClass(class_name);
	    }, 500);

	    var hightlight_timer_background = setInterval(function ()
	    {
	        selector.removeClass(class_name);
	    }, 600);

	    setTimeout(function ()
	    {
	        clearInterval(hightlight_timer_front);
	        clearInterval(hightlight_timer_background);

	        selector.addClass(class_name);
	    }, 1200);

	    setTimeout(function ()
	    {
	        selector.removeClass(class_name);
	    }, 6000);
	},

	nl2br: function(str)
	{
	    return str.replace(new RegExp("\r\n|\n\r|\r|\n", "g"), "<br />");
	},

	content_switcher: function(hide_el, show_el)
	{
	    hide_el.hide();
	    show_el.fadeIn();
	},

	htmlspecialchars: function(text)
	{
	    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	},

	/*
	 * 用户头像提示box效果
	 *  @params
	 *  type : user/topic
	 *	nTop    : 焦点到浏览器上边距
	 *	nRight  : 焦点到浏览器右边距
	 *	nBottom : 焦点到浏览器下边距
	 *	left    : 焦点距离文档左偏移量
	 *	top     : 焦点距离文档上偏移量
	 **
	 */
	show_card_box: function(selector, type, time) //selector -> .aw-user-name/.aw-topic-name
	{
		if (!time)
		{
			var time = 300;
		}

	    $(document).on('mouseover', selector, function ()
	    {
	        clearTimeout(AWS.G.card_box_hide_timer);
	        var _this = $(this);
	        AWS.G.card_box_show_timer = setTimeout(function ()
	        {
	            //判断用户id or 话题id 是否存在
	            if (_this.attr('data-id'))
	            {
	                 switch (type)
	                {
	                    case 'user' : 
	                        //检查是否有缓存
	                        if (AWS.G.cashUserData.length == 0)
	                        {
	                            _getdata('user', '/people/ajax/user_info/uid-');
	                        }
	                        else
	                        {
	                            var flag = 0;
	                            //遍历缓存中是否含有此id的数据
	                            _checkcash('user');
	                            if (flag == 0)
	                            {
	                                _getdata('user', '/people/ajax/user_info/uid-');
	                            }
	                        }
	                    break;

	                    case 'topic' :
	                        //检查是否有缓存
	                        if (AWS.G.cashTopicData.length == 0)
	                        {
	                            _getdata('topic', '/topic/ajax/topic_info/topic_id-');
	                        }
	                        else
	                        {
	                            var flag = 0;
	                            //遍历缓存中是否含有此id的数据
	                            _checkcash('topic');
	                            if (flag == 0)
	                            {
	                                _getdata('topic', '/topic/ajax/topic_info/topic_id-');
	                            }
	                        }
	                    break;
	                }
	            }

	            //获取数据
	            function _getdata(type, url)
	            {
	                if (type == 'user')
	                {
	                    $.get(G_BASE_URL + url + _this.attr('data-id'), function(result)
	                    {
	                        var focus = result.focus, verified = result.verified, focusTxt;
	                           
	                        if (focus == 1)
	                        {
	                            focus = '';
	                            focusTxt = '取消关注';
	                        }
	                        else
	                        {
	                            focus = 'aw-active';
	                            focusTxt = '关注';
	                        }
	                        
	                        if(result.verified == 'enterprise')
	                        {
	                            verified_enterprise = 'icon-v i-ve';
	                            verified_title = '企业认证';
	                        }
	                        else if(result.verified == 'personal')
	                        {
	                            verified_enterprise = 'icon-v';
	                            verified_title = '个人认证';
	                        }
	                        else
	                        {
	                            verified_enterprise = verified_title = '';
	                        }
	                        
	                        //动态插入盒子
	                        $('#aw-ajax-box').html(Hogan.compile(AW_TEMPLATE.userCard).render(
	                        {
	                            'verified_enterprise' : verified_enterprise,
	                            'verified_title' : verified_title,
	                            'uid': result.uid,
	                            'avatar_file': result.avatar_file,
	                            'user_name': result.user_name,
	                            'reputation': result.reputation,
	                            'agree_count': result.agree_count,
	                            'signature': result.signature,
	                            'url' : result.url,
	                            'category_enable' : result.category_enable,
	                            'focus': focus,
	                            'focusTxt': focusTxt,
	                            'ask_name': "'" + result.user_name + "'"
	                        }));

	                        //判断是否为游客or自己
	                        if (G_USER_ID <= 0 || G_USER_ID == result.uid || result.uid < 0)
	                        {
	                            $('#aw-card-tips .aw-mod-footer').hide();
	                        }
	                        _init();
	                        //缓存
	                        AWS.G.cashUserData.push($('#aw-ajax-box').html());
	                    }, 'json');
	                }
	                if (type == 'topic')
	                {
	                    $.get(G_BASE_URL + url + _this.attr('data-id'), function(result)
	                    {
	                        var focus = result.focus,
	                            focusTxt;
	                            if (focus > 0)
	                            {
	                                focus = '';
	                                focusTxt = _t('取消关注');
	                            }
	                            else
	                            {
	                                focus = 'aw-active';
	                                focusTxt = _t('关注');
	                            }
	                            //动态插入盒子
	                            $('#aw-ajax-box').html(Hogan.compile(AW_TEMPLATE.topicCard).render(
	                            {
	                                'topic_id': result.topic_id,
	                                'topic_pic': result.topic_pic,
	                                'topic_title': result.topic_title,
	                                'topic_description': result.topic_description,
	                                'discuss_count': result.discuss_count,
	                                'focus_count': result.focus_count,
	                                'focus': focus,
	                                'focusTxt': focusTxt,
	                                'url' : result.url
	                            }));
	                            //判断是否为游客
	                            if (G_USER_ID == 0)
	                            {
	                                $('#aw-card-tips .aw-mod-footer .focus').hide();
	                            }
	                            _init();
	                            //缓存
	                            AWS.G.cashTopicData.push($('#aw-ajax-box').html());
	                    }, 'json');
	                }
	            }

	            //检测缓存
	            function _checkcash(type)
	            {
	                if (type == 'user')
	                {
	                    $.each(AWS.G.cashUserData, function (i, a)
	                    {
	                        if (a.match('data-id="' + _this.attr('data-id') + '"'))
	                        {
	                            $('#aw-ajax-box').html(a);
	                            $('#aw-card-tips').removeAttr('style');
	                            _init();
	                            flag = 1;
	                        }
	                    });
	                }
	                if (type == 'topic')
	                {

	                    $.each(AWS.G.cashTopicData, function (i, a)
	                    {
	                        if (a.match('data-id="' + _this.attr('data-id') + '"'))
	                        {
	                            $('#aw-ajax-box').html(a);
	                            $('#aw-card-tips').removeAttr('style');
	                            _init();
	                            flag = 1;
	                        }
	                    });
	                }
	            }

	            //初始化
	            function _init()
	            {
	            	var left = _this.offset().left,
	            		top = _this.offset().top + _this.height() + 5,
	            		nTop = _this.offset().top - $(window).scrollTop();
			            
	            	//判断下边距离不足情况
	            	if (nTop + $('#aw-card-tips').innerHeight() > $(window).height())
	            	{
	            		top = _this.offset().top - ($('#aw-card-tips').innerHeight()) - 10;
	            	}

	            	//判断右边距离不足情况
	            	if (left + $('#aw-card-tips').innerWidth() > $(window).width())
	            	{
	            		left = _this.offset().left - $('#aw-card-tips').innerWidth() + _this.innerWidth();
	            	}

	                $('#aw-card-tips').css(
	                {
	                    left: left,
	                    top: top
	                }).fadeIn();
	            }
	        }, time);
	    });

	    $(document).on('mouseout', selector, function ()
	    {
	        clearTimeout(AWS.G.card_box_show_timer);
	        AWS.G.card_box_hide_timer = setTimeout(function ()
	        {
	            $('#aw-card-tips').fadeOut();
	        }, 600); 
	    });
	},

	// @人功能
	at_user_lists: function(selector) {
	    $(selector).keyup(function (e) {
	        var _this = $(this),
	            flag = _getCursorPosition($(this)[0]).start;
	        if ($(this).val().charAt(flag - 1) == '@')
	        {
	        	_init();
	        	$('#aw-ajax-box .content_cursor').html($(this).val().substring(0, flag));
	        } else
	        {
	        	var lis = $('.aw-invite-dropdown li');
	            switch (e.which)
	            {
	                case 38:
	                	var _index;
	                	if (!lis.hasClass('active'))
		            	{
		            		lis.eq(lis.length - 1).addClass('active');
		            	}
	                	else
	                	{
	                		$.each(lis, function (i, e)
		            		{
		            			if ($(this).hasClass('active'))
								{
									$(this).removeClass('active');
									if ($(this).index() == 0)
									{
										_index = lis.length - 1;
									}
									else
									{
										_index = $(this).index() - 1;
									}
								}
		            		});
		            		lis.eq(_index).addClass('active');
	                	}
	                    break;
	                case 40:
	                    var _index;
		            	if (!lis.hasClass('active'))
		            	{
		            		lis.eq(0).addClass('active');
		            	}
		            	else
		            	{
		            		$.each(lis, function (i, e)
		            		{
		            			if ($(this).hasClass('active'))
								{
			            			$(this).removeClass('active');
			            			if ($(this).index() == lis.length - 1)
			            			{
			            				_index = 0;
			            			}
			            			else
			            			{
			            				_index = $(this).index() + 1;
			            			}
			            		}
		            		});
		            		lis.eq(_index).addClass('active');
		            	}
	                    break;
	                case 13:
	                	$.each($('.aw-invite-dropdown li'), function (i, e)
	                	{
	                		if ($(this).hasClass('active'))
	                		{
	                			$(this).click();
	                		}
	                	});
	                    break;
	                default:
	                    if ($('.aw-invite-dropdown')[0])
	                    {
	                        var ti = 0;
	                        for (var i = flag; i > 0; i--)
	                        {
	                            if ($(this).val().charAt(i) == "@")
	                            {
	                                ti = i;
	                                break;
	                            }
	                        }
	                        $.get(G_BASE_URL + '/search/ajax/search/?type=users&q=' + encodeURIComponent($(this).val().substring(flag, ti).replace('@', '')) + '&limit=10', function (result)
	                        {
	                            if ($('.aw-invite-dropdown')[0])
	                            {
	                                if (result.length != 0)
	                                {
	                                    $('.aw-invite-dropdown').html('');
	                                    $.each(result, function (i, a)
	                                    {
	                                        $('.aw-invite-dropdown').append('<li><img src="' + a.detail.avatar_file + '"/><a>' + a.name + '</a></li>')
	                                    });
	                                    _display();
	                                    $('.aw-invite-dropdown li').click(function ()
	                                    {
	                                        _this.val(_this.val().substring(0, ti) + '@' + $(this).find('a').html() + " ").focus();
	                                        $('.aw-invite-dropdown').detach();
	                                    });
	                                }
	                                else
	                                {
	                                    $('.aw-invite-dropdown').hide();
	                                }
	                            }
	                            if (_this.val().length == 0)
	                            {
	                                $('.aw-invite-dropdown').hide();
	                            }
	                        }, 'json');
	                    }
	            }
	        }
	    });

	    $(selector).keydown(function (e) {
	        var key = e.which;
	        if ($('.aw-invite-dropdown').is(':visible')) {
	            if (key == 38 || key == 40 || key == 13) {
	                return false;
	            }
	        }
	    });

	    //初始化插入定位符
	    function _init() {
	        if (!$('.content_cursor')[0]) {
	            $('#aw-ajax-box').append('<span class="content_cursor"></span>');
	        }
	        $('#aw-ajax-box').find('.content_cursor').css({
	            'left': parseInt($(selector).offset().left + parseInt($(selector).css('padding-left')) + 2),
	            'top': parseInt($(selector).offset().top + parseInt($(selector).css('padding-left')))
	        });
	        if (!$('.aw-invite-dropdown')[0])
            {
                $('#aw-ajax-box').append('<ul class="aw-invite-dropdown"></ul>');
            }
	    };

	    //初始化列表和三角型
	    function _display() {
	        $('.aw-invite-dropdown').css({
	            'left': $('.content_cursor').offset().left + $('.content_cursor').innerWidth(),
	            'top': $('.content_cursor').offset().top + 24
	        }).show();
	    };

	    //获取当前textarea光标位置
		function _getCursorPosition(textarea)
		{
		    var rangeData = {
		        text: "",
		        start: 0,
		        end: 0
		    };
		    
		    textarea.focus();
		    
		    if (textarea.setSelectionRange) { // W3C
		        rangeData.start = textarea.selectionStart;
		        rangeData.end = textarea.selectionEnd;
		        rangeData.text = (rangeData.start != rangeData.end) ? textarea.value.substring(rangeData.start, rangeData.end) : "";
		    } else if (document.selection) { // IE
		        var i,
		            oS = document.selection.createRange(),
		            // Don't: oR = textarea.createTextRange()
		            oR = document.body.createTextRange();
		        oR.moveToElementText(textarea);

		        rangeData.text = oS.text;
		        rangeData.bookmark = oS.getBookmark();

		        // object.moveStart(sUnit [, iCount])
		        // Return Value: Integer that returns the number of units moved.
		        for (i = 0; oR.compareEndPoints('StartToStart', oS) < 0 && oS.moveStart("character", -1) !== 0; i++) {
		            // Why? You can alert(textarea.value.length)
		            if (textarea.value.charAt(i) == '\n') {
		                i++;
		            }
		        }
		        rangeData.start = i;
		        rangeData.end = rangeData.text.length + rangeData.start;
		    }

		    return rangeData;
		};
	},

	// 错误提示效果
	shake: function(selector)
	{
		var length = 6;
	    for (var i = 1; i <= length; i++)
	    {
	    	if (i % 2 == 0) 
	    	{
	        	if (i == length)
	        	{
	        		selector.animate({ 'left': 0 }, 50);
	        	}
	        	else
	        	{
	        		selector.animate({ 'left': 10 }, 50);
	        	}
	    	}
	    	else
	    	{
	    		selector.animate({ 'left': -10 }, 50);
	    	}
	    }
	}
}

// 全局变量
AWS.G = 
{
	cashUserData: [],
	cashTopicData: [],
	card_box_hide_timer: '',
	card_box_show_timer: '',
	dropdown_list_xhr: '',
	loading_timer: '',
	loading_bg_count: 12,
	notification_timer: ''
}

AWS.User = 
{
	// 关注
	follow: function(selector, type, data_id)
	{
		if (selector.html())
	    {
	        if (!selector.hasClass('aw-active'))
	        {
	            selector.html(_t('关注'));
	        }
	        else
	        {
	            selector.html(_t('取消关注'));
	        }
	    }
	    else
	    {
	        if (!selector.hasClass('aw-active'))
	        {
	            selector.attr('data-original-title', _t('关注'));
	        }
	        else
	        {
	            selector.attr('data-original-title', _t('取消关注'));
	        }
	    }

	    selector.addClass('disabled');

	    switch (type)
	    {
	    	case 'question':
	    		var url = '/question/ajax/focus/question_id-';
	    		break;

	    	case 'topic':
	    		var url = '/topic/ajax/focus_topic/topic_id-';
	    		break;

	    	case 'user':
	    		var url = '/follow/ajax/follow_people/uid-';
	    	break;
	    }

	    $.get(G_BASE_URL + url + data_id, function (result)
	    {
	    	if (result.errno == 1)
	        {
	            if (result.rsm.type == 'add')
	            {
	                selector.removeClass('aw-active');
	            }
	            else
	            {
	                selector.addClass('aw-active');
	            }
	        }
	        else
	        {
	            if (result.err)
	            {
	                AWS.alert(result.err);
	            }

	            if (result.rsm.url)
	            {
	                window.location = decodeURIComponent(result.rsm.url);
	            }
	        }

	        selector.removeClass('disabled');
	        
	    }, 'json');
	},

	// 删除草稿
	delete_draft: function(item_id, type)
	{
	    $.post(G_BASE_URL + '/account/ajax/delete_draft/', 'item_id=' + item_id + '&type=' + type, function (result)
	    {
	        if (result.errno != 1)
	        {
	            AWS.alert(result.err);
	        }
	    }, 'json');
	},

	// 赞成投票
	agree_vote: function(selector, user_name, answer_id)
	{
		$.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=1');
		
	    // 判断是否投票过
	    if ($(selector).parents('.aw-item').find('.aw-agree-by').text().match(user_name))
	    {
	        $.each($(selector).parents('.aw-item').find('.aw-user-name'), function (i, e)
	        {
	            if ($(e).html() == user_name)
	            {
	                if ($(e).prev())
	                {
	                    $(e).prev().remove();
	                }
	                else
	                {
	                    $(e).next().remove();
	                }

	                $(e).remove();
	            }
	        });

	        $(selector).removeClass('active');

	        if (parseInt($(selector).parents('.aw-item').find('.aw-vote-bar-count').html()) != 0)
	        {
	            $(selector).parents('.aw-item').find('.aw-vote-bar-count').html(parseInt($(selector).parents('.aw-item').find('.aw-vote-bar-count').html())-1);
	        }

	        if ($(selector).parents('.aw-item').find('.aw-agree-by a').length == 0)
	        {
	            $(selector).parents('.aw-item').find('.aw-agree-by').hide();
	        }
	    }
	    else
	    {
	        // 判断是否第一个投票
	        if ($(selector).parents('.aw-item').find('.aw-agree-by .aw-user-name').length == 0)
	        {
	            $(selector).parents('.aw-item').find('.aw-agree-by').append('<a class="aw-user-name">' + user_name + '</a>');
	        }
	        else
	        {
	            // 插入动画效果
	            $(selector).parents('.aw-item').find('.aw-agree-by').append('<em>、</em><a class="aw-user-name">' + user_name + '</a>');
	        }

	        $(selector).parents('.aw-item').find('.aw-vote-bar-count').html(parseInt($(selector).parents('.aw-item').find('.aw-vote-bar-count').html())+1);
	        $(selector).parents('.aw-item').find('.aw-agree-by').show();
	        $(selector).parents('.aw-item').find('a.active').removeClass('active');
	        $(selector).addClass('active');
	    }
	},

	// 反对投票
	disagree_vote: function(selector, user_name, answer_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/answer_vote/', 'answer_id=' + answer_id + '&value=-1', function (result) {});

	    // 判断是否投票过
	    if (!$(selector).hasClass('active'))// 没点亮反对
	    {
	        // 删除赞同操作
	        $.each($(selector).parents('.aw-item').find('.aw-user-name'), function (i, e)
	        {
	            if ($(e).html() == user_name)
	            {
	                if ($(e).prev())
	                {
	                    $(e).prev().remove();
	                }
	                else
	                {
	                    $(e).next().remove();
	                }

	                $(e).remove();
	            }
	        });

	        if ($(selector).prev().prev().hasClass('active'))
	        {
	            if (parseInt($(selector).parents('.aw-item').find('.aw-vote-bar-count').html()) != 0)
	            {
	                $(selector).parents('.aw-item').find('.aw-vote-bar-count').html(parseInt($(selector).parents('.aw-item').find('.aw-vote-bar-count').html())-1);
	            }
	        }
	        
	        $(selector).parents('.aw-item').find('a.active').removeClass('active');
	        $(selector).addClass('active');

	        // 判断赞同来自内是否有人
	        if ($(selector).parents('.aw-item').find('.aw-agree-by a').length == 0)
	        {
	            $(selector).parents('.aw-item').find('.aw-agree-by').hide();
	        }
	    }
	    else
	    {
	        $(selector).removeClass('active');
	    }
	},

	// 问题不感兴趣
	question_uninterested: function(selector, question_id)
	{
	    selector.fadeOut();

	    $.post(G_BASE_URL + '/question/ajax/uninterested/', 'question_id=' + question_id, function (result)
	    {
	        if (result.errno != '1')
	        {
	            AWS.alert(result.err);
	        }
	    }, 'json');
	},

	// 回复折叠
	answer_force_fold: function(selector, answer_id)
	{
		$.post(G_BASE_URL + '/question/ajax/answer_force_fold/', 'answer_id=' + answer_id, function (result) {
			if (result.errno != 1)
			{
				AWS.alert(result.err);
			}
			else if (result.errno == 1)
			{
				if (result.rsm.action == 'fold')
				{
					selector.html(selector.html().replace(_t('折叠'), _t('撤消折叠')));
				}
				else
				{
					selector.html(selector.html().replace(_t('撤消折叠'), _t('折叠')));
				}
			}
		}, 'json');
	},

	// 删除别人邀请我回复的问题
	question_invite_delete: function(selector, question_invite_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/question_invite_delete/', 'question_invite_id=' + question_invite_id, function (result)
	    {
	        if (result.errno == 1)
	        {
	            selector.fadeOut();
	        }
	        else
	        {
	            AWS.alert(result.rsm.err);
	        }
	    }, 'json');
	},

	// 邀请用户回答问题
	invite_user: function(selector, img)
	{
	    $.post(G_BASE_URL + '/question/ajax/save_invite/',
	    {
	        'question_id': QUESTION_ID,
	        'uid': selector.attr('data-id')
	    }, function (result)
	    {
	        if (result.errno != -1)
	        {
	            if (selector.parents('.aw-invite-box').find('.invite-list a').length == 0)
	            {
	                selector.parents('.aw-invite-box').find('.invite-list').show();
	            }
	            selector.parents('.aw-invite-box').find('.invite-list').append(' <a class="aw-text-color-999 invite-list-user" data-toggle="tooltip" data-placement="bottom" data-original-title="'+ selector.attr('data-value') +'"><img src='+ img +' /></a>');
	            selector.removeClass('aw-active').attr('onclick','AWS.User.disinvite_user($(this))').text('取消邀请');
	            selector.parents('.aw-question-detail-title').find('.aw-invite-replay .badge').text(parseInt(selector.parents('.aw-question-detail-title').find('.aw-invite-replay .badge').text()) + 1);
	        }
	        else if (result.errno == -1)
	        {
	            AWS.alert(result.err);
	        }
	    }, 'json');
	},

	// 取消邀请用户回答问题
	disinvite_user: function(selector)
	{
	    $.get(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + QUESTION_ID + "__recipients_uid-" + selector.attr('data-id'), function (result)
	    {
	        if (result.errno != -1)
	        {
	            $.each($('.aw-question-detail-title .invite-list a'), function (i, e)
	            {
	                if ($(this).attr('data-original-title') == selector.parents('.main').find('.aw-user-name').text())
	                {
	                    $(this).detach();
	                }
	            });
	            selector.addClass('aw-active').attr('onclick','AWS.User.invite_user($(this),$(this).parents(\'li\').find(\'img\').attr(\'src\'))').text('邀请');
	            selector.parents('.aw-question-detail-title').find('.aw-invite-replay .badge').text(parseInt(selector.parents('.aw-question-detail-title').find('.aw-invite-replay .badge').text()) - 1);
	            if (selector.parents('.aw-invite-box').find('.invite-list').children().length == 0)
	            {
	                selector.parents('.aw-invite-box').find('.invite-list').hide();
	            }
	        }
	    });
	},

	// 问题感谢
	question_thanks: function(selector, question_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/question_thanks/', 'question_id=' + question_id, function (result)
	    {
	        if (result.errno != 1)
	        {
	            AWS.alert(result.err);
	        }
	        else if (result.rsm.action == 'add')
	        {
	            selector.html(selector.html().replace(_t('感谢'), _t('已感谢')));
	            selector.removeAttr('onclick');
	        }
	        else
	        {
	            selector.html(selector.html().replace(_t('已感谢'), _t('感谢')));
	        }
	    }, 'json');
	},

	// 感谢评论回复者
	answer_user_rate: function(selector, type, answer_id)
	{
	    $.post(G_BASE_URL + '/question/ajax/question_answer_rate/', 'type=' + type + '&answer_id=' + answer_id, function (result)
	    {
	        if (result.errno != 1)
	        {
	            AWS.alert(result.err);
	        }
	        else if (result.errno == 1)
	        {
	            switch (type)
	            {
	            case 'thanks':
	                if (result.rsm.action == 'add')
	                {
	                    selector.html(selector.html().replace(_t('感谢'), _t('已感谢')));
	                    selector.removeAttr('onclick');
	                }
	                else
	                {
	                    selector.html(selector.html().replace(_t('已感谢'), _t('感谢')));
	                }
	                break;

	            case 'uninterested':
	                if (result.rsm.action == 'add')
	                {
	                    selector.html(selector.html().replace(_t('没有帮助'), _t('撤消没有帮助')));
	                }
	                else
	                {
	                    selector.html(selector.html().replace(_t('撤消没有帮助'), _t('没有帮助')));
	                }
	                break;
	            }
	        }
	    }, 'json');
	},

	// 提交评论
	save_comment: function(selector)
	{
	    selector.attr('_onclick', selector.attr('onclick')).addClass('disabled _save_comment').removeAttr('onclick');

	    AWS.ajax_post(selector.parents('form'), AWS.ajax_processer, 'comments_form');
	},

	// 删除评论
	remove_comment: function(selector, type, comment_id)
	{
		$.get(G_BASE_URL + '/question/ajax/remove_comment/type-' + type + '__comment_id-' + comment_id);
		
		selector.parents('.aw-comment-box li').fadeOut();
	},

	// 文章赞同
	article_vote: function(selector, article_id, rating)
	{
		AWS.loading('show');
		
		if (selector.hasClass('active'))
		{
			var rating = 0;
		}
		
		$.post(G_BASE_URL + '/article/ajax/article_vote/', 'type=article&item_id=' + article_id + '&rating=' + rating, function (result) {
			
			AWS.loading('hide');
			
			if (result.errno != 1)
		    {
		        AWS.alert(result.err);
		    }
		    else
		    {
				if (rating == 0)
				{
	                selector.find('b').html(parseInt(selector.find('b').html()) - 1).removeClass('active');
				}
	            else if (rating == -1)
	            {
	                if (selector.parents('.aw-article-vote').find('.agree').hasClass('active'))
	                {
	                    selector.parents('.aw-article-vote').find('b').html(parseInt(selector.parents('.aw-article-vote').find('b').html()) - 1);
	                    selector.parents('.aw-article-vote').find('a').removeClass('active');
	                }
	                selector.addClass('active');
	            }
				else
				{
					selector.parents('.aw-article-vote').find('a').removeClass('active');
					selector.addClass('active').find('b').html(parseInt(selector.find('b').html()) + 1);
				}
		    }
		}, 'json');
	},

	// 文章评论赞同
	article_comment_vote: function(selector, comment_id, rating)
	{
		AWS.loading('show');
		
		if (selector.hasClass('active'))
		{
			var rating = 0;
		}
		
		$.post(G_BASE_URL + '/article/ajax/article_vote/', 'type=comment&item_id=' + comment_id + '&rating=' + rating, function (result)
		{
			AWS.loading('hide');
			
			if (result.errno != 1)
		    {
		        AWS.alert(result.err);
		    }
		    else
		    {
				if (rating == 0)
				{
					selector.html(selector.html().replace(_t('我已赞'), _t('赞'))).removeClass('active');
				}
				else
				{
					selector.html(selector.html().replace(_t('赞'), _t('我已赞'))).addClass('active');
				}
		    }
		}, 'json');
	}
}

AWS.Dropdown = 
{
	// 下拉菜单功能绑定
	bind_dropdown_list: function(selector, type)
	{
	    if (type == 'search')
	    {
	        $(selector).focus(function()
	        {
	            $(selector).parent().find('.aw-dropdown').show();
	        });
	    }
	    $(selector).keyup(function(e)
	    {
	        if (type == 'search')
	        {
	            $(selector).parent().find('.search').show().children('a').text($(selector).val());
	        }

	        if ($(selector).val().length >= 1)
	        {
	        	if (e.which != 38 && e.which != 40 && e.which != 188 && e.which != 13)
	        	{
	            	AWS.Dropdown.get_dropdown_list($(this), type, $(selector).val());
	        	}
	        }
	        else
	        {
	           $(selector).parent().find('.aw-dropdown').hide();
	        }

	        if (type == 'topic')
	        {
	        	// 逗号或回车提交
	            if (e.which == 188)
	            {
	                if ($('.aw-edit-topic-box #aw_edit_topic_title').val() != ',')
	                {
	                    $('.aw-edit-topic-box #aw_edit_topic_title').val( $('.aw-edit-topic-box #aw_edit_topic_title').val().substring(0,$('.aw-edit-topic-box #aw_edit_topic_title').val().length-1));
	                    $('.aw-edit-topic-box .aw-dropdown').hide();
	                    $('.aw-edit-topic-box .submit-edit').click();
	                }
	                return false;
	            }

	            // 回车提交
	            if (e.which == 13)
	            {
	            	$('.aw-edit-topic-box .aw-dropdown').hide();
	                $('.aw-edit-topic-box .submit-edit').click(); 
	            	return false;
	            }

	            var lis = $(selector).parent().find('.aw-dropdown-list li');

	            //键盘往下
	            if (e.which == 40 && lis.is(':visible'))
	            {
	            	var _index;
	            	if (!lis.hasClass('active'))
	            	{
	            		lis.eq(0).addClass('active');
	            	}
	            	else
	            	{
	            		$.each(lis, function (i, e)
	            		{
	            			if ($(this).hasClass('active'))
							{
		            			$(this).removeClass('active');
		            			if ($(this).index() == lis.length - 1)
		            			{
		            				_index = 0;
		            			}
		            			else
		            			{
		            				_index = $(this).index() + 1;
		            			}
		            		}
	            		});
	            		lis.eq(_index).addClass('active');
	            		$(selector).val(lis.eq(_index).text());
	            	}
	            }

	            //键盘往上
	            if (e.which == 38 && lis.is(':visible'))
	            {
	            	var _index;
	            	if (!lis.hasClass('active'))
	            	{
	            		lis.eq(lis.length - 1).addClass('active');
	            	}
	            	else
	            	{
	            		$.each(lis, function (i, e)
	            		{
	            			if ($(this).hasClass('active'))
							{
								$(this).removeClass('active');
								if ($(this).index() == 0)
								{
									_index = lis.length - 1;
								}
								else
								{
									_index = $(this).index() - 1;
								}
							}
	            		});
	            		lis.eq(_index).addClass('active');
	            		$(selector).val(lis.eq(_index).text());
	            	}
	            	
	            }
	        }
	    });

	    $(selector).blur(function()
	    {
	        $(selector).parent().find('.aw-dropdown').delay(500).fadeOut(300);
	    });
	},

	// 插入下拉菜单
	set_dropdown_list: function(selector, data, selected)
	{
	    $(selector).append(Hogan.compile(AW_TEMPLATE.dropdownList).render(
	    {
	        'items': data
	    }));

	    $(selector + ' .aw-dropdown-list li a').click(function ()
	    {
	        $('#aw-topic-tags-select').html($(this).text());
	    });

	    if (selected)
	    {
	        $(selector + " .dropdown-menu li a[data-value='" + selected + "']").click();
	    }
	},

	/* 下拉菜单数据获取 */
	/*
	*    type : search, publish, redirect, invite, inbox, topic_question, topic
	*/
	get_dropdown_list: function(selector, type, data)
	{
	    if (AWS.G.dropdown_list_xhr != '')
	    {
	        AWS.G.dropdown_list_xhr.abort(); // 中止上一次ajax请求
	    }
	    var url;
	    switch (type)
	    {
	        case 'search' :
	            url = G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent(data) + '&limit=5';
	        break;

	        case 'publish' :
	            url = G_BASE_URL + '/search/ajax/search/?type=questions&q=' + encodeURIComponent(data) + '&limit=5';
	        break;

	        case 'redirect' :
	            url = G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent(data) + '&type=questions&limit=30';
	        break;

	        case 'invite' :
	        case 'inbox' :
	            url = G_BASE_URL + '/search/ajax/search/?type=users&q=' + encodeURIComponent(data) + '&limit=10';
	        break;

	        case 'topic_question' :
	            url = G_BASE_URL + '/search/ajax/search/?type=questions&q=' + encodeURIComponent(data) + '&topic_ids=' + CONTENTS_TOPIC_ID;
	        break;

	        case 'topic' :
	            url = G_BASE_URL + '/search/ajax/search/?type=topics&q=' + encodeURIComponent(data) + '&limit=10';
	        break;
	    }

	    AWS.G.dropdown_list_xhr = $.get(url, function (result)
	    {
	        if (result.length != 0 && AWS.G.dropdown_list_xhr != undefined)
	        {
	            $(selector).parent().find('.aw-dropdown-list').html(''); // 清空内容
	            switch (type)
	            {
	                case 'search' :
	                    $.each(result, function(i, a)
	                    {
	                        switch (a.type)
	                        {
	                            case 'questions':
	                                if (a.detail.best_answer > 0)
	                                {
	                                    var active = 'active';
	                                }
	                                else
	                                {
	                                    var active = ''
	                                }

	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListQuestions).render(
	                                {
	                                    'url': a.url,
	                                    'active': active,
	                                    'content': a.name,
	                                    'discuss_count': a.detail.answer_count
	                                }));
	                                break;
									
								case 'articles':
	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListArticles).render(
	                                {
	                                    'url': a.url,
	                                    'content': a.name,
	                                    'comments': a.detail.comments
	                                }));
	                                break;
									
	                            case 'topics':
	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListTopics).render(
	                                {
	                                    'url': a.url,
	                                    'name': a.name,
	                                    'discuss_count': a.detail.discuss_count,
	                                    'topic_id': a.detail.topic_id
	                                }));
	                                break;
	                            
	                            case 'users':
	                                if (a.detail.signature == '')
	                                {
	                                    var signature = _t('暂无介绍');
	                                }
	                                else
	                                {
	                                    var signature = a.detail.signature;
	                                }
	                                
	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListUsers).render(
	                                {
	                                    'url': a.url,
	                                    'img': a.detail.avatar_file,
	                                    'name': a.name,
	                                    'intro': signature
	                                }));
	                                break;
	                        }
	                    });
	                break;

	                case 'publish' :
	                case 'topic_question' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.questionDropdownList).render(
	                        {
	                            'url': a.url,
	                            'name': a.name
	                        }));
	                    });
	                	break;

	                case 'topic' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.editTopicDorpdownList).render(
	                        {
	                            'name': a['name']
	                        }));
	                    });
	                	break;

	                case 'redirect' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.questionRedirectList).render(
	                        {
	                            'url': "'" + G_BASE_URL + "/question/ajax/redirect/', 'item_id=" + $(selector).attr('data-id') + "&target_id=" + a['search_id'] + "'",
	                            'name': a['name']
	                        }));
	                    });
	                	break;

	                case 'inbox' :
	                case 'invite' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.inviteDropdownList).render(
	                        {
	                            'uid': a.uid,
	                            'name': a.name,
	                            'img': a.detail.avatar_file
	                        }));
	                    });
	                	break;
	            }
	            if (type == 'publish')
	            {
	                $(selector).parent().find('.aw-publish-suggest-question, .aw-publish-suggest-question .aw-dropdown-list').show();
	            }
	            else
	            {
	                $(selector).parent().find('.aw-dropdown, .aw-dropdown-list').show().children().show();
	                $(selector).parent().find('.title').hide();
	                // 关键词高亮
	                $(selector).parent().find('.aw-dropdown-list li.question a').highText(data, 'b', 'active');
	            }
	        }else
	        {
	            $(selector).parent().find('.aw-dropdown').show().end().find('.title').html(_t('没有找到相关结果')).show();
	            $(selector).parent().find('.aw-dropdown-list, .aw-publish-suggest-question').hide();
	        }
	    }, 'json');

	}
}

AWS.Editor = 
{
	// 设置编辑器预览状态
    set_editor_preview: function ()
    {
        if ($.cookie('data_editor_preview') != 'false')
        {
            $('.markItUpButton13').removeClass('cur');
        }
        else
        {
        	$('.markItUpButton13').addClass('cur');
			if ($('.markItUpPreviewFrame').css('display') == 'none')
			{
				$('.markItUpPreviewFrame').fadeIn();
			}
            else
			{
				$('.markItUpPreviewFrame').fadeOut();	
			}
        }
    },
    
	// 向编辑器插入多媒体内容(图片,视频,超链接)
	add_multimedia: function (type)
	{
		switch (type){
			case 'img' : 
				var title = 'imgsAlt',
					url = 'imgsUrl',
					textFeildValue = '\n![' + ($('#addTxtForms :input[name="' + title + '"]').val()) + '](' + $('#addTxtForms :input[name="' + url + '"]').val() + ')';
			break;

			case 'video' :
				var title = 'videoTitle',
					url = 'videoUrl',
					textFeildValue = '\n!![' + ($('#addTxtForms :input[name="' + title + '"]').val()) + '](' + $('#addTxtForms :input[name="' + url + '"]').val() + ')';
			break;

			case 'link' :
				var title = 'linkText',
					url = 'linkUrl',
					textFeildValue = '[' + ($('#addTxtForms :input[name="' + title + '"]').val()) + '](' + $('#addTxtForms :input[name="' + url + '"]').val() + ')';;
			break;	
		}
		if($('#addTxtForms :input[name="' + url + '"]').val() == '')
    	{
    		return false;
    	}
    	if ($('.aw-mod-replay-box .advanced_editor').length)
        {
        	var selector = $('.aw-mod-replay-box .advanced_editor');
        }
        else if ($('.aw-mod-publish .advanced_editor').length)
        {
        	var selector = $('.aw-mod-publish .advanced_editor');
        }
        else if ($('.aw-mod-topic-edit-box .advanced_editor').length)
        {
        	var selector = $('.aw-mod-topic-edit-box .advanced_editor');
        }
        selector.insertAtCaret(textFeildValue);
	},

    // 向编辑器插入文本
    insert_into_codemirror: function (fn)
    {
        var flag = false, pos = {}, cursor = 0, textarea = $('.advanced_editor');
        if (advanced_editor != null)
        {
            pos = advanced_editor.getCursor('start');

            advanced_editor.toTextArea();

            for (var i = 0, iLen = pos.line; i < iLen; i++)
            {
                cursor += advanced_editor.lineInfo(i).text.length + 1;
            }

            cursor += pos.ch;

            if (textarea.createTextRange)
            {
                // quick fix to make it work on Opera 9.5
                /*if ($.browser.opera && $.browser.version >= 9.5 && advanced_editor.getSelection().length == 0)
                {
                    return false;
                }*/
                range = textarea.createTextRange();
                range.collapse(true);
                range.moveStart('character', cursor);
                range.moveEnd('character', advanced_editor.getSelection().length);
                range.select();
            }
            else if (textarea.setSelectionRange)
            {
                textarea.setSelectionRange(cursor, cursor + advanced_editor.getSelection().length);
            }
            textarea.focus();
            advanced_editor = null;
            flag = true;
        }

        fn(arguments[1]);

        if (flag)
        {
            advanced_editor = CodeMirror.fromTextArea(textarea,
            {
                mode: 'markdown',
                lineNumbers: true,
                theme: "default",
                extraKeys:
                {
                    "Enter": "newlineAndIndentContinueMarkdownList"
                }
            });

            if (typeof arguments[1] != 'undefined' && (arguments[1].offset || arguments[1].addline))
            {
                pos.line += (arguments[1].addline ? arguments[1].addline : 0);
                pos.ch += (arguments[1].offset ? arguments[1].offset : 0);
            }

            advanced_editor.setCursor(pos);
        }
    },

    // 编辑器插入附件
    insert_attach: function(selector, attach_id, attach_tag)
	{
	    selector.parents('form').find('textarea').insertAtCaret("\n[" + attach_tag + "]" + attach_id + "[/" + attach_tag + "]\n");
	}
}

AWS.Message = 
{
	// 检测通知
	check_notifications: function()
	{
		// 检测登录状态
	    if (G_USER_ID == 0)
	    {
	    	clearInterval(AWS.G.notification_timer);
	        return false;
	    }

	    $.get(G_BASE_URL + '/home/ajax/notifications/', function (result)
	    {
	        $('#inbox_unread').html(Number(result.rsm.inbox_num));

	        var last_unread_notification = G_UNREAD_NOTIFICATION;

	        G_UNREAD_NOTIFICATION = Number(result.rsm.notifications_num);

	        if (G_UNREAD_NOTIFICATION > 0)
	        {
	            if (G_UNREAD_NOTIFICATION != last_unread_notification)
	            {
	            	// 加载消息列表
	                AWS.Message.load_notification_list();

	                // 给导航label添加未读消息数量
	                $('#notifications_unread').html(G_UNREAD_NOTIFICATION);
	            }

	            document.title = '(' + (Number(result.rsm.notifications_num) + Number(result.rsm.inbox_num)) + ') ' + document_title;

	            $('#notifications_unread').show();
	        }
	        else
	        {
	            if ($('#header_notification_list').length)
	            {
	                $("#header_notification_list").html('<p style="padding: 0" align="center">' + _t('没有未读通知') + '</p>');
	            }

	            if ($("#index_notification").length)
	            {
	                $("#index_notification").fadeOut();
	            }

	            document.title = document_title;

	            $('#notifications_unread').hide();
	        }

	        // 私信
	        if (Number(result.rsm.inbox_num) > 0)
	        {
	            $('#inbox_unread').show();
	        }
	        else
	        {
	            $('#inbox_unread').hide();
	        }

	    }, 'json');
	},

	// 阅读通知
	read_notification: function(selector, notification_id , reload)
	{
	    if (notification_id)
	    {
	        selector.remove();

	        var url = G_BASE_URL + '/notifications/ajax/read_notification/notification_id-' + notification_id;
	    }
	    else
	    {
	        if ($("#index_notification").length)
	        {
	            $("#index_notification").fadeOut();
	        }

	        var url = G_BASE_URL + '/notifications/ajax/read_notification/';
	    }

	    $.get(url, function (result)
	    {
	        AWS.Message.check_notifications();

	        if (reload)
	        {
	            window.location.reload();
	        }
	    });
	},

	// 重新加载通知列表
	load_notification_list: function()
	{
	    if ($("#index_notification").length)
	    {
	    	// 给首页通知box内label添加未读消息数量
	        $("#index_notification").fadeIn().find('[name=notification_unread_num]').html(G_UNREAD_NOTIFICATION);

	        $('#index_notification ul#notification_list').html('<p align="center" style="padding: 15px 0"><img src="' + G_STATIC_URL + '/common/loading_b.gif"/></p>');

	        $.get(G_BASE_URL + '/notifications/ajax/list/flag-0__page-0', function (result)
	        {
	            $('#index_notification ul#notification_list').html(result);

	            AWS.Message.notification_show(5);
	        });
	    }

	    if ($("#header_notification_list").length)
	    {
	        $.get(G_BASE_URL + '/notifications/ajax/list/flag-0__limit-5__template-header_list', function (result)
	        {
	            if (result.length)
	            {
	                $("#header_notification_list").html(result);
	            }
	            else
	            {
	                $("#header_notification_list").html('<p style="padding: 0" align="center">' + _t('没有未读通知') + '</p>');
	            }
	        });
	    }
	},

	// 控制通知数量
	notification_show: function(length)
	{
	    if ($('#index_notification').length > 0)
	    {
	    	if ($('#index_notification ul#notification_list li').length == 0)
	        {
	            $('#index_notification').fadeOut();
	        }
	        else
	        {
	        	$('#index_notification ul#notification_list li').each(function (i, e)
		        {
		            if (i < length)
		            {
		                $(e).show();
		            }
		            else
		            {
		                $(e).hide();
		            }
		        });
	        }
	    }
	}
}

AWS.Init = 
{
	// 初始化问题评论框
	init_comment_box: function(selector)
	{
	    $(document).on('click', selector, function ()
	    {
	        $(this).parents('.aw-question-detail').find('.aw-invite-box, .aw-question-related-box').hide();
	        if (typeof COMMENT_UNFOLD != 'undefined')
	        {
	            if (COMMENT_UNFOLD == 'all' && $(this).attr('data-comment-count') == 0 && $(this).attr('data-first-click') == 'hide')
	            {
	                $(this).removeAttr('data-first-click');
	                return false;
	            }
	        }

	        if (!$(this).attr('data-type') || !$(this).attr('data-id'))
	        {
	            return true;
	        }

	        var comment_box_id = '#aw-comment-box-' + $(this).attr('data-type') + '-' + 　$(this).attr('data-id');
			
	        if ($(comment_box_id).length)
	        {
	            if ($(comment_box_id).css('display') == 'none')
	            {
	                $(comment_box_id).fadeIn();
	            }
	            else
	            {
	                $(comment_box_id).fadeOut();
	            }
	        }
	        else
	        {
	            // 动态插入commentBox
	            switch ($(this).attr('data-type'))
	            {
		            case 'question':
		                var comment_form_action = G_BASE_URL + '/question/ajax/save_question_comment/question_id-' + $(this).attr('data-id');
		                var comment_data_url = G_BASE_URL + '/question/ajax/get_question_comments/question_id-' + $(this).attr('data-id');
		                break;
		
		            case 'answer':
		                var comment_form_action = G_BASE_URL + '/question/ajax/save_answer_comment/answer_id-' + $(this).attr('data-id');
		                var comment_data_url = G_BASE_URL + '/question/ajax/get_answer_comments/answer_id-' + $(this).attr('data-id');
		                break;
	            }
	            if (G_USER_ID)
	            {
	                $(this).parents('.aw-item').append(Hogan.compile(AW_TEMPLATE.commentBox).render(
	                {
	                    'comment_form_id': comment_box_id.replace('#', ''),
	                    'comment_form_action': comment_form_action
	                }));
					
	                $(comment_box_id).find('.aw-comment-txt').bind(
	                {
	                    focus: function ()
	                    {
	                        $(comment_box_id).find('.aw-comment-box-btn').show();
	                    },

	                    blur: function ()
	                    {
	                        if ($(this).val() == '')
	                        {
	                            $(comment_box_id).find('.aw-comment-box-btn').hide();
	                        }
	                    }
	                });

	                $(comment_box_id).find('.close-comment-box').click(function ()
	                {
	                    $(comment_box_id).fadeOut();
	                    $(comment_box_id).find('.aw-comment-txt').css('height', $(this).css('line-height'));
	                });
	            }
	            else
	            {
	                $(this).parent().parent().append(Hogan.compile(AW_TEMPLATE.commentBoxClose).render(
	                {
	                    'comment_form_id': comment_box_id.replace('#', ''),
	                    'comment_form_action': comment_form_action
	                }));
	            }

	            // 判断是否有评论数据
	            $.get(comment_data_url, function (result)
	            {
	                if ($.trim(result) == '')
	                {
	                    result = '<div align="center" class="aw-padding10">' + _t('暂无评论') + '</div>';
	                }
					
	                $(comment_box_id).find('.aw-comment-list').html(result);
	            });

	            // 给三角形定位
	            $(comment_box_id).find('.i-dropdown-triangle').css('left', $(this).position().left - 10);
	            // textarae自动增高
	            $(comment_box_id).find('.aw-comment-txt').autosize();
	        }

	        AWS.at_user_lists($(this).parents('.aw-item').find('.aw-comment-txt'));
	    });
	},

	// 初始化文章评论框
	init_article_comment_box: function(selector)
	{
	    $(document).on('click', selector, function ()
	    {
	    	var _editor_box = $(this).parents('.aw-item').find('.aw-article-comment-box');
	        if (_editor_box.length)
	        {
	            if (_editor_box.css('display') == 'block')
	            {
	               _editor_box.fadeOut();
	            }
	            else
	            {
	                _editor_box.fadeIn();
	            }
	        }
	        else
	        {
	            $(this).parents('.aw-item-content').append(Hogan.compile(AW_TEMPLATE.articleCommentBox).render(
	            {
	                'at_uid' : $(this).attr('data-id'),
	                'article_id' : $('.aw-mod-article-replay-box input[name="article_id"]').val()
	            }));
	        }
	    });
	},

	// 初始化投票框
	init_vote_bar: function(data)
	{
	    switch (data.flag)
	    {
		    case 1:
		        var up_class = 'active';
		        var down_class = '';
		        break;
		
		    case -1:
		        var up_class = '';
		        var down_class = 'active';
		        break;
		
		    case 0:
		        var up_class = '';
		        var down_class = '';
		        break;
	    }

	    $(data.element).parent().prepend(Hogan.compile(AW_TEMPLATE.voteBar).render(
	    {
	        'agree_count': data.agree_count,
	        'up_class': up_class,
	        'down_class': down_class,
	        'user_name': data.user_name,
	        'answer_id': data.answer_id
	    }));

	    $(data.element).detach();
	},

	// 初始化话题编辑box
	init_topic_edit_box: function(selector) //selector -> .aw-edit-topic
	{
	    $(selector).click(function ()
	    {
	        var _topic_editor = $(this).parents('.aw-topic-editor'),
	        	data_id = _topic_editor.attr('data-id'),
	        	data_type = _topic_editor.attr('data-type');

	        if (!_topic_editor.find('.aw-topic-name').children('span').hasClass('aw-close'))
	        {
	            _topic_editor.find('.aw-topic-name').children('span').append('<button type="button" class="close aw-close">×</button>');
	        }
	        else
	        {
	            _topic_editor.find('.aw-close').show();
	        }

	        // 判断插入编辑box
	        if (_topic_editor.find('.aw-edit-topic-box').length == 0)
	        {
	            _topic_editor.append(AW_TEMPLATE.editTopicBox);

	            // 给编辑box添加按钮添加事件
	            _topic_editor.find('.submit-edit').click(function ()
	            {
	                if (_topic_editor.find('#aw_edit_topic_title').val() != '')
	                {
	                    switch (data_type)
	                    {
	                    	case 'publish':
		                        _topic_editor.prepend('<a href="javascript:;" class="aw-topic-name"><span>' + _topic_editor.find('#aw_edit_topic_title').val() + '<button class="close aw-close" type="button" onclick="$(this).parents(\'.aw-topic-name\').remove();">×</button></span><input type="hidden" value="' + _topic_editor.find('#aw_edit_topic_title').val() + '" name="topics[]" /></a>').hide().fadeIn();

		                        _topic_editor.find('#aw_edit_topic_title').val('');
	                        break;

	                    	case 'question':
		                        $.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=question&item_id=' + data_id + '&topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function (result)
		                        {
		                            if (result.errno != 1)
		                            {
		                                AWS.alert(result.err);

		                                return false;
		                            }

		                            _topic_editor.prepend('<a href="' + G_BASE_URL + '/topic/' + result.rsm.topic_id + '" class="aw-topic-name" data-id="' + result.rsm.topic_id + '"><span>' + _topic_editor.find('#aw_edit_topic_title').val() + '<button class="close aw-close">×</button></span></a>').hide().fadeIn();

		                            _topic_editor.find('#aw_edit_topic_title').val('');
		                        }, 'json');
	                        break;

	                    	case 'article':
		                        $.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=article&item_id=' + data_id + '&topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function (result)
		                        {
		                            if (result.errno != 1)
		                            {
		                                AWS.alert(result.err);

		                                return false;
		                            }

		                            _topic_editor.prepend('<a href="' + G_BASE_URL + '/topic/' + result.rsm.topic_id + '" class="aw-topic-name" data-id="' + result.rsm.topic_id + '"><span>' + _topic_editor.find('#aw_edit_topic_title').val() + '<button class="close aw-close">×</button></span></a>').hide().fadeIn();

		                            _topic_editor.find('#aw_edit_topic_title').val('');
		                        }, 'json');
	                        break;


	                    	case 'topic':
		                        $.post(G_BASE_URL + '/topic/ajax/save_related_topic/topic_id-' + data_id, 'topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function (result)
		                        {
		                            if (result.errno != 1)
		                            {
		                                AWS.alert(result.err);

		                                return false;
		                            }

		                            _topic_editor.prepend('<a href="' + G_BASE_URL + '/favorite/tag-' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()) + '" class="aw-topic-name"><span>' + _topic_editor.find('#aw_edit_topic_title').val() + '<button class="close aw-close">x</button></span></a>').hide().fadeIn();

		                            _topic_editor.find('#aw_edit_topic_title').val('');
		                        }, 'json');
	                        break;

	                    	case 'favorite':
		                        $.post(G_BASE_URL + '/favorite/ajax/update_favorite_tag/', 'answer_id=' + data_id + '&tags=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function (result)
		                        {
		                            if (result.errno != 1)
		                            {
		                                AWS.alert(result.err);

		                                return false;
		                            }

		                            _topic_editor.prepend('<a href="' + G_BASE_URL + '/favorite/tag-' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()) + '" class="aw-topic-name"><span>' + _topic_editor.find('#aw_edit_topic_title').val() + '<button class="close aw-close">x</button></span></a>').hide().fadeIn();
		                            
		                            _topic_editor.find('#aw_edit_topic_title').val('');
		                        }, 'json');
	                        break;
	                    }
	                }
	            });

	            // 给编辑box取消按钮添加事件
	            _topic_editor.find('.close-edit').click(function ()
	            {
	                _topic_editor.find('.aw-edit-topic-box, .aw-close').hide();
	                _topic_editor.find('.aw-edit-topic').show();
	            });

	       		AWS.Dropdown.bind_dropdown_list($(this).parents('.aw-topic-editor').find('#aw_edit_topic_title'),'topic');
	        }

    		//话题编辑下拉菜单mouseover click事件
	        $(document).on('mouseover', '.aw-edit-topic-box .aw-dropdown-list li', function ()
		    {
		        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
		    });
		    
		    $(document).on('click', '.aw-edit-topic-box .aw-dropdown-list li', function ()
		    {
		        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
		        $(this).parents('.aw-edit-topic-box').find('.submit-edit').click();
		        $(this).parents('.aw-edit-topic-box').find('.aw-dropdown').hide();
		    });

		    //话题删除按钮
		    $(document).on('click', '.aw-topic-name .aw-close',  function()
		    {
		        var data_type = $(this).parents('.aw-topic-editor').attr('data-type'),
		            data_id = $(this).parents('.aw-topic-editor').attr('data-id');
		        switch (data_type)
		        {
		            case 'question':
		                $.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', 'type=question&item_id=' + data_id + '&topic_id=' + $(this).parents('.aw-topic-name').attr('data-id'),function(){
		                    $('#aw-ajax-box').empty();
		                });
		                break;

		            case 'topic':
		                $.get(G_BASE_URL + '/topic/ajax/remove_related_topic/related_id-' + $(this).parents('.aw-topic-name').attr('data-id') + '__topic_id-' + data_id);
		                break;

		            case 'favorite':
		                $.post(G_BASE_URL + '/favorite/ajax/remove_favorite_tag/', 'answer_id=' + data_id + '&tags=' + $(this).parents('.aw-topic-name').text().substring(0, $(this).parents('.aw-topic-name').text().length - 1));
		                break;

		            case 'article':
		                $.post(G_BASE_URL + '/topic/ajax/remove_topic_relation/', 'type=article&item_id=' + data_id + '&topic_id=' + $(this).parents('.aw-topic-name').attr('data-id'),function(){
		                    $('#aw-ajax-box').empty();
		                });
		                break;

		        }

		        $(this).parents('.aw-topic-name').remove();

		        return false;
		    });

	        $(this).parents('.aw-topic-editor').find('.aw-edit-topic-box').fadeIn();

	        // 是否允许创建新话题
	        if (!G_CAN_CREATE_TOPIC)
	        {
	            $(this).parents('.aw-topic-editor').find('.submit-edit').hide();
	        }

	        $(this).hide();
	    });
	},

	// 初始化图片上传控件
	init_img_uploader: function(upload_url, upload_name, upload_element, upload_status_elememt, perview_element)
	{
	    return new AjaxUpload(upload_element,
	    {
	        action: upload_url,
	        name: upload_name,
	        responseType: 'json',
	        onSubmit: function (file, ext)
	        {
	            if (!new RegExp('(png|jpe|jpg|jpeg|gif)$', 'i').test(ext))
	            {
	                alert(_t('上传失败: 只支持 jpg、gif、png 格式的图片文件'));

	                return false;
	            }

	            this.disable();

	            if (upload_status_elememt)
	            {
	                upload_status_elememt.show();
	            }
	        },
	        onComplete: function (file, response)
	        {
	            this.enable();

	            if (upload_status_elememt)
	            {
	                upload_status_elememt.hide();
	            }

	            if (response.errno == -1)
	            {
	                alert(response.err);
	            }
	            else if (typeof (response.rsm != 'undefined'))
	            {
	                if (typeof (perview_element.attr('src')) != 'undefined' && response.rsm.preview)
	                {
	                    perview_element.attr('src', response.rsm.preview + '?' + Math.floor(Math.random() * 10000));
	                }
	                else if (response.rsm.preview)
	                {
	                    perview_element.css('background-image', 'url(' + response.rsm.preview + '?' + Math.floor(Math.random() * 10000) + ')');
	                }
	            }
	        }
	    });
	},

	// 初始化头像上传控件
	init_avatar_uploader: function(upload_element, upload_status_elememt, avatar_element)
	{
	    return AWS.Init.init_img_uploader(G_BASE_URL + '/account/ajax/avatar_upload/', 'user_avatar', upload_element, upload_status_elememt, avatar_element);
	},

	// 初始化文件上传控件
	init_fileuploader: function(selector, action_url)
	{
	    if (!document.getElementById(selector))
	    {
	        return false;
	    }
	    
	    if (G_UPLOAD_ENABLE == 'Y')
	    {
	    	$('.aw-upload-tips').show();
	    }

	    return new _ajax_uploader.FileUploader(
	    {
	        element: document.getElementById(selector),
	        action: action_url,
	        debug: false
	    });
	}
}

function _t(string, replace)
{
    if (typeof (aws_lang) != 'undefined')
    {
        if (typeof (aws_lang[string]) != 'undefined')
        {
            string = aws_lang[string];
        }
    }

    if (replace)
    {
        string = string.replace('%s', replace);
    }

    return string;
};

// jQuery扩展
(function ($)
{
	$.fn.extend(
    {
    	insertAtCaret: function (textFeildValue)
	    {
	        var textObj = $(this).get(0);
	        if (document.all && textObj.createTextRange && textObj.caretPos)
	        {
	            var caretPos = textObj.caretPos;
	            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == '' ?
	                textFeildValue + '' : textFeildValue;
	        }
	        else if (textObj.setSelectionRange)
	        {
	            var rangeStart = textObj.selectionStart,
	            	rangeEnd = textObj.selectionEnd,
	            	tempStr1 = textObj.value.substring(0, rangeStart),
	            	tempStr2 = textObj.value.substring(rangeEnd);
	            textObj.value = tempStr1 + textFeildValue + tempStr2;
	            textObj.focus();
	            var len = textFeildValue.length;
	            textObj.setSelectionRange(rangeStart + len, rangeStart + len);
	            textObj.blur();
	        }
	        else
	        {
	            textObj.value += textFeildValue;
	        }
	    },

	    highText: function (searchWords, htmlTag, tagClass)
	    {
	        return this.each(function ()
	        {
	            $(this).html(function high(replaced, search, htmlTag, tagClass)
	            {
	                var pattarn = search.replace(/\b(\w+)\b/g, "($1)").replace(/\s+/g, "|");

	                return replaced.replace(new RegExp(pattarn, "ig"), function (keyword)
	                {
	                    return $("<" + htmlTag + " class=" + tagClass + ">" + keyword + "</" + htmlTag + ">").outerHTML();
	                });
	            }($(this).text(), searchWords, htmlTag, tagClass));
	        });
	    },

	    outerHTML: function (s)
	    {
	        return (s) ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
	    }
    });

	$.extend(
	{
		// 滚动到指定位置
		scrollTo : function (type, duration, options)
		{
			if (typeof type == 'object')
			{
				var type = $(type).offset().top
			}
			
			$('html, body').animate({
				scrollTop: type
			}, {
				duration: duration,
				queue: options.queue
			});
		}
	})

})(jQuery);