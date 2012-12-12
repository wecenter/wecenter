$(document).ready(function(){
	if (typeof(DateInput) != 'undefined')
	{
		$('input.date_picker').date_input();
	}
	
	$("#main-nav li a.nav-top-item").click( // When a top menu item is clicked...
		function () {
			$(this).parent().siblings().find("ul").slideUp("normal"); // Slide up all sub menus except the one clicked
			$(this).next().slideToggle("normal"); // Slide down the clicked sub menu
			return false;
		}
	);

	$("#main-nav li .nav-top-item").hover(
		function () {
			$(this).stop().animate({ paddingLeft: "35px" }, 200);
		}, 
		function () {
			$(this).stop().animate({ paddingLeft: "25px" });
		}
	);
	
	$(".content-tgbox-header").css({ "cursor":"s-resize" }); // Give the h3 in Content Box Header a different cursor
	//$(".closed-box .content-box-content").hide(); // Hide the content of the header if it has the class "closed"
	//$(".closed-box .content-box-tabs").hide(); // Hide the tabs in the header if it has the class "closed"
	
	$(".content-tgbox-header").click( // When the h3 is clicked...
		function () {
		  $(this).next().slideToggle('fast'); // Toggle the Content Box
		  $(this).toggleClass("closed-box"); // Toggle the class "closed-box" on the content box
		  $(this).parent().find(".content-box-tabs").toggle(); // Toggle the tabs
		}
	);
	
	// Content box tabs:
	$('.content-box .content-box-content div.tab-content').hide(); // Hide the content divs
	$('ul.content-box-tabs li a.default-tab').addClass('current'); // Add the class "current" to the default tab
	$('.content-box-content div.default-tab').show(); // Show the div with class "default-tab"
	
	$('.content-box ul.content-box-tabs li a').click( // When a tab is clicked...
		function() { 
			$(this).parent().siblings().find("a").removeClass('current'); // Remove "current" class from all tabs
			$(this).addClass('current'); // Add class "current" to clicked tab
			var currentTab = $(this).attr('href'); // Set variable "currentTab" to the value of href of clicked tab
			$(currentTab).siblings().hide(); // Hide all content divs
			$(currentTab).show(); // Show the content div with the id equal to the id of clicked tab
			return false; 
		}
	);

	//Close button:
	$(".close").click(
		function () {
			$(this).parent().fadeOut();
			return false;
		}
	);
	
	// Alternating table rows:
	$('.list tbody tr:even').addClass("alt-row"); // Add class "alt-row" to even table rows
	
	// Check all checkboxes when the one in a table head is checked:
	$('.check-all').click(
		function(){
			$(this).parent().parent().parent().parent().find("input[type='checkbox']").attr('checked', $(this).is(':checked'));   
		}
	);
	
	$(".fancybox").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: false,
		width		: '70%',
		height		: '60%',
		autoSize	: false,
		closeClick	: false
	});
});