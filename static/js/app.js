var document_title;

$(document).ready(function () {
	
	document_title = document.title;
	
	check_notifications();
	
	setInterval('check_notifications()', G_NOTIFICATION_INTERVAL);
	
	$('a[rel=lightbox]').fancybox({			
		openEffect  : 'none',
		closeEffect : 'none',

		prevEffect : 'none',
		nextEffect : 'none',

		closeBtn  : false,

		helpers : {
			buttons	: {
				position : 'bottom'
			}
		},

		afterLoad : function() {
			this.title = '第 ' + (this.index + 1) + ' 张, 共 ' + this.group.length + ' 张' + (this.title ? ' - ' + this.title : '');
		}
	});
	
	if (typeof(markdownSettings) != 'undefined')
	{
		$('.advanced_editor').markItUp(markdownSettings);
	}
});