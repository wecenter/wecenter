function Echarts(element, type, url, options) {

    this.element = element;
    this.type = type;
    this.url = url;

    this.options = {
        animation: false,
        addDataAnimation: false,
        grid: {
            x: 45,
            y: 65,
            x2: 15,
            y2: 35,
            backgroundColor: '#fff',
            borderColor: '#fff'
        },
        calculable: false,
        yAxis: [{
            type: 'value',
            splitLine: {
                show: false,
            },

            axisLine: {
                show: false
            },

            splitLine: {
                show: true,
                lineStyle: {
                    color: 'rgba(0,0,0,0.1)',
                    type: 'dashed',
                    width: 1
                }
            }
        }],

    };

    this.options = $.extend(this.options, options);

    this.initChart(url);
}

Echarts.prototype = {
    // 图表初始化

    initChart: function (url) {
        this.getData(url);
    },

    // 初始化x轴数据
    initxAxis: function (data) {
        var options = {
            xAxis: [{
                type: 'category',
                splitLine: {
                    show: false,
                },

                axisLine: {
                    show: false
                },
                axisTick: {
                    show: false,
                },
                data: data
            }]
        };
        return options;
    },

    // 获取数据
    getData: function (url) {
        var _this = this;
        if (url) {
            $.get(url, {
                'async': false
            }, function (result) {
                if (result) {
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
    initSeries: function (type, data) {
        if (data) {
            var arr = [];

            for (var i = 0; i < data.length; i++) {
                var j = {
                    name: this.legend_data[i],
                    type: type,
                    symbol: 'none',
                    itemStyle: {
                        normal: {
                            lineStyle: {
                                width: 4,
                            }
                        },
                    },
                    data: data[i]
                }
                arr.push(j);
            }

            var options = {
                series: arr
            }

            return options;

        }
    },

    // 渲染
    render: function () {
        var chart = echarts.init($(this.element)[0]);

        chart.setOption(this.options);
    },

    // 获取url参数
    getUrlParam: function (name) {
        var star_flag = this.url.search(name) + name.length + 1,
            end_flag = this.url.search(/&/),
            param = this.url.substring(star_flag, end_flag).split(',');
        arr = [];

        for (var i = 0; i < param.length; i++) {
            switch (param[i]) {
            case 'new_answer':
                arr.push('新增答案');
                break;

            case 'new_question':
                arr.push('新增问题');
                break;

            case 'new_user':
                arr.push('新注册用户');
                break;

            case 'user_valid':
                arr.push('新激活用户');
                break;

            case 'new_topic':
                arr.push('新增话题');
                break;

            case 'new_answer_vote':
                arr.push('新增回复投票');
                break;

            case 'new_answer_thanks':
                arr.push('新增回复感谢');
                break;

            case 'new_favorite_item':
                arr.push('新增收藏');
                break;

            case 'new_question_thanks':
                arr.push('新增问题感谢');
                break;

            case 'new_question_redirect':
                arr.push('新增问题重定向');
                break;
            }
        }

        var options = {
            legend: {
                data: arr,
                padding: 8,
                x: 'right',
            }
        }

        this.legend_data = arr;

        return options;
    }

}