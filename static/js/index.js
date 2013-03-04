var cur_page = 0;
var bp_more_inner_o = '';
var cur_uid = 0;
var cur_filter = '';

function reload_list()
{	
	cur_page = 0;
	
	$('#c_list').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
	
	$('#bp_more').html(bp_more_inner_o);
	
	$('#bp_more').click();
}

$(document).ready(function()
{
	if (Number($("#announce_num").html()) > 0)
	{
		request_url = G_BASE_URL + '/notifications/ajax/list/flag-0__page-0';
		
		$.get(request_url, function (response)
		{
			if (response.length)
			{
				$("#notification_list").html(response);
				
				notification_show(5)
			}
		});
	}
	
	bp_more_inner_o = $('#bp_more').html();
	
	$('#i_tabs a').click(function () {
		if ($('#c_title').attr('id') != null && $(this).attr('rel'))
		{
			$('#i_tabs a, #i_tabs li').removeClass('cur');
			
			window.location.hash = $(this).attr('rel');
			
			$('#c_title').html($(this).html());
			
			$(this).addClass('cur');
			$(this).parents('li').addClass('cur');
			
			reload_list();
			
			return false;
		}
	});
	
	$('#bp_more').click(function()
	{
		var _this = this;
		var index_all = false;	// 首页最新动态
		
		$("#delete_draft").hide();
		$("#c_list").removeClass();
		
		
		switch (window.location.hash)
		{
			default:
				if (window.location.hash != '#all')
				{
					query_string = window.location.hash.replace(/#/g, '').split('__');
	
					for (i = 0; i < 3; i++)
					{
						if (!query_string[i])
						{
							query_string[i] = '';
						}
					}
					
					if (query_string[1])
					{
						cur_filter = query_string[1];
					}
					else
					{
						cur_filter = '';
					}
					
					if (query_string[2])
					{
						cur_uid = query_string[2];
					}
					else
					{
						cur_uid = 0;
					}
					
					index_all = false;
				}
				else
				{
					cur_filter = '';
					cur_uid = 0;
					index_all = true;
				}
				
				var request_url = G_BASE_URL + '/home/ajax/index_actions/page-' + cur_page + '__type-all__uid-' + cur_uid + '__filter-' + cur_filter;
			break;

			case '#draft_list__draft':
				var request_url = G_BASE_URL + '/home/ajax/draft/page-' + cur_page;
				
				$("#c_list").addClass("default_draft");
				
				$("#delete_draft").show();
			break;

			case '#invite_list__invite':
				var request_url = G_BASE_URL + '/home/ajax/invite/page-' + cur_page;
				
				$("#c_list").addClass("default_draft");
			break;
		}
		
		$(this).addClass('loading');
		$(this).find('a').html('正在载入...');
		
		$.get(request_url, function (response)
		{
			if (response.length)
			{
				if (cur_page == 0)
				{
					$('#c_list').html(response);
				}
				else
				{
					$('#c_list').append(response);
				}
					
				cur_page++;
				
				$(_this).html(bp_more_inner_o); 
				
			}
			else
			{
				if (cur_page == 0)
				{
					$('#c_list').html('<p style="padding: 15px 0" align="center">没有内容</p>');
				}
					
				$(_this).addClass('disabled');
				
				$(_this).find('a').html('没有更多了');
			}
			
			$(_this).removeClass('loading');
			
			index_all ? $('#c_list >.S_module').addClass('index_module') : '';
			
		})
		
		return false;
	});
	
	if ($('#i_tabs a[rel=' + window.location.hash.replace(/#/g, '') + ']').attr('href'))
	{
		$('#i_tabs a[rel=' + window.location.hash.replace(/#/g, '') + ']').click();
	}
	else
	{
		$('#i_tabs a[rel=all]').click();
	}
});

function _welcome_step_1_form_processer(result)
{
	if (result.errno != 1)
	{		
		alert(result.err);
	}
	else
	{
		welcome_step_2_load();
	}
}

function welcome_step_1_load()
{
	var elem_mask = document.createElement('div');
	var height = Math.max(document.body.clientHeight,document.documentElement.scrollHeight,document.body.scrollHeight);
	var width =  Math.max(document.body.clientWidth,document.documentElement.scrollWidth,document.body.scrollWidth);
	elem_mask.style.cssText = 'width:'+width+'px;height:'+height+'px';
	elem_mask.className = 'i_mask i_pas i_alpha_login';
	elem_mask.id = 'xd_mask';
	document.body.appendChild(elem_mask);
	
	var elem_data = document.createElement('div');
	elem_data.id = 'xd_data';
	document.body.appendChild(elem_data);
	
	document.getElementById('xd_data').innerHTML = $('#welcome_step1').html();
	
	$('#welcome_step1').remove();
	
	$(".select_area").LocationSelect({
        labels: ["请选择省份或直辖市", "请选择城市"],
        
        elements: document.getElementsByTagName("select"),
        
        detector: function () {
	  		this.selectID(["", ""]);
   		},	// 默认显示的城市
        
		dataUrl: G_BASE_URL + '/account/ajax/areas_json_data/'
	});
		
	init_avatar_uploader($('#welcome_avatar_uploader'), $('#welcome_avatar_uploading_status'), $("#welcome_avatar_src"));
}

function welcome_step_2_load()
{
	document.getElementById('xd_data').innerHTML = $('#welcome_step2').html();
	
	$('#welcome_step2').remove();
	
	$('#welcome_topics_list').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
		
	$.get(G_BASE_URL + '/account/ajax/welcome_get_topics/', function (result) {
		$('#welcome_topics_list').html(result);
	});
}

function welcome_step_3_load()
{
	document.getElementById('xd_data').innerHTML = $('#welcome_step3').html();
	
	$('#welcome_step3').remove();
	
	$('#welcome_users_list').html('<p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p>');
		
	$.get(G_BASE_URL + '/account/ajax/welcome_get_users/', function (result) {
		$('#welcome_users_list').html(result);
	});
}

function welcome_step_finish()
{
	$('#xd_data, #xd_mask').remove();
	
	$.get(G_BASE_URL + '/account/ajax/clean_first_login/', function (result)
	{
		//window.location = G_BASE_URL + '/home/';
	});
}

function check_actions_new(uid, time)
{
	var url = G_BASE_URL + "/home/ajax/check_actions_new/uid-" + uid + "__time-" + time;

	$.get(url, function (result) 
	{
		if (result.errno == 1)
		{
			if (result.rsm.new_count > 0)
			{
				if ($("#new_actions_tip").is(":hidden"))
				{
					$("#new_actions_tip").fadeIn();
				}
				
				$("#new_action_num").html(result.rsm.new_count);
			}
		}
	}, 'json');
}