$(function () {

    $('#captcha').click();

    // bs自带方法 TAB切换
    $('#myTab a').click(function (e) 
    {
        e.preventDefault();
        $(this).tab('show');
    });

    
    // bs自带方法-气泡提示
    $('.aw-content-wrap .md-tip').tooltip('hide');

    $('.aw-header .mod-head-btn').click(function () 
    {

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
    if (typeof (DateInput) != 'undefined') 
    {
        $('input.mod-data').date_input();
    }

    // 单选框 input checked radio 初始化
    $('.aw-content-wrap').find("input").iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%'
    });


    // 左侧导航菜单的折叠与展开
    $('.mod-bar>li>a').click(function () 
    {
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
    $('.aw-nav-menu li .mod-set-head').click(function () 
    {
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
    $('.aw-content-wrap .table').find(".check-all").on('ifChecked', function (e) 
    {
        e.preventDefault()
        $(this).parents('table').find(".icheckbox_square-blue").iCheck('check');

    });

    $('.aw-content-wrap .table').find(".check-all").on('ifUnchecked', function (e) 
    {
        e.preventDefault()
        $(this).parents('table').find(".icheckbox_square-blue").iCheck('uncheck');
    });


    //微博发布用户
    $('.aw-admin-weibo-answer .search-input').bind("keydown", function()
    {
        if (window.event && window.event.keyCode == 13)
        {
            return false;
        }
    });

    // 微博提问用户删除
    $(document).on('click', '.aw-admin-weibo-publish .delete', function()
    {
        $('.aw-admin-weibo-publish').find('.search-input').val('').show();

        $(this).parents('li').detach();
    });

    // 微博接收用户删除
    $(document).on('click', '.aw-admin-weibo-answer li .delete', function()
    {
        $(this).parent().detach();                                                         

        weiboPost($(this));
    });

    /**
     * 所有textarea高度自适应内容
     * jQuery oninput onpropertychange事件支持不准确。
     */

    var txt= getClass("textarea", "textarea"),
        leng = txt.length;

    for (var i = leng - 1; i >= 0; i--)
    {
        
        if(txt[i].value.length <= 0){

            txt[i].style.height = 52+"px";
        }else{
        
            txt[i].style.height = txt[i].scrollHeight+"px";
        }
         

        if(typeof txt[i].oninput=="undefined")
        {
           
           txt[i].onpropertychange=function()
           {
             if(event.propertyName=="value"){ 
               this.style.height="20px";
               this.style.height=this.scrollHeight+"px";
             }
           }
        }else{
           
           txt[i].oninput=function()
           {
             this.style.height="auto";
             this.style.height=this.scrollHeight+"px";
           }
        }
    };


    /*概述页面，新增话题数，点击排序*/
    
    $('#sorttable thead').delegate("td","click",function()
    {   
        if($(this).index()==0)
        {
            return false;
        }else{
            $(this).find('i').addClass('fa-sort-desc');
            $(this).siblings('td').find('i').removeClass('fa-sort-desc');
            
            if($(this).index()==1)
            {
                subjectData('week')
            }
            else if ($(this).index()==2)
            {
                subjectData('month')
            }
            else if ($(this).index()==3)
            {
                subjectData('all')
            }       
        }
        function subjectData(type){
            $.ajax({
               url: G_BASE_URL + '/admin/ajax/topic_statistic/tag-'+ type +'__limit-10',
               dataType:"json",
               type: "GET",
               beforeSend:function(){

                    AWS.loading('show');
                    var tempTop = $('#sorttable').offset().top + $('#sorttable').height()/2 - 50;
                    var tempLeft = $('#sorttable').offset().left + $('#sorttable').width()/2;
                    $('#aw-loading').css({top:tempTop+'px',left:tempLeft+'px',position:'absolute'})
               }, 
               success:function(data)
               {
                    AWS.loading('hide')
                    $.each(data,function(key,value){
                        var tempObj = $('#sorttable tbody tr:eq('+ key +')');                    
                        tempObj.find('td:eq(3)').text(value.all)
                        tempObj.find('td:eq(2)').text(value.month)
                        tempObj.find('td:eq(1)').text(value.week)
                        tempObj.find('td:eq(0)').text(value.title)
                    });
               }  
            });
        }
    });
    $('#sorttable thead td:eq(2)').click();

});
    
function weiboPost(obj)
{
    $.post(G_BASE_URL + '/admin/ajax/weibo_batch/', {'uid': obj.attr('data-id'), 'action':obj.attr('data-actions')}, function (result)
    {  
        if (result.errno == -1)
        {
            AWS.alert(result.err);
             $('.mod-weibo-reply li:last').detach()
            
        }
        else if (result.errno == 1)
        {   
            if(result.rsm != null){
                if (result.rsm.staus == 'bound')
                {   
                    $('.mod-weibo-reply li:last .btn-primary').text('更新 Access Token');
                }
                else
                {   
                   $('.mod-weibo-reply li:last .btn-primary').text('绑定新浪微博');  
                }
            }   

            $(".alert-box").modal('hide');
        }
    }, 'json');
};

function getClass(tagName, classStr) {

    if (document.getElementsByClassName) {
        return document.getElementsByClassName(classStr)
    } else {
        var nodes = document.getElementsByTagName(tagName),
            ret = [];

        for (var i = 0; i < nodes.length; i++) {
            if (hasClass(nodes[i], classStr)) {
                ret.push(nodes[i])
            }
        }
        return ret;
    }
    function hasClass(tagStr, classStr) {
        var arr = tagStr.className.split(/\s+/);
        for (var i = 0; i < arr.length; i++) {
            if (arr[i] == classStr) {
                return true;
            }
        }
        return false;
    }
};
