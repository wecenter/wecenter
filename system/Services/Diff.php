<?php
/**
 * WordPress Diff bastard child of old MediaWiki Diff Formatter.
 *
 * Basically all that remains is the table structure and some method names.
 *
 * @package WordPress
 * @subpackage Diff
 */

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once( dirname(__FILE__) . '/Text/Diff.php' );	

/** Text_Diff_Renderer class */
require_once( dirname(__FILE__) . '/Text/Diff/Renderer.php' );

/** Text_Diff_Renderer_inline class */
require_once( dirname(__FILE__) . '/Text/Diff/Renderer/inline.php' );


class Services_diff extends Text_Diff
{	
	public function __construct($left_string, $right_string)
    {	
		$left_string  =  preg_replace(array('/\n+/', '/[ \t]+/'), array("\n", ' '), str_replace("\r", "\n", trim($left_string)));
		$right_string = preg_replace(array('/\n+/', '/[ \t]+/'), array("\n", ' '), str_replace("\r", "\n", trim($right_string)));
					 
		$left_string = str_replace('。', "。\n", $left_string);
		$right_string = str_replace('。', "。\n", $right_string);
		
		$left_lines  = explode("\n", $left_string);
		$right_lines = explode("\n", $right_string);

    	$this->Text_Diff($left_lines, $right_lines);
    }
    
    public function get_Text_Diff_Renderer_inline()
    {
    	$renderer = new Text_Diff_Renderer_inline();
    	return  $renderer->render($this);
    }
}
