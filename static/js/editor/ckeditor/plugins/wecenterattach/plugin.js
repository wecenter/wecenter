CKEDITOR.plugins.add('wecenterattach', {
    init: function (editor) {
        var pluginName = 'WecenterAttach';
        //CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/wecentervideo.js');
        var attach_length = $('.cke_button__wecenterattach').length + 1;
        editor.ui.addButton(pluginName,
        {
            label: '插入附件',
            command: pluginName,
            className : 'cke_attach_' + attach_length
        });
        editor.on('instanceReady', function(editorEvent)
        {

            $('.cke_attach_' + attach_length).click(function ()
            {

                if(!$(this).parents('.aw-editor-box').find('.upload-list li').length)
                {
                    AWS.alert('当前没有上传附件!');
                }
                else
                {
                    $('.aw-editor-dropdown').detach();
                    var top = $(this).offset().top + 35,
                        left = $(this).offset().left,
                        flag = false,
                        template = '<div aria-labelledby="dropdownMenu" role="menu" class="aw-dropdown aw-editor-dropdown" style="top:' + top + 'px;left:' + left + 'px;width:140px;">'+
                                        '<ul class="aw-dropdown-list">';

                    $.each($(this).parents('.aw-editor-box').find('.upload-list li'), function ()
                    {
                        if ($(this).find('.img').attr('style'))
                        {
                            template += '<li><a data-id="' + $(this).find('.hidden-input').val() + '"><img width="24" class="aw-border-radius-5" src="' + $(this).find('.img').attr('data-img')  + '" />' + $(this).find('.title').html() + '</a></li>';
                            flag = true;
                        }
                    });

                    template += '</ul></div>';

                    if(flag)
                    {
                        $('#aw-ajax-box').append(template);

                        $('.aw-editor-dropdown ul li a').click(function ()
                        {
                            editor.insertText("\n[attach]" + $(this).attr('data-id') + "[/attach]\n");

                            $(this).parents('.aw-editor-dropdown').detach();
                        });

                        $('.aw-editor-dropdown').show();
                    }
                    
                }
                    
            });

            $(document).click(function (e)
            {
                if (!$(e.target).hasClass('cke_button__wecenterattach_icon'))
                {
                    $('.aw-editor-dropdown').detach();
                }
                
            });

        });
    }
});