$(document).ready(function ()
{
	if($(":input[name=human_valid]:checked").val() == 0)
	{
		$("p[rel=human_valid]").hide()
	}
	
	if($(":input[name=publish_approval]:checked").val() == 0)
	{
		$("p[rel=publish_approval]").hide()
	}
	
	$(":input[name=human_valid]").change(function()
	{
		if($(":input[name=human_valid]:checked").val() == 1)
		{
			$("p[rel=human_valid]").show();
		}
		else
		{
			$("p[rel=human_valid]").hide();
		}
	});
	
	$(":input[name=publish_approval]").change(function()
	{
		if($(":input[name=publish_approval]:checked").val() == 1)
		{
			$("p[rel=publish_approval]").show();
		}
		else
		{
			$("p[rel=publish_approval]").hide();
		}
	});
	
	$(":input[name=visit_site]").change(function()
	{
		if($(":input[name=visit_site]:checked").val() == 1)
		{
			$("#_save_form p").show();
		}
		else
		{
			$("#_save_form p").slice(1, 8).hide();
		}
	});

	$(":input[name=visit_site]").change();
});

function addrow(obj){
	var rowdata = '<tr><td></td><td></td><td><input type="text" style="width:100px;" value="" class="text-input" name="group_new[group_name][]" size="16"></td><td><input type="text" value="" class="text-input small-input" name="group_new[reputation_lower][]" size="6"> ~ <input type="text" value="" class="text-input small-input" name="group_new[reputation_higer][]" size="6"></td><td><input type="text" value="" class="text-input small-input" name="group_new[reputation_factor][]" size="6"></td><td></td></tr>';

	obj.parent().parent().parent().before(rowdata);

	obj.parent().find("span").show();

	$('tbody tr').removeClass();
	$('tbody tr:even').addClass("alt-row");
}