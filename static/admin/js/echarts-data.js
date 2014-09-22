$(function () {
    // 填入对应日期
    var dateArr = getDate();

    /**
     * 禁止日历框输入
     */

    $('.date-end,.date-start').bind("keydown",function(){
        
        return false
    })
    
    $('.aw-content-wrap').find('.date-start').val(dateArr[1]);
    $('.aw-content-wrap').find('.date-end').val(dateArr[0]);

    //图表数据接入
    var echart = new Echarts('#main', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=new_user,user_valid&start_date=' + dateArr[3] + '&end_date=' + dateArr[2]);
    var echart2 = new Echarts('#main2', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=new_question,new_answer,new_topic,new_favorite_item,new_question_redirect&start_date=' + dateArr[3] + '&end_date=' + dateArr[2]);
    var echart3 = new Echarts('#main3', 'line', G_BASE_URL + '/admin/ajax/statistic/?tag=new_answer_vote,new_answer_thanks,new_question_thanks&start_date=' + dateArr[3] + '&end_date=' + dateArr[2]);


    // 左侧菜单收缩重新渲染图表
    $('.aw-header .mod-head-btn').click(function ()
    {       
        echart.render();
        echart2.render();
        echart3.render();
    });


    window.addEventListener("orientationchange", function ()
    {

        echart.render();
        echart2.render();
        echart3.render();
    }, false);

    var oEchart = $('.echart-date');

    for (var i = 0, j = oEchart.length; i < j; i++) 
    {
        (function (i) {
            oEchart[i].onclick = function (ev) {
                var ev = ev || window.event;
                var target = ev.targe || ev.srcElement;

                if (ev.target.nodeName.toLocaleLowerCase() == "a") {

                    var start_date = ($(this).find('.date-start').val()).substring(0, 7),
                        end_date = ($(this).find('.date-end').val()).substring(0, 7),
                        aEchart = '';
                    i > 0 ? aEchart = eval('echart' + (i + 1)) : aEchart = eval(aEchart = 'echart');

                    var url = aEchart.url.substring(0, aEchart.url.search(/&/)) + '&start_date=' + start_date + '&end_date=' + end_date;


                    aEchart.initChart(url);
                }
            };
        })(i);
    }
});

function getDate() {
    var date = new Date(),
        Year = 0,
        beforeYear = 0,
        Month = 0,
        beforeSixMonth = 0,
        Day = 0,
        stratDate = "",
        endDate = "",
        stratDateM = "",
        endDateM = "",
        arr = [];

    Year = date.getFullYear();
    Month = date.getMonth() + 1;
    Day = date.getDate();

    if (Month > 6) {
        beforeSixMonth = Month - 6;
        beforeYear = date.getFullYear();
    } else {
        beforeYear = (date.getFullYear()) - 1;
        beforeSixMonth = 6 + Month;
    }


    stratDate += Year + "-";
    endDate += beforeYear + "-";

    if (Month >= 10) {
        stratDate += Month + "-";
        endDate += "0" + beforeSixMonth + "-";
    } else {
        stratDate += "0" + Month + "-";
        endDate += beforeSixMonth + "-";
    }

    if (Day >= 10) {
        stratDate += Day;
        endDate += Day;
    } else {
        stratDate += "0" + Day;
        endDate += "0" + Day;
    }

    stratDateM = stratDate.substring(0, 7);
    endDateM = endDate.substring(0, 7);

    arr.push(stratDate, endDate, stratDateM, endDateM);
    return arr;
}


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
                    type: 'line',
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