var AW_TEMPLATE = {
	'userCard':
			'<div id="aw-card-tips" class="aw-card-tips">'+
				'<div class="aw-mod">'+
					'<div class="aw-mod-head">'+
						'<a class="aw-user-img">'+
							'<img src="img/user-img.jpg" alt="" title=""/>'+
						'</a>'+
						'<p class="title">'+
							'<a href="#">{{username}}</a>'+
							'<i class="aw-icon i-v"></i>'+
						'</p>'+
						'<p class="aw-user-center-follow-meta">'+
							'<span>威望: <em class="aw-text-color-green">{{weiwang}}</em></span>'+
							'<span>赞同: <em class="aw-text-color-oragne">{{zantong}}</em></span>'+
						'</p>'+
					'</div>'+
					'<div class="aw-mod-body">'+
						'<p title="anwsion产品经理">{{title}}</p>'+
					'</div>'+
					'<div class="aw-mod-footer">'+
						'<span class="pull-right">'+
							'<a>私信</a>&nbsp;&nbsp;&nbsp;&nbsp;<a>问Ta</a>'+
						'</span>'+
						'<a class="btn btn-mini">取消关注</a>'+
					'</div>'+
				'</div>'+
			'</div>',
	
	'topicCard' : 
			'<div id="aw-card-tips" class="aw-card-tips">'+
				'<div class="aw-mod">'+
					'<div class="aw-mod-head">'+
						'<a class="aw-user-img">'+
							'<img src="img/user-img.jpg" alt="" title=""/>'+
						'</a>'+
						'<p class="title">'+
							'<a href="#">{{topicName}}</a>'+
						'</p>'+
						'<p>'+
							'{{topicTitle}}'+
						'</p>'+
					'</div>'+
					'<div class="aw-mod-footer">'+
						'<span class="pull-right">'+
							'问题数{{questionNum}} • 关注者{{followNum}}'+
						'</span>'+
						'<a class="btn btn-mini btn-success">关注</a>'+
					'</div>'+
				'</div>'+
			'</div>',

	'alertBox' : 
			'<div class="modal hide fade alert-box aw-tips-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">提示信息</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<p><i class="aw-icon i-warmming"></i>{{message}}</p>'+
				'</div>'+
			'</div>',

	'imagevideoBox' : 
			'<div id="aw-image-box" class="modal alert-box aw-image-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">{{title}}</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form id="addTxtForms">'+
						'<p>图片连接地址</p>'+
						'<input type="text" value="http://" name="{{url}}" />'+
						'<p>图片说明:</p>'+
						'<input type="text" name="{{tips}}"/>'+
					'</form>'+
				'</div>'+
				'<div class="modal-footer">'+
					'<a data-dismiss="modal" aria-hidden="true" class="closeBox">取消</a>'+
					'<button class="btn btn-large btn-success">确定</button>'+
				'</div>'+
			'</div>'+
			'<div class="modal-backdrop fade in"></div>',

	'editCommentBox' : 
				'<div class="modal hide fade alert-box aw-edit-comment-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">编辑回复</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form>'+
						'<textarea>{{message}}</textarea>'+
					'</form>'+
					'<div class="aw-file-upload-box">'+
						'<a class="aw-btn b-up-load"></a>'+
						'<input type="file" class="hide"/>'+
						'<!-- 上传附件模块 -->'+
						'<div class="aw-file-uploader">'+
							'<dl>'+
								'<dt>'+
									'<img src="img/user-img-max.jpg" width="90" height="90" alt="" title=""/>'+
								'</dt>'+
								'<dd>'+
									'<p class="aw-file-uploader-name">aw-btn-sprite.png</p>'+
									'<p class="aw-text-color-999">11.1kB</p>'+
									'<p><a>删除</a>&nbsp;&nbsp;&nbsp;<a>插入</a></p>'+
								'</dd>'+
							'</dl>'+
							'<dl>'+
								'<dt>'+
									'<img src="img/user-img-max.jpg" width="90" height="90" alt="" title=""/>'+
								'</dt>'+
								'<dd>'+
									'<p class="aw-file-uploader-name">aw-btn-sprite.png</p>'+
									'<p class="aw-text-color-999">11.1kB</p>'+
									'<p><a>删除</a>&nbsp;&nbsp;&nbsp;<a>插入</a></p>'+
								'</dd>'+
							'</dl>'+
							'<dl>'+
								'<dt>'+
									'<img src="img/user-img-max.jpg" width="90" height="90" alt="" title=""/>'+
								'</dt>'+
								'<dd>'+
									'<p class="aw-file-uploader-name">aw-btn-sprite.png</p>'+
									'<p class="aw-text-color-999">11.1kB</p>'+
									'<p><a>删除</a>&nbsp;&nbsp;&nbsp;<a>插入</a></p>'+
								'</dd>'+
							'</dl>'+
						'</div>'+
						'<!-- end 上传附件模块 -->'+
					'</div>'+
				'</div>'+
				'<div class="modal-footer">'+
					'<span><input id="aw-do-delete" type="checkbox" value="1" name="do_delete"><label for="aw-do-delete">删除回复</label></span>'+
					'<button class="btn btn-large btn-success">确定</button>'+
				'</div>'+
			'</div>',

	'favoriteBox' : 
			'<div class="modal hide fade alert-box aw-favorite-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">收藏</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form>'+
						'<p>添加话题标签: <input type="text" placeholder="搜索问题"/></p>'+
						'<p>常用标签:</p>'+
					'</form>'+
				'</div>'+
				'<div class="modal-footer">'+
					'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
					'<button class="btn btn-large btn-success">发起</button>'+
				'</div>'+
			'</div>',

	'questionRedirect' : 
			'<div  class="modal hide fade alert-box aw-question-redirect-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">问题重定向至</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form>'+
						'<p>将问题重定向至</p>'+
						'<p><input type="text" placeholder="搜索问题"/></p>'+
						'<p><a class="btn btn-mini pull-right">放弃操作</a></p>'+
					'</form>'+
				'</div>'+
			'</div>',

	'publishBox' : 
			'<div class="modal hide fade alert-box aw-publish-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">发起问题</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form>'+
						'<textarea></textarea>'+
						'<p><span class="aw-publish-box-supplement"><i class="aw-icon i-edit"></i>补充说明 »</span></p>'+
						'<textarea class="aw-publish-box-supplement-content hide"></textarea>'+
						'<div class="aw-publish-title-dropdown">'+
							'<p class="dropdown-toggle" data-toggle="dropdown">'+
								'<span id="aw-topic-tags-select">选择分类</span>'+
								'<a><i class="aw-icon i-triangle-down"></i></a>'+
							'</p>'+
						'</div>'+
					'</form>'+
				'</div>'+
				'<div class="modal-footer">'+
					'<span class="pull-left">分享到</span>'+
					'<a class="pull-left"><i class="aw-icon i-share-sina"></i></a>'+
					'<a class="pull-left"><i class="aw-icon i-share-weibo"></i></a>'+
					'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
					'<button class="btn btn-large btn-success">发起</button>'+
				'</div>'+
			'</div>',

	'messageBox' : 
			'<div id="aw-message-box" class="modal hide fade aw-alert-box aw-message-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">新私信</h3>'+
				'</div>'+
				'<div class="modal-body">'+
					'<form>'+
						'<input type="text" value="Izekiel"/>'+
						'<textarea></textarea>'+
					'</form>'+
				'</div>'+
				'<div class="modal-footer">'+
					'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
					'<button class="btn btn-large btn-success">发起</button>'+
				'</div>'+
			'</div>',

	'shareBoxOutside' :
			'<div class="modal hide fade alert-box aw-share-box aw-share-box-outside" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">分享答案</h3>'+
					'<!-- tab切换 -->'+
					'<p class="aw-share-box-tabs">'+
						'<a class="active"><i class="aw-icon i-share-user"></i>站外</a>'+
						'<a><i class="aw-icon i-message"></i>私信</a>'+
					'</p>'+
					'<!-- end tab切换 -->'+
				'</div>'+
					'<!-- tab切换内容 -->'+
						'<div class="aw-share-box-tabs-content">'+
							'<!-- 站外 -->'+
							'<div class="aw-item">'+
								'<div class="modal-body">'+
									'<ul>'+
									'{{#items}}'+
										'<li><a title="分享到{{title}}"><i class="bds {{className}}"></i>{{name}}</a></li>'+
									'{{/items}}'+
									'</ul>'+
								'</div>'+
							'</div>'+
							'<!-- end 站外 -->'+
							'<div class="aw-item hide">'+
								'<div class="modal-body">'+
									'<form>'+
										'<input type="text" placeholder="搜索用户"/>'+
										'<textarea>{{textareaContent}}</textarea>'+
									'</form>'+
								'</div>'+
								'<div class="modal-footer">'+
									'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
									'<button class="btn btn-large btn-success">发送</button>'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<!-- end tab切换内容 -->'+
			'</div>',

	'shareBoxMessage' : 
			'<div class="modal hide fade alert-box aw-share-box aw-share-box-message" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-header">'+
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
					'<h3 id="myModalLabel">分享答案</h3>'+
					'<!-- tab切换 -->'+
					'<p class="aw-share-box-tabs">'+
						'<a><i class="aw-icon i-share-user"></i>站外</a>'+
						'<a class="active"><i class="aw-icon i-message"></i>私信</a>'+
					'</p>'+
					'<!-- end tab切换 -->'+
				'</div>'+
					'<!-- tab切换内容 -->'+
						'<div class="aw-share-box-tabs-content">'+
							'<!-- 站外 -->'+
							'<div class="aw-item hide">'+
								'<div class="modal-body">'+
									'<ul>'+
									'{{#items}}'+
										'<li><a title="分享到{{title}}"><i class="bds {{className}}"></i>{{name}}</a></li>'+
									'{{/items}}'+
									'</ul>'+
								'</div>'+
							'</div>'+
							'<!-- end 站外 -->'+
							'<div class="aw-item">'+
								'<div class="modal-body">'+
									'<form>'+
										'<input type="text" placeholder="搜索用户"/>'+
										'<textarea>{{textareaContent}}</textarea>'+
									'</form>'+
								'</div>'+
								'<div class="modal-footer">'+
									'<a data-dismiss="modal" aria-hidden="true">取消</a>'+
									'<button class="btn btn-large btn-success">发送</button>'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<!-- end tab切换内容 -->'+
			'</div>',
	'shareList' : [ //分享外网icon列表
		{'className':'bds-qzone','name':'QQ空间','title':'QQ空间'},
		{'className':'bds-tsina','name':'新浪微博','title':'新浪微博'},
		{'className':'bds-tqq','name':'腾讯微博','title':'腾讯微博'},
		{'className':'bds-baidu-zone','name':'百度空间','title':'百度空间'},
		{'className':'bds-t163','name':'网易微博','title':'网易微博'},
		{'className':'bds-tqf','name':'朋友网','title':'朋友网'},
		{'className':'bds-kaixin','name':'开心网','title':'开心网'},
		{'className':'bds-renren','name':'人人网','title':'人人网'},
		{'className':'bds-douban','name':'豆瓣网','title':'人人网'},
		{'className':'bds-taobao','name':'淘宝网','title':'淘宝网'},
		{'className':'bds-fbook','name':'Facebook','title':'Facebook'},
		{'className':'bds-twi','name':'Twitter','title':'Twitter'},
		{'className':'bds-ms','name':'Myspace','title':'Myspace'},
		{'className':'bds-deli','name':'Delicious','title':'Delicious'},
		{'className':'bds-linkedin','name':'linkedin','title':'linkedin'}
	],

	'editTopicBox' : 
			'<div class="aw-edit-topic-box">'+
				'<input type="text" placeholder="创建或搜索添加新话题...">'+
				'<a class="btn btn-mini btn-success">添加 »</a>'+
				'<a class="btn btn-mini close-edit">取消</a>'+
			'</div>',

	'commentBox' : 
			'<div class="aw-comment-box">'+
				'<div class="aw-comment-list"></div>'+
				'<form>'+
					'<div class="aw-comment-box-main">'+
						'<textarea class="aw-comment-txt" placeholder="评论一下..."></textarea>'+
						'<div class="aw-comment-box-btn">'+
							'<span class="pull-right">'+
								'<a class="btn btn-mini btn-success">评论</a>'+
								'<a class="btn btn-mini close-comment-box">取消</a>'+
							'</span>'+
						'</div>'+
					'</div>'+
				'</form>'+
				'<i class="aw-icon i-comment-triangle"></i>'+
			'</div>',
	'commentList' : 
			'<ul>'+
				'{{#items}}'+
					'<li>'+
						'<a class="aw-user-name"><img src="img/user-img.jpg" alt="" title=""/></a>'+
						'<p>'+
							'<span class="pull-right"><a>删除</a> <a>回复</a></span>'+
							'<a class="aw-user-name">im3e</a>'+
						'</p>'+
						'<p>{{commentTxt}}</p>'+
					'</li>'+
				'{{/items}}'+
			'</ul>',

	'dropdownList' : 
		'<div aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu aw-dropdown-menu">'+
			'<span><i class="aw-icon i-dropdown-triangle active"></i></span>'+
			'<ul>'+
			'{{#items}}'+
				'<li><a>{{name}}</a></li>'+
			'{{/items}}'+
			'</ul>'+
		'</div>',

	'searchDropdownList' : 
		'<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu aw-search-dropdown-list">'+
			'<li><p>输入关键字进行搜索</p></li>'+
			'<li><a>123</a></li>'+
			'<li class="aw-no-border-bottom"><p><a class="pull-right btn btn-mini btn-success">发起问题</a></p></li>'+
		'<i class="aw-icon i-dropdown-triangle active"></i>'+
		'</ul>',

	'voteBar' : 
		'<div class="aw-vote-bar pull-left">'+
			'<a class="aw-border-radius-5 {{up_active}}">'+
				'<i data-original-title="赞同回复" class="aw-icon i-up active" data-toggle="tooltip" title="" data-placement="right"></i>'+
			'</a>'+
			'<em class="aw-border-radius-5 aw-vote-bar-count aw-hide-txt active">{{count}}</em>'+
			'<a class="aw-border-radius-5 {{down_active}}">'+
				'<i data-original-title="对回复持反对意见" class="aw-icon i-down" data-toggle="tooltip" title="" data-placement="right"></i>'+
			'</a>'+
		'</div>'


}