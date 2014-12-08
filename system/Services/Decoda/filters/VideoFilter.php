<?php
/**
 * VideoFilter
 *
 * Provides the tag for videos. Only a few video services are supported.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

class VideoFilter extends DecodaFilter {

	/**
	 * Regex pattern.
	 */
	const VIDEO_PATTERN = '/^[-_a-z0-9]+$/is';

	/**
	 * Supported tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array(
		'video' => array(
			'template' => 'video',
			'type' => self::TYPE_BLOCK,
			'allowed' => self::TYPE_NONE,
			'pattern' => self::VIDEO_PATTERN,
			'attributes' => array(
				'default' => '/[a-z0-9]+/i',
				'size' => '/small|medium|large/i'
			)
		)
	);

	/**
	 * Video formats.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_formats = array(
		'youtube' => array(
			'small' => array(560, 315),
			'medium' => array(640, 360),
			'large' => array(853, 480),
			'player' => 'iframe',
			'path' => 'http://www.youtube.com/embed/{id}'
		),
		'vimeo' => array(
			'small' => array(400, 225),
			'medium' => array(550, 309),
			'large' => array(700, 394),
			'player' => 'iframe',
			'path' => 'http://player.vimeo.com/video/{id}'
		),
		'liveleak' => array(
			'small' => array(450, 370),
			'medium' => array(600, 493),
			'large' => array(750, 617),
			'player' => 'embed',
			'path' => 'http://liveleak.com/e/{id}'
		),
		'veoh' => array(
			'small' => array(410, 341),
			'medium' => array(610, 507),
			'large' => array(810, 674),
			'player' => 'embed',
			'path' => 'http://veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.3.1004&permalinkId={id}&player=videodetailsembedded&videoAutoPlay=0&id=anonymous'
		),
		'dailymotion' => array(
			'small' => array(320, 240),
			'medium' => array(480, 360),
			'large' => array(560, 420),
			'player' => 'embed',
			'path' => 'http://dailymotion.com/swf/video/{id}&additionalInfos=0&autoPlay=0'
		),
		'myspace' => array(
			'small' => array(325, 260),
			'medium' => array(425, 340),
			'large' => array(525, 420),
			'player' => 'embed',
			'path' => 'http://mediaservices.myspace.com/services/media/embed.aspx/m={id},t=1,mt=video'
		),
		'wegame' => array(
			'small' => array(325, 260),
			'medium' => array(480, 387),
			'large' => array(525, 420),
			'player' => 'embed',
			'path' => 'http://wegame.com/static/flash/player.swf?xmlrequest=http://www.wegame.com/player/video/{id}&embedPlayer=true'
		),
		'collegehumor' => array(
			'small' => array(300, 169),
			'medium' => array(450, 254),
			'large' => array(600, 338),
			'player' => 'embed',
			'path' => 'http://collegehumor.com/moogaloop/moogaloop.swf?clip_id={id}&use_node_id=true&fullscreen=1'
		)
	);

	/**
	 * Custom build the HTML for videos.
	 *
	 * @access public
	 * @param array $tag
	 * @param string $content
	 * @return string
	 */
	public function parse(array $tag, $content) {
		$provider = $tag['attributes']['default'];
		$size = strtolower(isset($tag['attributes']['size']) ? $tag['attributes']['size'] : 'medium');

		if (empty($this->_formats[$provider])) {
			return sprintf('(Invalid %s video code)', $provider);
		}

		$video = $this->_formats[$provider];
		$size = isset($video[$size]) ? $video[$size] : $video['medium'];

		$tag['attributes']['width'] = $size[0];
		$tag['attributes']['height'] = $size[1];
		$tag['attributes']['player'] = $video['player'];
		$tag['attributes']['url'] = str_replace(array('{id}', '{width}', '{height}'), array($content, $size[0], $size[1]), $video['path']);

		return parent::parse($tag, $content);
	}

}