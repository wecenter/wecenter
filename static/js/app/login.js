$(document).ready(function () { 
    $('#login_form input').keydown(function (e) {
        if (e.keyCode == 13)
        {
            $('#login_submit').click();
        }
    });
    
    login_slide('#aw-bg-loading li');
});

function login_slide(selecter)
{
    $(selecter).eq(0).css({
        'opacity': 1,
        'z-index': 2
    }).siblings().css({
        'opacity': 0,
        'z-index': 1
    });

    //轮播
    setInterval(function ()
    {
        var num;
        //获取当前轮播图的index
        $(selecter).each(function () {
            if ($(this).css('opacity') == 1)
            {
                num = $(this).index();
            }
        });

        //隐藏当前那张轮播图
        $(selecter).eq(num).animate({
            opacity: '0'
        }, 500);

        //判断如果当前是最后一张的话跳会第一张
        if (num + 1 >= $(selecter).length)
        {
            $(selecter).eq(0).animate({
                opacity: '1'
            }, 500);

            $('.aw-login-state p').eq(0).show().siblings().hide();

        }
        else
        {
            $(selecter).eq(num + 1).animate({
                opacity: '1'
            }, 500);

            $('.aw-login-state p').eq(num + 1).show().siblings().hide();
        }
    }, 7000);
}