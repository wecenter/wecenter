$(function () {

    $('#captcha').click();

    // bs自带方法 TAB切换
    $('#myTab a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    //徽章兼容IE8 
    var tooltip = $('.aw-header').find(".label-danger");
    tooltip.text() > 0 ? tooltip.show() : tooltip.hide();
    
    // bs自带方法-气泡提示
    $('.aw-content-wrap .md-tip').tooltip('hide');

    $('.aw-header .mod-head-btn').click(function () {

        if ($('#aw-side').is(':hidden')) {
            $('#aw-side').show(0, function () {
                $('.aw-content-wrap, .aw-footer').css("marginLeft", "235px");
                $('.mod-echat-info').css("marginLeft", "0");
            });
        } else {
            $('#aw-side').hide(0, function () {
                $('.aw-content-wrap, .aw-footer').css("marginLeft", "0");
            });
        }
    });


    $("#aw-side").perfectScrollbar({
        wheelSpeed: 20,
        wheelPropagation: true,
        minScrollbarLength: 20
    })

    /*日期选择*/
    if (typeof (DateInput) != 'undefined') {
        $('input.mod-data').date_input();
    }

    // 单选框 input checked radio 初始化
    $('.aw-content-wrap').find("input").iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%'
    });


    // 左侧导航菜单的折叠与展开
    $('.mod-bar>li>a').click(function () {
        if ($(this).next().is(':visible')) {
            $(this).next().slideUp('normal');
            $(this).removeClass('collapsed active');

        } else {
            $('#aw-side').find('li').children('ul').hide();
            $(this).addClass('active collapsed').parent().siblings().find('a').removeClass('active collapsed');
            $(this).next().slideDown('normal');
        }
    });

    // input 菜单折叠，展开、拖动
    $('.aw-nav-menu li .mod-set-head').click(function () {
        if ($(this).parents('li').find('.mod-set-body').is(':visible')) {
            $(this).parents('li').find('.mod-set-body').slideUp();
        } else {
            $(this).parents('li').find('.mod-set-body').slideDown();
            $(this).parents('li').siblings('li').find('.mod-set-body').slideUp();
        }
    });

    $(".aw-nav-menu").find('ul:first').dragsort({
        dragEnd: function () {
            var arr = [];
            $.each($('.aw-nav-menu ul li'), function (i, e) {
                arr.push($(this).attr('data-sort'));
            });
            $('#nav_sort').val(arr.join(','));

        }
    });


    // input 单选框全选or 全取消
    $('.aw-content-wrap .table').find(".check-all").on('ifChecked', function (e) {
        e.preventDefault()
        $(this).parents('table').find(".icheckbox_square-blue").iCheck('check');

    });

    $('.aw-content-wrap .table').find(".check-all").on('ifUnchecked', function (e) {
        e.preventDefault()
        $(this).parents('table').find(".icheckbox_square-blue").iCheck('uncheck');
    });

});