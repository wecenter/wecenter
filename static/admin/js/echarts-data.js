$(function ()
{
	var echart = new Echarts('#main', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=new_question_by_month,new_answer_by_month&start_date=2012-01&end_date=2012-12');
    var echart2 = new Echarts('#main2', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=user_register_by_month,new_answer_by_month,new_question_by_month,new_topic_by_month&start_date=2013-01&end_date=2013-12');
    var echart3 = new Echarts('#main3', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=user_register_by_month,new_answer_by_month,new_question_by_month,new_topic_by_month&start_date=2014-01&end_date=2014-12');

    // 左侧菜单收缩重新渲染图表
    $('.aw-header .mod-head-btn').click(function ()
	{
		echart.render();
		echart2.render();
		echart3.render();
	});

});

function Echarts(element, type, url, options)
{
	this.element = element;
	this.type = type;
	this.url = url;
	this.options = {
		animation:false,
        addDataAnimation:false,
        grid:{
           x:45,
           y:25,
           x2:15,
           y2:35,
           backgroundColor:'#fff',
           borderColor: '#fff'
        },
	    calculable: true,
	    yAxis: [
            {
                type: 'value',
                splitLine : {
                    show:false,
                },

                axisLine : {    
                    show: false
                },

                splitLine : {
                    show:true,
                    lineStyle: {
                        color: 'rgba(0,0,0,0.1)',
                        type: 'dashed',
                        width: 1
                    }
                }
            }
        ],
	    lineColor : ['#4195fd', '#50dcb3', '#fde457', '#fd575f']

	};

	this.options = $.extend(this.options, options);

	this.initChart(element, type, url, options);
}

Echarts.prototype = 
{
	// 图表初始化
	initChart : function (element, type, url, options)
	{
		this.getData(url);
	},

	// 初始化x轴数据
	initxAxis : function (data)
	{
		var options = {
			xAxis : [
	            {
	                type: 'category',
	                splitLine : {
	                    show:false,
	                },

	                axisLine : {    
	                    show: false
	                },
	                axisTick : {    
	                    show:false,
	                },
	                data: data
	            }
	        ]};
	    return options;
	},

	// 获取数据
	getData : function (url)
	{
		var _this = this;
		if (url)
		{
			$.get(url,  {'async' : false}, function (result)
			{
				if (result)
				{
					var xAxis = _this.initxAxis(result.labels),
						legend = _this.getUrlParam('tag'),
						series = _this.initSeries(_this.type, result.data);
					_this.options = $.extend(_this.options, xAxis, series, legend);
					_this.render(_this.element);
				}
			}, 'json');
		}
	},

	// 初始化线的模板数据
	initSeries : function(type, data)
	{
		if (data)
		{
			var arr = [];

			for (var i = 0; i < data.length; i++)
			{
				var j = {
					name: this.legend_data[i],
	                type: 'line',
	                symbol: 'none',
	                itemStyle: {
	                     normal: {
	                          lineStyle:{
	                            width: 4,
	                            color: this.options.lineColor[i],
	                          }
	                        },
	                },
	                data: data[i]
				}
				arr.push(j);
			}

			var options = {
				series : arr
			}

			return options;

		}
	},

	// 渲染
	render : function()
	{
		var chart = echarts.init($(this.element)[0]);
		
		chart.setOption(this.options);
	},

	// 获取url参数
	getUrlParam : function (name)
	{
		var star_flag = this.url.search(name) + name.length + 1, end_flag = this.url.search(/&/),
			param = this.url.substring(star_flag, end_flag).split(',');
			arr = [];

		for (var i = 0; i < param.length; i++)
		{
			switch(param[i]) 
			{
				case 'new_question_by_month' :
					arr.push('新问题');
				break;

				case 'new_answer_by_month' :
					arr.push('新回复');
				break;

				case 'user_register_by_month' :
					arr.push('新用户注册');
				break;

				case 'new_topic_by_month' :
					arr.push('新话题');
				break;
			}
		}

		var options = {
			legend : {
				data: arr
			}
		}

		this.legend_data = arr;

		return options;
	}

}