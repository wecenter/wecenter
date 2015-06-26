(function () {
    function WecenterLinkDialog(editor) {
 
        return {
            title: '插入超链接',
            minWidth: 470,
            minHeight: 110,
            buttons: [
                CKEDITOR.dialog.okButton,
                CKEDITOR.dialog.cancelButton
            ],
            contents:
            [
                {
                    id: 'info',
                    elements:
                    [
                        {
                            type: 'html',
                            html : '<p style="margin-bottom:10px;font-size:14px;">链接标题</p>'
                        },
                        {
                            type: 'text',
                            className: 'link_name',
                            required: false
                        },
                        {
                            type: 'html',
                            html : '<p style="margin-top:15px;font-size:14px;">链接地址</p>'
                        },
                        {
                            type: 'text',
                            className: 'link_text',
                            required: true,
                            commit: function () {
                                var value = $('.cke_dialog_body .link_text input').val(), 
                                    name = $('.cke_dialog_body .link_name input').val();
                                if (value)
                                {
                                    var element = editor.document.createElement( 'a' );
                                    if (name)
                                    {
                                        element.setHtml(name);
                                    }
                                    else
                                    {
                                        element.setHtml(value);
                                    }

                                    if (value.match(/https?:/))
                                    {
                                        element.setAttribute( 'href', value );
                                    }
                                    else
                                    {
                                        element.setAttribute( 'href', 'http://' + value );
                                    }
                                    
                                    editor.insertElement( element );
                                }
                                else 
                                {
                                    return false;
                                }
                            }
                        }
                    ]
                }
            ],
            onLoad: function () {
                //alert('onLoad');
            },
            onShow: function () {
                //alert('onShow');
            },
            onHide: function () {
                //alert('onHide');
            },
            onOk: function () {
                this.commitContent();
            },
            onCancel: function () {
                //alert('onCancel');
            },
            resizable: false
        };
    }
 
    CKEDITOR.dialog.add('WecenterLink', function (editor) {
        return WecenterLinkDialog(editor);
    });
})();