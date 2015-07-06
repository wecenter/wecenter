/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	config.toolbar = 'Full';

	config.toolbar_Full = [
		//'FontSize','RemoveFormat'
		 ['Cleanup','Bold','Italic','NumberedList','BulletedList', 'Blockquote', 'pbckcode', 'WecenterImage', 'WecenterAttach', 'WecenterLink', 'WecenterVideo', 'Maximize']
	]

	config.extraPlugins = 'autolink,pbckcode,bbcode,sourcearea,wecenterattach,wecenterimage,wecenterlink,wecentervideo,blockquote,font';

	config.resize_enabled = false;

	config.language = 'zh-cn';

	config.skin = 'bootstrapck';

	config.height = 250;

	// 过滤粘贴内容
	config.forcePasteAsPlainText = true;

	config.magicline_color = '#ccc';

	config.magicline_everywhere = true;

	config.fontSize_sizes = '16px;18px';

	// The default plugins included in the basic setup define some buttons that
	// are not needed in a basic editor. They are removed here.
	config.removeButtons = 'Cut,Copy,Paste,Undo,Redo,Anchor,Underline,Strike,Subscript,Superscript';

	// Dialog windows are also simplified.
	config.removeDialogTabs = 'link:advanced';

	config.removePlugins = 'enterkey,elementspath,tabletools,contextmenu';

};
