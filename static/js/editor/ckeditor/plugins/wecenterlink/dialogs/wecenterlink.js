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
                            commit: function () {
                                if (this.getValue()) {
                                    var element = editor.document.createElement( 'a' );
                                    element.setAttribute( 'href', this.getValue() );
                                    element.setHtml(this.getValue());
                                    editor.insertElement( element );
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