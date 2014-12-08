<?php
/**
 * EmailFilter
 *
 * Provides tags for emails. Will obfuscate emails against bots.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

class EmailFilter extends DecodaFilter {

	/**
	 * Regex pattern.
	 */
	const EMAIL_PATTERN = '/(^|\n|\s)([-a-z0-9\.\+!]{1,64}+)@([-a-z0-9]+\.[a-z\.]+)/is';

	/**
	 * Configuration.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_config = array(
		'encrypt' => true
	);

	/**
	 * Supported tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array(
		'email' => array(
			'tag' => 'a',
			'type' => self::TYPE_INLINE,
			'allowed' => self::TYPE_INLINE,
			'pattern' => self::EMAIL_PATTERN,
			'testNoDefault' => true,
			'escapeAttributes' => false,
			'attributes' => array(
				'default' => self::EMAIL_PATTERN
			)
		),
		'mail' => array(
			'tag' => 'a',
			'type' => self::TYPE_INLINE,
			'allowed' => self::TYPE_INLINE,
			'pattern' => self::EMAIL_PATTERN,
			'testNoDefault' => true,
			'escapeAttributes' => false,
			'attributes' => array(
				'default' => self::EMAIL_PATTERN
			)
		)
	);

	/**
	 * Encrypt the email before parsing it within tags.
	 *
	 * @access public
	 * @param array $tag
	 * @param string $content
	 * @return string
	 */
	public function parse(array $tag, $content) {
		if (empty($tag['attributes']['default'])) {
			$email = $content;
			$default = false;
		} else {
			$email = $tag['attributes']['default'];
			$default = true;
		}

		$encrypted = '';

		if ($this->_config['encrypt']) {
			$length = strlen($email);

			if ($length > 0) {
				for ($i = 0; $i < $length; ++$i) {
					$encrypted .= '&#' . ord(substr($email, $i, 1)) . ';';
				}
			}
		} else {
			$encrypted = $email;
		}

		$tag['attributes']['href'] = 'mailto:' . $encrypted;

		if ($this->getParser()->config('shorthand')) {
			$tag['content'] = $this->message('mail');

			return '[' . parent::parse($tag, $content) . ']';
		}

		if (!$default) {
			$tag['content'] = $encrypted;
		}

		return parent::parse($tag, $content);
	}

}