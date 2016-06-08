<?php
/**
 * Video Parser
 *
 * @package
 * @version 1.3
 * @copyright 2005-2011 HDJ.ME
 * @author Dijia Huang <huangdijia@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 *
 * Usage
 * require_once "VideoUrlParser.class.php";
 * $urls[] = "http://v.youku.com/v_show/id_XMjI4MDM4NDc2.html";
 * $urls[] = "http://www.tudou.com/playlist/p/l13087099.html";
 * $urls[] = "http://www.tudou.com/programs/view/ufg-A3tlcxk/";
 * $urls[] = "http://v.ku6.com/special/show_4926690/Klze2mhMeSK6g05X.html";
 * $urls[] = "http://www.56.com/u68/v_NjI2NTkxMzc.html";
 * $urls[] = "http://www.letv.com/ptv/vplay/1168109.html";
 * $urls[] = "http://video.sina.com.cn/v/b/46909166-1290055681.html";
 * $urls[] = "http://www.youtube.com/watch?v=n6NLtldvGCk";
 *
 * foreach($urls as $url){
 * $info = VideoUrlParser::parse($url);
 * //var_dump($info);
 * echo "<a href='{$info['url']}' target='_new'>{$info['title']}</a>";
 * echo "<br />";
 * echo $info['object'];
 * echo "<br />";
 * }
 *
 *
 *
 * // 优酷
 * http://v.youku.com/v_show/id_XMjU0NjY4OTEy.html
 * <embed src="http://player.youku.com/player.php/sid/XMjU0NjY4OTEy/v.swf" quality="high" width="480" height="400" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash"></embed>
 *
 * // 土豆
 * http://www.tudou.com/playlist/p/a65929.html?iid=74905844
 * <embed src="http://www.tudou.com/l/A_0urj-Geec/&iid=74905844/v.swf" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="480" height="400"></embed>
 *
 * // 56
 * http://www.56.com/u98/v_NTkyODY2NTU.html
 * <embed src="http://player.56.com/v_NTkyODY2NTU.swf"  type="application/x-shockwave-flash" width="480" height="405" allowNetworking="all" allowScriptAccess="always"></embed>
 *
 * // Youtube
 * http://www.youtube.com/watch?v=n6NLtldvGCk
 * <embed src="http://www.youtube.com/v/n6NLtldvGCk?version=3&hl=th_TH" type="application/x-shockwave-flash" width="560" height="315" allowscriptaccess="always" allowfullscreen="true"></embed>
 */


class Services_VideoUrlParser
{
	const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.7 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.7';
	const CHECK_URL_VALID = "/(youku\.com|tudou\.com|56\.com|(my\.)?tv\.sohu\.com|v\.qq\.com|youtube\.com)/";

	/**
	 * parse
	 *
	 * @param string $url
	 * @static
	 * @access public
	 * @return void
	 */
	static public function parse($url = '')
	{
		$lowerurl = strtolower($url);

		if (strstr($lowerurl, '.swf'))
		{
			return '<p><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="640" height="400"><param name="movie" value="' . $url . '" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="allowFullScreen" value="true" /><embed src="' . $url . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="640" height="400" wmode="transparent" allowfullscreen="true"></embed></object></p>';
		}

		preg_match(self::CHECK_URL_VALID, $lowerurl, $matches);

		if (!$matches)
		{
			return '<p><img src="' . G_STATIC_URL .  '/common/video_parser_unsupport.png" alt="" /></p>';
		}

		if (!$data = AWS_APP::cache()->get('video_parse_' . md5($url)))
		{
			switch ($matches[1])
			{
				case 'youku.com' :
					$data = self::_parseYouku($url);
					break;
				case 'tudou.com' :
					$data = self::_parseTudou($url);
					break;
				case '56.com' :
					$data = self::_parse56($url);
					break;
				case 'my.tv.sohu.com' :
				case 'tv.sohu.com' :
				case 'sohu.com' :
					$data = self::_parseSohu($url);
					break;
				case 'v.qq.com' :
					$data = self::_parseQq($url);
					break;
				case 'youtube.com' :
					$data = self::_parseYoutube($url);
					break;
				default :
					return $url;
			}

			if ($data)
			{
				AWS_APP::cache()->set('video_parse_' . md5($url), $data, 3600, 'video_parser');
			}

		}

		if ($data)
		{
			if ($data['iframe'])
			{
				return '<p><iframe width="640" height="400" src="' . $data['iframe'] . '" frameborder="0" allowfullscreen="allowfullscreen"></iframe></p>';
			}
			else
			{
				return '<p><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="560" height="360"><param name="movie" value="' . $data['swf'] . '" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="allowFullScreen" value="true" /><embed src="' . $data['swf'] . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="460" height="360" wmode="transparent" allowfullscreen="true"></embed></object></p>';
			}
		}

		return '<p><img src="' . G_STATIC_URL .  '/common/video_parser_unsupport.png" alt="" /></p>';
	}

