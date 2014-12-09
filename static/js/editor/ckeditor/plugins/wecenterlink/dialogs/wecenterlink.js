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
                            id: 'text',
                            type: 'text',
                            required: true,
                            validate: CKEDITOR.dialog.validate.notEmpty('链接地址不能为空'),
                            commit: function () {
                                var element = editor.document.createElement( 'a' );
                                element.setAttribute( 'href', this.getValue() );
                                element.setHtml(this.getValue());
                                console.log(element);
                                editor.insertElement( element );
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