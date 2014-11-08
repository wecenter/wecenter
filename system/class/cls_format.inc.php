<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class FORMAT
{
	public static function parse_links($str)
	{
		$str = @preg_replace_callback('/(?<!!!\[\]\(|"|\'|\)|>)(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)(?!"|\'|\)|>)/i', 'parse_link_callback', $str);

		if (strpos($str, 'http') === FALSE)
		{
			$str = @preg_replace_callback('/(www\.[-a-zA-Z0-9@:;%_\+\.~#?&\/\/=]+)/i', 'parse_link_callback', $str);
		}

		$str = @preg_replace('/([a-z0-9\+_\-]+[\.]?[a-z0-9\+_\-]+@[a-z0-9\-]+\.+[a-z]{2,6}+(\.+[a-z]{2,6})?)/is', '<a href="mailto:\1">\1</a>', $str);

		return $str;
	}

	public static function outside_url_exists($str)
	{
		$str = strtolower($str);

		if (strstr($str, 'http'))
		{
			preg_match_all('/(?<!!!\[\]\(|"|\'|\)|>)(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)(?!"|\'|\)|>)/i', $str, $matches);
		}
		else
		{
			preg_match_all('/(www\.[-a-zA-Z0-9@:;%_\+\.~#?&\/\/=]+)/i', $str, $matches);
		}

		if ($matches)
		{
			foreach($matches as $key => $val)
			{
				if (!$val)
				{
					continue;
				}

				if (!is_inside_url($val[0]))
				{
					return true;
				}
			}
		}

		return false;
	}

	public static function parse_attachs($str, $get_attachs_id = false)
	{
		if ($get_attachs_id)
		{
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $str, $matches);

			return array_unique($matches[1]);
		}
		else
		{
			return preg_replace_callback('/\[attach\]([0-9]+)\[\/attach\]/i', 'parse_attachs_callback', $str);
		}
	}

	public static function parse_markdown($text)
	{
		if (!$text)
		{
			return false;
		}

		return load_class('Services_Markdown')->transform($text);
	}

	public static function bbcode_2_markdown($text)
	{
		$p[] = '#\[img\]([\w]+?://[\w\#$%&~/.\-;:=,' . "'" . '?@\[\]+]*?)\[/img\]#is';
		$p[] = '#\[img\]<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,' . "'" . '?@\[\]+]*?)</a>\[/img\]#is';

		$p[] = "#\[url\]([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
		$p[] = "#\[url\]<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\[/url\]#is";

		$p[] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
		$p[] = "#\[url=<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\]<a (.*?)>([^?\n\r\t].*?)</a>\[/url\]#is";
		$p[] = "#\[url=<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\]([^?\n\r\t].*?)\[/url\]#is";

		$p[] = "#\[url=([\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
		$p[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
		$p[] = '/\[url\]([^?].*?)\[\/url\]/i';
		$p[] = "#\[color=(.*?)\](.*?)\[/color\]#is";
		$p[] = "#\[size=(.*?)\](.*?)\[/size\]#is";
		$p[] = "#\[font=(.*?)\](.*?)\[/font\]#is";
		$p[] = '/\[pre\]([^?].*?)\[\/pre\]/i';
		$p[] = '/\[address\]([^?].*?)\[\/address\]/i';
		$p[] = '/\[h1\]([^?].*?)\[\/h1\]/i';
		$p[] = '/\[h2\]([^?].*?)\[\/h2\]/i';
		$p[] = '/\[h3\]([^?].*?)\[\/h3\]/i';
		$p[] = "#\[code=(.*?)\](.*?)\[/code\]#is";
		$p[] = "#\[li\](.*?)\[/li\]#is";

		$r[] = '!($1)';
		$r[] = '!($2)';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '## $1';
		$r[] = '### $1';
		$r[] = '### $1';
		$r[] = '{{{$2}}}';
		$r[] = '- $1';

		$text = preg_replace($p, $r, $text);

		$text = str_ireplace(array('[ul]', '[ol]', '[/ul]', '[/ol]'), '', $text);

		preg_match('/\[b\]/i', $text, $_m_b_open);
		preg_match('/\[\/b\]/i', $text, $_m_b_close);

		preg_match('/\[i\]/i', $text, $_m_i_open);
		preg_match('/\[\/i\]/i', $text, $_m_i_close);

		preg_match('/\[u\]/i', $text, $_m_u_open);
		preg_match('/\[\/u\]/i', $text, $_m_u_close);

		preg_match('/\[s\]/i', $text, $_m_s_open);
		preg_match('/\[\/s\]/i', $text, $_m_s_close);

		preg_match('/\[quote\]/i', $text, $_m_quote_open);
		preg_match('/\[\/quote\]/i', $text, $_m_quote_close);

		if (count($_m_b_open) == count($_m_b_close)) {
			$text = str_ireplace("[b]\n", '[b]', $text);
			$text = str_ireplace("\n[/b]", '[/b]', $text);
			$text = str_ireplace('[b]', '**', $text);
			$text = str_ireplace('[/b]', '**', $text);
		}

		if (count($_m_i_open) == count($_m_i_close)) {
			$text = str_ireplace("[i]\n", '[i]', $text);
			$text = str_ireplace("\n[/i]", '[/i]', $text);
			$text = str_ireplace('[i]', '_', $text);
			$text = str_ireplace('[/i]', '_', $text);
		}

		if (count($_m_u_open) == count($_m_u_close)) {
			$text = str_ireplace("[u]\n", '[u]', $text);
			$text = str_ireplace("\n[/u]", '[/u]', $text);
			$text = str_ireplace('[u]', '', $text);
			$text = str_ireplace('[/u]', '', $text);
		}

		if (count($_m_s_open) == count($_m_s_close)) {
			$text = str_ireplace("[s]\n", '[s]', $text);
			$text = str_ireplace("\n[/s]", '[/s]', $text);
			$text = str_ireplace('[s]', '', $text);
			$text = str_ireplace('[/s]', '', $text);
		}

		if (count($_m_quote_open) == count($_m_quote_close)) {
			$text = str_ireplace("[quote]\n", '[quote]', $text);
			$text = str_ireplace("\n[/quote]", '[/quote]', $text);
			$text = str_ireplace('[quote]', '> ', $text);
			$text = str_ireplace('[/quote]', "\n", $text);
		}

		$text = preg_replace('/\[(?![\/]?attach)[^\[\]]{1,}\]/', '', $text);

		return $text;
	}

	public static function sub_url($url, $length)
	{
		if (strlen($url) > $length)
		{
			$url = str_replace(array('%3A', '%2F'), array(':', '/'), rawurlencode($url));

			$url = substr($url, 0, intval($length * 0.6)) . ' ... ' . substr($url, - intval($length * 0.1));
		}

		return $url;
	}
}