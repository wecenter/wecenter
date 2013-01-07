<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class aws_external_class extends AWS_MODEL
{
	public function format_js_question_ul_output($ul_class, $data)
	{
		$output = '<ul class="' . $ul_class . '">';
		
		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				if ($val['title'])
				{
					$val['question_content'] = $val['title'];
				}
				
				$output .= '<li><a href="' . get_js_url('/question/' . $val['question_id']) . '" target="_blank">' . $val['question_content'] . '</a></li>';
			}	
		}
		
		$output .= '</ul>';
		
		return "document.write('" . addcslashes($output, "'") . "');";
	}
}