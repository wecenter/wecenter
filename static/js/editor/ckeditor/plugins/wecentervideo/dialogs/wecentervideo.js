(function () {
    function WecenterVideoDialog(editor) {
 
        return {
            title: '插入视频',
            minWidth: 400,
            minHeight: 110,
            buttons: [
                CKEDITOR.dialog.okButton,
                CKEDITOR.dialog.cancelButton
            ],
            contents:
            [
                {
                    elements:
                    [
                        {
                            id: 'text',
                            type: 'text',
                            required: true,
                            commit: function () {
                                if (this.getValue()) {
                                    editor.insertHtml('<br /><video title="' + this.getValue() + '">' + this.getValue()  + '</video>');
                                    //editor.insertText( '!![视频名称](' + this.getValue() + ')' );
                                }
                            }
                        },
                        {
                            type: 'html',
                            html : '<p style="font-size:14px;color:#999;">我们目前支持: 优酷、酷六、土豆、56、新浪播客、乐视、Youtube 与 SWF 文件!</p>'
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
 
    CKEDITOR.dialog.add('WecenterVideo', function (editor) {
        return WecenterVideoDialog(editor);
    });
})();