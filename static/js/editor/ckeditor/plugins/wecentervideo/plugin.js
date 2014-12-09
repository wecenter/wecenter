CKEDITOR.plugins.add('wecentervideo', {
    init: function (editor) {
        var pluginName = 'WecenterVideo';
        CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/wecentervideo.js');
        editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
        editor.ui.addButton(pluginName,
        {
            label: '插入视频',
            command: pluginName
        });
    }
});