	/**
	 * 腾讯视频
	 * http://v.qq.com/cover/o/o9tab7nuu0q3esh.html?vid=97abu74o4w3_0
	 * http://v.qq.com/play/97abu74o4w3.html
	 * http://v.qq.com/cover/d/dtdqyd8g7xvoj0o.html
	 * http://v.qq.com/cover/d/dtdqyd8g7xvoj0o/9SfqULsrtSb.html
	 * http://imgcache.qq.com/tencentvideo_v1/player/TencentPlayer.swf?_v=20110829&vid=97abu74o4w3&autoplay=1&list=2&showcfg=1&tpid=23&title=%E7%AC%AC%E4%B8%80%E7%8E%B0%E5%9C%BA&adplay=1&cid=o9tab7nuu0q3esh
	 */
	static private function _parseQq($url)
	{
		$html = self::_fget($url);

		preg_match('/vid:"(\w+)"/i', $html, $matches);

		if (!$vid = $matches[1])
		{
			return false;
		}

		preg_match('/<h1 class="mod_player_title" title="(.+)" id="h1_title">/i', $html, $matches);
		$data['title'] = $matches[1];

		preg_match('/pic :"(.+)"/i', $html, $matches);
		$data['img'] = $matches[1];

		$data['url'] = $url;
		$data['swf'] = 'http://static.video.qq.com/TPout.swf?vid=' . $vid . '&auto=0';

		return $data;
	}

