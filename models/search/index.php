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

class search_index_class extends AWS_MODEL
{
	public function encode_search_code($string)
	{
		if (is_array($string))
		{
			$string = implode(' ', $string);
		}
		
		$string = convert_encoding($string, 'UTF-8', 'UTF-16');

		for ($i = 0; $i < strlen($string); $i++, $i++)
    	{ 
    		$code = ord($string{$i}) * 256 + ord($string{$i + 1});
    		
    		if ($code == 32)
    		{
    			$output .= ' ';
    		}
    		else if ($code < 128)
    		{ 
    			$output .= chr($code); 
    		}
    		else if ($code != 65279)
    		{ 
    			//$output .= '&#' . $code . ';'; 
    			$output .= $code;
    		}
    	}
    	
    	return htmlspecialchars($output);
	}
	
	public function push_index($type, $string, $item_id)
	{
		if ($keywords = $this->model('system')->analysis_keyword($string))
		{
			if (sizeof($keywords) > 10)
			{
				$keywords = array_slice($keywords, 0, 10);
			}
			
			$search_code = $this->encode_search_code($keywords);
		}
		
		switch ($type)
		{
			case 'question':
				return $this->update('question', array(
					'question_content_fulltext' => $search_code
				), 'question_id = ' . intval($item_id));
			break;
			
			/*case 'topic':
				return $this->update('topic', array(
					'topic_title_fulltext' => $search_code
				), 'topic_id = ' . intval($item_id));
			break;
			
			case 'user':
				return $this->update('users', array(
					'user_name_fulltext' => $search_code
				), 'uid = ' . intval($item_id));
			break;*/
		}
	}
}