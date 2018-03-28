<?php

class Services_BBCode
{
	protected $bbcode_table = array();

	private function _code_callback($match)
	{
		return '<pre>' . str_replace('[', '<span>[</span>', $match[1]) . '</pre>';
	}

	private function _b_callback($match)
	{
		return '<strong>' . $match[1] . '</strong>';
	}

	private function _i_callback($match)
	{
		return '<em>' . $match[1] . '</em>';
	}

	private function _quote_callback($match)
	{
		return '<blockquote><p>' . $match[1] . '</p></blockquote>';
	}

	private function _size_callback($match)
	{
		return '<span style="font-size:' . intval($match[1]) . 'px">' . $match[2] . '</span>';
	}

	private function _s_callback($match)
	{
		return '<del>' . $match[1] . '</del>';
	}

	private function _u_callback($match)
	{
		return '<span style="text-decoration:underline;">' . $match[1] . '</span>';
	}

	private function _url_callback($match)
	{
		if (stristr($match[1], 'http://%'))
		{
			return false;
		}

		if (stristr($match[1], 'http://&'))
		{
			return false;
		}

		if (!preg_match('#^(http|https)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $match[1]))
		{
			return false;
		}

		return '<a href="' . str_replace(array('"', "'"), '', $match[1]) . '" rel="nofollow" target="_blank">' . $match[1] . '</a>';
	}

	private function _link_callback($match)
	{
		if (stristr($match[1], 'http://%'))
		{
			return false;
		}

		if (stristr($match[1], 'http://&'))
		{
			return false;
		}

		if (!preg_match('#^(http|https)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $match[1]))
		{
			return false;
		}

		return '<a href="' . str_replace(array('"', "'"), '', $match[1]) . '" rel="nofollow" target="_blank">' . htmlspecialchars($match[2]) . '</a>';
	}

	private function _img_callback($match)
	{
		if (!$match[1])
		{
			return false;
		}

		$match[1] = strip_tags($match[1]);

		if (stristr($match[1], 'http://%'))
		{
			return false;
		}

		if (stristr($match[1], 'http://&'))
		{
			return false;
		}

		if (!preg_match('#^(http|https)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $match[1]))
		{
			return false;
		}

		return '<img src="' . str_replace(array('"', "'"), '', $match[1]) . '" alt="" />';
	}

	private function _list_callback($match)
	{
		$match[1] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $match[1]);

        return "<ul>" . preg_replace("/[\n\r?]/", "", $match[1]) . "</ul>";
	}

	private function _list_element_callback($match)
	{
		return "<li>" . preg_replace("/[\n\r?]$/", "", $match[1]) . "</li>";
	}

	private function _video_callback($match)
	{
		return load_class('Services_VideoUrlParser')->parse($match[1]);
	}

	private function _list_advance_callback($match)
	{
		if ($match[1] == '1')
        {
            $list_type = 'ol';
        }
        else
        {
        	$list_type = 'ul';
        }

        $match[2] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $match[2]);

        return '<' . $list_type . '>' . preg_replace("/[\n\r?]/", "", $match[2]) . '</' . $list_type . '>';
	}

    public function __construct()
    {
	    // Replace [code]...[/code] with <pre><code>...</code></pre>
        $this->bbcode_table["/\[code\](.*?)\[\/code\]/is"] = '_code_callback';

        // Replace [b]...[/b] with <strong>...</strong>
        $this->bbcode_table["/\[b\](.*?)\[\/b\]/is"] = '_b_callback';

        // Replace [i]...[/i] with <em>...</em>
        $this->bbcode_table["/\[i\](.*?)\[\/i\]/is"] = '_i_callback';

        // Replace [quote]...[/quote] with <blockquote><p>...</p></blockquote>
        $this->bbcode_table["/\[quote\](.*?)\[\/quote\]/is"] = '_quote_callback';

        // Replace [size=30]...[/size] with <span style="font-size:30%">...</span>
        $this->bbcode_table["/\[size=(\d+)\](.*?)\[\/size\]/is"] = '_size_callback';

        // Replace [s] with <del>
        $this->bbcode_table["/\[s\](.*?)\[\/s\]/is"] = '_s_callback';

        // Replace [u]...[/u] with <span style="text-decoration:underline;">...</span>
        $this->bbcode_table["/\[u\](.*?)\[\/u\]/is"] = '_u_callback';

        // Replace [color=somecolor]...[/color] with <span style="color:somecolor">...</span>
        /*$this->bbcode_table["/\[color=([#a-z0-9]+)\](.*?)\[\/color\]/is"] = function ($match)
        {
            return '<span style="color:' . $match[1] . ';">' . $match[2] . '</span>';
        };*/


        // Replace [url]...[/url] with <a href="...">...</a>
        $this->bbcode_table["/\[url\](.*?)\[\/url\]/is"] = '_url_callback';

        // Replace [url=http://www.google.com/]A link to google[/url] with <a href="http://www.google.com/">A link to google</a>
        $this->bbcode_table["/\[url=(.*?)\](.*?)\[\/url\]/is"] = '_link_callback';

        // Replace [img]...[/img] with <img src="..."/>
        $this->bbcode_table["/\[img\](.*?)\[\/img\]/is"] = '_img_callback';

        // Replace [video]...[/video] with swf video player
        $this->bbcode_table["/\[video\](.*?)\[\/video\]/is"] = '_video_callback';

        // Replace [list]...[/list] with <ul><li>...</li></ul>
        $this->bbcode_table["/\[list\](.*?)\[\/list\]/is"] = '_list_callback';

        // Replace [list=1|a]...[/list] with <ul|ol><li>...</li></ul|ol>
        $this->bbcode_table["/\[list=(1|a)\](.*?)\[\/list\]/is"] = '_list_advance_callback';

        return $this;
    }

    public function parse($text)
    {
        if (! $text)
        {
            return false;
        }

        foreach ($this->bbcode_table AS $key => $val)
        {
            $text = preg_replace_callback($key, array(&$this, $val), $text);
        }

        return $text;
    }
}
