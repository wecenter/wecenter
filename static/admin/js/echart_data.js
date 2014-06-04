$(function () {
    oData();
});


/*$(window).resize(function () {

    oData();

});*/

$('.aw-header .mod-head-btn').click(function ()
{

    if ($('#aw-side').is(':hidden'))
    {
        $('#aw-side').show(0, function () {
            $('.aw-content-wrap').css("marginLeft", "235px");
            oData();
        });
    }
    else{
        $('#aw-side').hide(0, function ()
        {
            $('.aw-content-wrap').css("marginLeft", "0");
            oData();
        });
    }
});


function type(o){
    return (o=== null)?'null':(typeof 0);
}

function oData() {
    var myChart = echarts.init(document.getElementById('main'));
    var myChart2 = echarts.init(document.getElementById('main2'));
    var myChart3 = echarts.init(document.getElementById('main3'));
    var myChart4 = echarts.init(document.getElementById('main4'));
    var myChart5 = echarts.init(document.getElementById('main5'));
    myChart.setOption({

        animation:false,
        addDataAnimation:false,

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            padding: 5,
            itemGap: 10,
            data: ['蒸发量', '降水量']
        },
        grid:{
           x:45,
           y:40,
           x2:15,
           y2:30,
           backgroundColor:'#fff'
        },
        toolbox: {
            show: true,
            feature: {
                restore: {show: true},
                magicType: {show: true, type: ['line', 'bar']}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                data: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']
            }
        ],
        yAxis: [
            {
                type: 'value',
                splitArea: {show: true}
            }
        ],
        series: [
            {
                name: '蒸发量',
                type: 'line',
                data: [2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
            },
            {
                name: '降水量',
                type: 'line',
                data: [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
            }
        ]
    });
    myChart2.setOption({
        animation:false,
        addDataAnimation:false,
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            padding: 5,
            itemGap: 10,
            data: ['蒸发量', '降水量']
        },
        grid:{
            x:45,
            y:40,
            x2:15,
            y2:30,
            backgroundColor:'#fff'
        },
        toolbox: {
            show: true,
            feature: {
                restore: {show: true},
                magicType: {show: true, type: ['line', 'bar']}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                data: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']
            }
        ],
        yAxis: [
            {
                type: 'value',
                splitArea: {show: true}
            }
        ],
        series: [
            {
                name: '蒸发量',
                type: 'line',
                data: [2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
            },
            {
                name: '降水量',
                type: 'line',
                data: [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
            }
        ]
    });
    myChart3.setOption({
        animation:false,
        addDataAnimation:false,
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            padding: 5,
            itemGap: 10,
            data: ['蒸发量', '降水量']
        },
        grid:{
            x:45,
            y:40,
            x2:15,
            y2:30,
            backgroundColor:'#fff'
        },
        toolbox: {
            show: true,
            feature: {
                restore: {show: true},
                magicType: {show: true, type: ['line', 'bar']}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                data: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']
            }
        ],
        yAxis: [
            {
                type: 'value',
                splitArea: {show: true}
            }
        ],
        series: [
            {
                name: '蒸发量',
                type: 'line',
                data: [2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
            },
            {
                name: '降水量',
                type: 'line',
                data: [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
            }
        ]
    });
    myChart4.setOption({
        animation:false,
        addDataAnimation:false,
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            padding: 5,
            itemGap: 10,
            data: ['蒸发量', '降水量']
        },
        grid:{
            x:45,
            y:40,
            x2:15,
            y2:30,
            backgroundColor:'#fff'
        },
        toolbox: {
            show: true,
            feature: {
                restore: {show: true},
                magicType: {show: true, type: ['line', 'bar']}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                data: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']
            }
        ],
        yAxis: [
            {
                type: 'value',
                splitArea: {show: true}
            }
        ],
        series: [
            {
                name: '蒸发量',
                type: 'line',
                data: [2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
            },
            {
                name: '降水量',
                type: 'line',
                data: [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
            }
        ]
    });
    myChart5.setOption({
        animation:false,
        addDataAnimation:false,
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            padding: 5,
            itemGap: 10,
            data: ['蒸发量', '降水量']
        },
        grid:{
            x:45,
            y:40,
            x2:15,
            y2:30,
            backgroundColor:'#fff'
        },
        toolbox: {
            show: true,
            feature: {
                restore: {show: true},
                magicType: {show: true, type: ['line', 'bar']}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                data: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']
            }
        ],
        yAxis: [
            {
                type: 'value',
                splitArea: {show: true}
            }
        ],
        series: [
            {
                name: '蒸发量',
                type: 'line',
                data: [2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
            },
            {
                name: '降水量',
                type: 'line',
                data: [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
            }
        ]
    });
}