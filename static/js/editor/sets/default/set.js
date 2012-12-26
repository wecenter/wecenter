// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
myMarkdownSettings = {
    nameSpace:          'markdown', // Useful to prevent multi-instances CSS conflict
    previewParser:  function(content){
        var html = Markdown(content);
        return html;
    },
    previewInElement : '#markItUpPreviewFrames',
    onShiftEnter:       {keepDefault:false, openWith:'\n\n'},
    markupSet: [
        {name:'Bold', key:"B", openWith:'**', closeWith:'**'},
        {name:'Italic', key : "I", openWith : '*', closeWith : '*'},
        {separator:'---------------' },        
        {name:'Quotes', openWith:'> '},
        {name:'Code Block / Code', openWith:'{{{\n', closeWith:'\n}}}'},
        {separator:'---------------' },
        {name:'Bulleted List', openWith:'- ' },
        {name:'Numeric List', openWith:function(markItUp) {
            return markItUp.line+'. ';
        }},
        {separator:'---------------' },
        {name:'Picture', key:"P", replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")'},
        {separator:'---------------'},
        {name:'First Level Heading', key : "1", openWith:'\n## '},
        {name:'Second Level Heading', key : "2", openWith : '\n### ' },
        {separator:'---------------'},
        {name : _t('预览模式'), openWith:function(){
            $('#markItUpPreviewFrame').toggle();
        }}, 
        {name : _t('清空'), openWith:function(){
            $('#question_detail').val('');
            $('#markItUpPreviewFrames').html('');
        }}
    ]
}

// mIu nameSpace to avoid conflict.
miu = {
    markdownTitle: function(markItUp, char) {
        heading = '';
        n = $.trim(markItUp.selection||markItUp.placeHolder).length;
        for(i = 0; i < n; i++) {
            heading += char;
        }
        return '\n'+heading+'\n';
    }
}
