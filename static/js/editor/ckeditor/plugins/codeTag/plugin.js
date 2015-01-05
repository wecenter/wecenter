CKEDITOR.plugins.add( 'codeTag', {
  icons: 'code',
  init: function( editor ) {
    editor.addCommand( 'wrapCode', {
      exec: function( editor ) {
        editor.insertHtml( '<pre>&nbsp;&nbsp;' + editor.getSelection().getSelectedText() + '</pre>' );
      }
    });
    editor.ui.addButton( 'Code', {
      label: 'Wrap code',
      command: 'wrapCode',
      toolbar: 'blocks'
    });
  }
});