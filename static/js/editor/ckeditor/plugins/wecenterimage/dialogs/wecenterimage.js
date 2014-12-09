(function () {
    function WecenterImageDialog(editor) {
 
        return {
            title: '插入图片',
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
                    label: '名字',
                    title: '名字',
                    elements:
                    [
                        {
                            id: 'text',
                            type: 'text',
                            class: 'form-control',
                            label: '名字',
                            'default': '',
                            required: true,
                            validate: CKEDITOR.dialog.validate.notEmpty('名字不能为空'),
                            commit: function () {
                                var text = 'Hello '+this.getValue();
                                alert(text);
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
 
    CKEDITOR.dialog.add('WecenterImage', function (editor) {
        return WecenterImageDialog(editor);
    });
})();