	/**
	 * 优酷网
	 * http://v.youku.com/v_show/id_XMjI4MDM4NDc2.html
	 * http://player.youku.com/player.php/sid/XMjU0NjI2Njg4/v.swf
	 */
	static private function _parseYouku($url)
	{
		preg_match("#id\_(\w+(?:==)?)#", $url, $matches);

		if (empty($matches))
		{
			preg_match("#v_playlist\/#", $url, $mat);

			if (!$mat)
			{
				return false;
			}

			$html = self::_fget($url);

			preg_match("#videoId2\s*=\s*\'(\w+)\'#", $html, $matches);

			if (!$matches)
			{
				return false;
			}
		}

		return array(
			'url' => $url,
			'iframe' => "http://player.youku.com/player.php/sid/{$matches[1]}/v.swf"
		);

		$link = "http://v.youku.com/player/getPlayList/VideoIDS/{$matches[1]}/timezone/+08/version/5/source/out?password=&ran=2513&n=3";

		$retval = self::_cget($link);

		if ($retval)
		{
			$json = json_decode($retval, true);

			$data['img'] = $json['data'][0]['logo'];
			$data['title'] = $json['data'][0]['title'];
			$data['url'] = $url;
			$data['iframe'] = "http://player.youku.com/embed/{$matches[1]}";

			return $data;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 土豆网
	 * http://www.tudou.com/programs/view/Wtt3FjiDxEE/
	 * http://www.tudou.com/v/Wtt3FjiDxEE/v.swf
	 *
	 * http://www.tudou.com/playlist/p/a65718.html?iid=74909603
	 * http://www.tudou.com/l/G5BzgI4lAb8/&iid=74909603/v.swf
	 */
	static private function _parseTudou($url)
	{
		$html = self::_fget($url);

		preg_match('/icode: \'(\w+)\'/i', $html, $matches);
		$icode = $matches[1];
		if (!$icode) return false;

		preg_match('/kw: \'(.+)\'/i', $html, $matches);
		$data['title'] = $matches[1];

		preg_match('/pic: \'(.+)\'/i', $html, $matches);
		$data['img'] = $matches[1];

		$data['url'] = $url;

		$data['swf'] = 'http://www.tudou.com/v/' . $icode . '/';

		return $data;
	}

	/**
	 * 56网
	 * http://www.56.com/u73/v_NTkzMDcwNDY.html
	 * http://player.56.com/v_NTkzMDcwNDY.swf
	 */
	static private function _parse56($url)
	{
		preg_match("#/v_(\w+)\.html#", $url, $matches);

		if (empty($matches))
		{
			return false;
		}

		$link = "http://vxml.56.com/json/{$matches[1]}/?src=out";
		$retval = self::_cget($link);

		if ($retval)
		{
			$json = json_decode($retval, true);

			$data['img'] = $json['info']['img'];
			$data['title'] = $json['info']['Subject'];
			$data['url'] = $url;
			$data['swf'] = "http://player.56.com/v_{$matches[1]}.swf";

			return $data;
		}
		else
		{
			return false;
		}
	}

	// 搜狐TV http://my.tv.sohu.com/u/vw/5101536
	static private function _parseSohu($url)
	{
		$html = iconv('GBK', 'UTF-8', self::_fget($url));

		preg_match_all('#<meta property="og:(title|image|videosrc)" content="(.+)" />#i', $html, $matches);

		$data['img'] = $matches[2][2];
		$data['title'] = $matches[2][1];
		$data['url'] = $url;
		$data['swf'] = $matches[2][0];

		return $data;
	}

	static private function _parseYoutube($url)
	{
		preg_match("#\?v=([0-9a-zA-Z_\-]+)#", $url, $matches);

		if (!$matches[1])
		{
			return false;
		}

		//$contents = self::_fget($url);

		//preg_match_all("#<title>([^<]+)<\/title>#", $contents, $contentMatches);

		$data['img'] = "http://img.youtube.com/vi/{$matches[1]}/0.jpg";
		//$data['title'] = $contentMatches[1][0];
		$data['url'] = $url;
		$data['iframe'] = "http://www.youtube-nocookie.com/embed/{$matches[1]}";

		return $data;
	}

	/*
     * 通过 file_get_contents 获取内容
     */
	static private function _fget($url = '')
	{
		if (!$url)
		{
			return false;
		}

		$html = self::_vita_get_url_content($url);
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
		{
			return $dehtml;
		}
		else
		{
			return $html;
		}
	}

	/*
     * 通过 fsockopen 获取内容
     */
	static private function _fsget($path = '/', $host = '', $user_agent = '')
	{
		if (!$path || !$host)
		{
			return false;
		}

		$user_agent = $user_agent ? $user_agent : self::USER_AGENT;

		$out = <<<HEADER
GET $path HTTP/1.1
Host: $host
User-Agent: $user_agent
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: zh-cn,zh;q=0.5
Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n\r\n
HEADER;
		$fp = @fsockopen($host, 80, $errno, $errstr, 10);
		if (!$fp)
			return false;
		if (!fputs($fp, $out))
			return false;
		while (!feof($fp))
		{
			$html .= fgets($fp, 1024);
		}
		fclose($fp);
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
			return $dehtml;
		else
			return $html;
	}

	/*
     * 通过 curl 获取内容
     */
	static private function _cget($url = '', $user_agent = '')
	{
		if (!$url)
		{
			return;
		}

		$user_agent = $user_agent ? $user_agent : self::USER_AGENT;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if (strlen($user_agent))
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

		ob_start();
		curl_exec($ch);
		$html = ob_get_contents();
		ob_end_clean();

		if (curl_errno($ch))
		{
			curl_close($ch);
			return false;
		}

		curl_close($ch);

		if (!is_string($html) || !strlen($html))
		{
			return false;
		}

		return $html;
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
			return $dehtml;
		else
			return $html;
	}

	static private function _vita_get_url_content($url)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		$file_contents = curl_exec($ch);

		curl_close($ch);

		return $file_contents;
	}

	static private function _gzdecode($data)
	{
		$len = strlen($data);

		if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b"))
		{
			return null; // Not GZIP format (See RFC 1952)
		}

		$method = ord(substr($data, 2, 1)); // Compression method
		$flags = ord(substr($data, 3, 1)); // Flags

		if ($flags & 31 != $flags)
		{
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}

		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack("V", substr($data, 4, 4));
		$mtime = $mtime[1];
		$xfl = substr($data, 8, 1);
		$os = substr($data, 8, 1);
		$headerlen = 10;
		$extralen = 0;
		$extra = "";

		if ($flags & 4)
		{
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8)
			{
				return false; // Invalid format
			}
			$extralen = unpack("v", substr($data, 8, 2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8)
			{
				return false; // Invalid format
			}
			$extra = substr($data, 10, $extralen);
			$headerlen += 2 + $extralen;
		}

		$filenamelen = 0;
		$filename = "";

		if ($flags & 8)
		{
			// C-style string file NAME data in header
			if ($len - $headerlen - 1 < 8)
			{
				return false; // Invalid format
			}
			$filenamelen = strpos(substr($data, 8 + $extralen), chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8)
			{
				return false; // Invalid format
			}
			$filename = substr($data, $headerlen, $filenamelen);
			$headerlen += $filenamelen + 1;
		}

		$commentlen = 0;
		$comment = "";

		if ($flags & 16)
		{
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8)
			{
				return false; // Invalid format
			}
			$commentlen = strpos(substr($data, 8 + $extralen + $filenamelen), chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8)
			{
				return false; // Invalid header format
			}
			$comment = substr($data, $headerlen, $commentlen);
			$headerlen += $commentlen + 1;
		}

		$headercrc = "";

		if ($flags & 1)
		{
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8)
			{
				return false; // Invalid format
			}

			$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data, $headerlen, 2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc)
			{
				return false; // Bad header CRC
			}

			$headerlen += 2;
		}

		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack("V", substr($data, -8, 4));
		$datacrc = $datacrc[1];
		$isize = unpack("V", substr($data, -4));
		$isize = $isize[1];

		// Perform the decompression:
		$bodylen = $len - $headerlen - 8;

		if ($bodylen < 1)
		{
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data, $headerlen, $bodylen);
		$data = "";
		if ($bodylen > 0)
		{
			switch ($method)
			{
				case 8 :
					// Currently the only supported compression method:
					$data = gzinflate($body);
					break;
				default :
					// Unknown compression method
					return false;
			}
		}
		else
		{
			//...
		}

		if ($isize != strlen($data) || crc32($data) != $datacrc)
		{
			// Bad format!  Length or CRC doesn't match!
			return false;
		}
		return $data;
	}
}
