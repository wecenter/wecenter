CKEDITOR.plugins.add( 'codeTag', {
  icons: 'code',
  init: function( editor ) {
    editor.addCommand( 'wrapCode', {
      exec: function( editor ) {
        editor.insertHtml( '<code>&nbsp;&nbsp;' + editor.getSelection().getSelectedText() + '</code>' );
      }
    });
    editor.ui.addButton( 'Code', {
      label: 'Wrap code',
      command: 'wrapCode',
      toolbar: 'blocks'
    });
  }
});