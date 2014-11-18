var questions_list = new Array();
var answers_list = new Array();

var cur_page = 1;
var loading_data = false;
var stop_load = false;

$(document).ready(function () {
	$('#left_panel .aw-mod ul').css('height', ($(window).height() - $('#left_panel .aw-mod').offset()['top']));
	// 侧边导航添加选中样式
	$(document).on('click', '#data_lister #show_answer', function () {
		$('#data_lister li').removeClass('current');

		$(this).parent().addClass('current');

		view_answer($(this).attr('data_id'));

		return false;
	});

	$('#data_lister').scroll(function() {
		 if (($(this)[0].scrollTop + $(this).height()) >= $(this)[0].scrollHeight && loading_data == false && stop_load == false) {
			load_questions_list();
		 }
	});

	load_questions_list();

	//模拟下拉列表
	$('.dropdown-list-wrap span').click(function() {
		if ($(this).hasClass('active')) {
			$('.dropdown-list-wrap ul').hide();
			$(this).removeClass('active');
		}else {
			$('.dropdown-list-wrap ul').show();
			$(this).addClass('active');
		}
	});
	$('.dropdown-list-wrap ul li').click(function() {
		$('.dropdown-list-wrap span').removeClass('active').html($(this).find('a').text());
		$('.dropdown-list-wrap ul').hide();
		$('#feature_selecter').val($('#feature_selecter option').eq($(this).index()).val());
		reload_questions_list();
	});
});

$(window).resize(function(){
	$('#left_panel .aw-mod ul').css('height', ($(window).height() - $('#left_panel .aw-mod').offset()['top']));
});

function reload_questions_list() {
	cur_page = 1;
	questions_list = new Array();
	answers_list = new Array();

	$('#data_lister').empty();

	load_questions_list();
}

function load_questions_list() {
	$('#data_lister').append('<li class="loading">Loading...</li>');

	loading_data = true;

	$('#feature_selecter').attr('disabled', true);

 	$.getJSON(G_BASE_URL + '/reader/ajax/questions_list/?page=' + cur_page + '&feature_id=' + $('#feature_selecter').val(), function(data) {

 		if (data == '') {
	 		stop_load = true;
 		}
 		$('#data_lister li.loading').remove();

		$.each(data.questions, function (i, a) {
			questions_list[i] = a;
	 	});

		$.each(data.answers, function (i, a) {
			if (questions_list[a['question_id']]) {
				answers_list[i] = a;

				template = '<li>' +
					'<a href="javascript:;" id="show_answer" data_id="' + a['answer_id'] + '">' +
						'<h2>' + questions_list[a['question_id']]['question_content'] + '</h2>' +
							'<p>' +
								'<img src="' + a['avatar'] + '" alt="" />' +
								'<strong>' + a['user_name'] + '</strong>';

				if (a['signature'])
				{
					template += ' -';
				}

				template += '<span> ' + a['signature'] + '</span>' +
							'</p>' +
							'<span class="vote-count">' + a['agree_count'] + '<i class="icon icon-agree"></i>' + '</span>' +
					'</a>' +
				'</li>';

				$('#data_lister').append(template);
			}
		});

		loading_data = false;

		$('#feature_selecter').attr('disabled', false);

		cur_page++;
	});
}

function view_answer(answer_id) {
	$('#top_actions').attr('href', G_BASE_URL + '/question/' + answers_list[answer_id]['question_id']);

	$('#answer_users').html(questions_list[answers_list[answer_id]['question_id']]['answer_users']);
	$('#focus_count').html(questions_list[answers_list[answer_id]['question_id']]['focus_count']);
	$('#view_count').html(questions_list[answers_list[answer_id]['question_id']]['view_count']);

	$('#entry_title').html(questions_list[answers_list[answer_id]['question_id']]['question_content']);
	$('#question_description').html(questions_list[answers_list[answer_id]['question_id']]['question_detail']);

	$('#question_topics').empty();

	if (questions_list[answers_list[answer_id]['question_id']]['topics'])
	{
		$.each(questions_list[answers_list[answer_id]['question_id']]['topics'], function (k, v) {
			$('#question_topics').append('<li><a href="' + G_BASE_URL + '/topic/' + v['url_token'] + '">' + v['topic_title'] + '</a></li>');
		});
	}

	$('.aw-answer-info a').attr('href', G_BASE_URL + '/people/' + answers_list[answer_id]['uid']);
	$('#author_img').attr('src', answers_list[answer_id]['avatar']);
	$('#author_name').html(answers_list[answer_id]['user_name']);

	// 签名为空则显示空行
	if (answers_list[answer_id]['signature'] == '') {
		answers_list[answer_id]['signature'] = '&nbsp;';

	}

	$('#author_intro').html(answers_list[answer_id]['signature']);
	$('#vote_info_num').html(answers_list[answer_id]['agree_count']);

	$('#vote_info_users_list').empty();
	$('#vote_info_users_list_more').empty().hide();
	$('#vote_info_more_link').show();
	$('#answer_vote_info').hide();

	if (answers_list[answer_id]['agree_users']) {
		$.each(answers_list[answer_id]['agree_users'], function (i, name) {
			if ($('#vote_info_users_list li').length < 5)
			{
				$('#vote_info_users_list').append('<li><a href="' + G_BASE_URL + '/people/' + i + '">' + name + '</a>、</li>');
			}
			else
			{
				$('#vote_info_users_list_more').append('<li><a href="' + G_BASE_URL + '/people/' + i + '">' + name + '</a>、</li>');
			}
		});

		$('#answer_vote_info').show();
	}

	$('#answer_content').html(answers_list[answer_id]['answer_content']);
	$('#add_time').html(answers_list[answer_id]['add_time']);
	$('#right_panel').show();
}
