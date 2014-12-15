CKEDITOR.plugins.add('wecenterlink', {
    init: function (editor) {
        var pluginName = 'WecenterLink';
        CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/wecenterlink.js');
        editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
        editor.ui.addButton(pluginName,
        {
            label: editor.lang.link.menu,
            command: pluginName
        });
    }
});