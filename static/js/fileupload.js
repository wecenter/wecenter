/*!
 * FileUpload v1.0
 * Copyright 2011-2014 Wecenter, Inc.
 * Date: 2014-06-02
 */
function FileUpload (element, container, url, options)
{
	this.element = element;
	this.container = container;
	this.url = url;
	this.template = '<li>'+
		    			'<div class="img"></div>'+
						'<div class="content">'+
							'<p class="title"></p>'+
							'<p class="size"></p>'+
							'<p class="meta"></p>'+
						'</div>'+
		    		'</li>';

    this.options = {
		'multiple' : true,
		'deleteBtn' : true,
		'insertBtn' : true,
		'insertTextarea' : '.advanced_editor'
	},

	this.options = $.extend(this.options, options);

	this.init(element, container);
}

FileUpload.prototype = 
{
	// 初始化上传器
	init : function (element, container)
	{
		var form = this.createForm(),
			input = this.createInput();

		$(element).prepend($(form).append(input));

		$(container).append('<ul class="upload-list"></ul>');
	},

	// 创建表单
	createForm : function ()
	{
		var form = this.toElement('<form method="post" enctype="multipart/form-data"><input type="submit" class="submit" /></form>');

		$(form).attr({
			'id' : 'upload-form',
			'action' : this.url,
			'target' : 'ajaxUpload'
		});

		return form;
	},

	// 创建input
	createInput : function ()
	{
		var _this = this, input = this.toElement('<input type="file" />');

		$(input).attr({
			'class' : 'file-input',
			'name' : 'qqfile',
			'multiple' : this.options.multiple ? 'multiple' : false
		});

		$(input).change(function()
		{
			_this.addFileList(this);
		});

		return input;
	},

	// 创建iframe
	createIframe : function ()
	{
		var iframe = this.toElement('<iframe></iframe>');
    	$(iframe).attr({
    		'class': 'hide',
    		'id': 'upload-iframe',
    		'name': 'ajaxUpload'
    	});
    	return iframe;
	},

	// 添加文件列表
	addFileList : function (input)
	{
		var files = $(input)[0].files;
		if (files)
		{
			for (i = 0; i < files.length; i++)
			{
				this.li = this.toElement(this.template);
				this.file = files[i];
				$(this.container).find('.upload-list').append(this.li);
				this.upload(files[i], this.li);
			}
		}
		else
		{
			this.li = this.toElement(this.template);
			$(this.container).find('.upload-list').append(this.li);
			this.upload('', this.li);
		}
		
	},

	// 上传功能
	upload : function (file, li)
	{
		var _this = this;

		if (file)
		{
			var xhr = new XMLHttpRequest(), status = false;

	        xhr.upload.onprogress = function(event)
	        {
	        	if (event.lengthComputable)
	        	{
	                var percent = Math.round(event.loaded * 100 / event.total);
	            }

                $(li).find('.title').html(file.name);

                $(li).find('.size').html(percent + '%');
	        };

	        xhr.onreadystatechange = function()
	        {      
	            _this.oncomplete(xhr, li, file);
	        };

	        var url = this.url + '&qqfile=' + file.name;

	        xhr.open("POST", url);

	        xhr.send(file);
		}
        else
        {
        	//低版本ie上传
			var iframe = this.createIframe();

        	if (iframe.addEventListener)
        	{
		        iframe.addEventListener('load', function()
	        	{
	        		_this.getIframeContentJSON(iframe);
	        	}, false);
		    } else if (iframe.attachEvent)
		    {
		        iframe.attachEvent('onload', function()
	        	{
	        		_this.getIframeContentJSON(iframe);
	        	});
	    	}

    		$('body').append(iframe);

        	$('#upload-form .submit').click();
        }
	},

	// 从iframe获取json内容
	getIframeContentJSON : function (iframe)
	{
		var doc = iframe.contentDocument ? iframe.contentDocument: iframe.contentWindow.document,
			response, filename;
		try
		{
            response = eval("(" + doc.body.innerHTML + ")");

        	this.render(this.li, response);

           	filename = this.getName($('#upload-form .file-input')[0].value);

           	$(this.li).find('.title').html(filename);

           	$('#upload-iframe').detach();
        }
        catch(err)
        {
            response = {};
        }
	},

	// ajax完成callback
	oncomplete : function (xhr, li, file)
	{
		var _this = this, response, filesize = this.getFIleSize(file);

		if (xhr.readyState == 4 && xhr.status == 200)
		{
            try
            {
                response = eval("(" + xhr.responseText + ")");

                this.render(li, response, filesize);
            }
            catch(err)
            {
                response = {};
            }
		}
	},

	// 渲染缩略列表
	render : function (element, json, filesize)
	{
		if (json)
		{
			if (!json.error)
			{
				switch (json.class_name)
				{
					case 'txt':
						$(element).find('.img').addClass('file').html('<i class="fa fa-file-o"></i>');
					break;

					default :
						$(element).find('.img').css(
						{
			                'background': 'url("' + json.thumb + '")'
			            });
			        break;
				}

				if (filesize)
				{
					$(element).find('.size').html(filesize);
				}

				if (this.options.deleteBtn && json.delete_url)
				{
					var btn = this.createDeleteBtn(json.delete_url);

					$(element).find('.meta').append(btn);
				}

				if (this.options.insertBtn && json.delete_url && !json.class_name)
				{
					var btn = this.createInsertBtn(json.attach_id);

					$(element).find('.meta').append(btn);
				}
			}
			else
			{
				$(element).addClass('error').find('.img').addClass('error').html('<i class="fa fa-times"></i>');
			}
		}
	},

	toElement : function (html)
	{
		var div = document.createElement('div');
		div.innerHTML = html;
        var element = div.firstChild;
        div.removeChild(element);
        return element;
	},

	// 获取文件名
	getName : function (filename)
	{
        return filename.replace(/.*(\/|\\)/, "");
    },

    // 获取文件大小
    getFIleSize : function (file)
    {
    	var filesize;
    	if (file.size > 1024 * 1024)
        {
            filesize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
        }
        else
        {
            filesize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
        }
        return filesize;
    },

    // 创建插入按钮
    createInsertBtn : function (attach_id)
    {
    	var btn = this.toElement('<a class="insert-file">插入</a>'), _this = this;

    	$(btn).click(function()
		{
			$(_this.options.insertTextarea).insertAtCaret("\n[attach]" + attach_id + "[/attach]\n");
		});

		return btn;
    },

    // 创建删除按钮
   	createDeleteBtn : function (url)
   	{
   		var btn = this.toElement('<a class="delete-file">删除</a>');

   		$(btn).click(function()
		{
			if (confirm('确认删除?'))
			{
				var _this = this;
				$.get(url, function (result)
				{
					if (result.errno == "-1")
					{
						AWS.alert(result.err);
					}
					else
					{
						$(_this).parents('li').detach();
					}
				}, 'json');
			}
		});

		return btn;
   	},

   	// 初始化文件列表
    setFileList : function (json)
    {
    	var template = '<li>'+
		    			'<div class="img" style="background:url(' + json.thumb + ')"></div>'+
						'<div class="content">'+
							'<p class="title">' + json.file_name + '</p>'+
							'<p class="size"></p>'+
							'<p class="meta"></p>'+
						'</div>'+
		    		'</li>', 
		    insertBtn = this.createInsertBtn(json.attach_id),
		    deleteBtn = this.createDeleteBtn(json.delete_link);

		template = this.toElement(template), _this = this;

		$(template).find('.meta').append(deleteBtn);
		$(template).find('.meta').append(insertBtn);
    	$(this.container).find('.upload-list').append(template);
    }
}

