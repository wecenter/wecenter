/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	
	// %REMOVE_START%
	// The configuration options below are needed when running CKEditor from source files.
	config.plugins = 'wecenterimage,removeformat,font,blockquote,maximize,basicstyles,button,toolbar,enterkey,entities,floatingspace,wysiwygarea,image,fakeobjects,link,list,undo,bbcode,sourcearea,codeTag';
	config.skin = 'bootstrapck';

	config.height = '250';

	config.autoUpdateElement = true;

	// %REMOVE_END%

	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for a single toolbar row.
	// config.toolbarGroups = [
	// 	{ name: 'basicstyles', groups: [ 'bold', 'Italic' ] },
	// 	{ name: 'paragraph',   groups: [ 'list', 'blocks', 'align', 'bidi', 'code' ] },
	// 	{ name: 'links', groups: [ 'links', 'insert' ] },
	// 	{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
	// 	{ name: 'forms' },
	// 	{ name: 'styles' },
	// 	{ name: 'colors' },
	// 	{ name: 'tools' },
	// 	{ name: 'others' },
	// 	{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] }
	// ];

	config.toolbar = 'Full';

	config.toolbar_Full = [
		 ['WecenterImage','FontSize','Cleanup','Bold','Italic',
		 // 'Strike','Underline',
		 'NumberedList','BulletedList', 'Blockquote', 'Code', 'Image', 'Link', 'Maximize', 'RemoveFormat', 'Source']

	]

	config.fontSize_sizes = '14/14px;16/16px;';

	// The default plugins included in the basic setup define some buttons that
	// are not needed in a basic editor. They are removed here.
	config.removeButtons = 'Cut,Copy,Paste,Undo,Redo,Anchor,Subscript,Superscript';

	// Dialog windows are also simplified.
	config.removeDialogTabs = 'link:advanced';

};
