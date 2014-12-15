CKEDITOR.plugins.add('wecenterimage', {
    init: function (editor) {
        var pluginName = 'WecenterImage';
        CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/wecenterimage.js');
        editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
        editor.ui.addButton(pluginName,
        {
            label: editor.lang.common.image,
            command: pluginName
        });
    }
});