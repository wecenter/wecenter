$(document).ready(function() {
	$('.box_shadow').css('top', $('.r_indexfiller').innerHeight() + 2);
	
	$('.i_elmsInputs input,.i_elmsInputs select').focus(function() {
		$(this).addClass('i_cur');	
	}).blur(function() {
		$(this).removeClass('i_cur');	
	});
	
	$('.q_nav_tx').hover(function() {
		$(this).siblings().removeClass('i_prl');
	}, function() {
		$(this).siblings().addClass('i_prl');
	});
});