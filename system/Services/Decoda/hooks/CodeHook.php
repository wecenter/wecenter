<?php
/**
 * CodeHook
 *
 * Encodes and decodes [code] blocks so that the inner content doesn't get processed.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

class CodeHook extends DecodaHook {

	/**
	 * Encode code blocks before parsing.
	 *
	 * @access public
	 * @param string $string
	 * @return mixed
	 */
	public function beforeParse($string) {
		return preg_replace_callback('/\[code(.*?)\](.*?)\[\/code\]/is', array($this, '_encodeCallback'), $string);
	}

	/**
	 * Decode code blocks after parsing.
	 *
	 * @access public
	 * @param string $string
	 * @return mixed
	 */
	public function afterParse($string) {
		return preg_replace_callback('/\<pre(.*?)>(.*?)\<\/pre>/is', array($this, '_decodeCallback'), $string);
	}

	/**
	 * Encode content using base64.
	 *
	 * @access protected
	 * @param array $matches
	 * @return string
	 */
	protected function _encodeCallback(array $matches) {
		return '[code' . $matches[1] . ']' . base64_encode($matches[2]) . '[/code]';
	}

	/**
	 * Decode content using base64.
	 *
	 * @access protected
	 * @param array $matches
	 * @return string
	 */
	protected function _decodeCallback(array $matches) {
		return '<pre' . $matches[1] . '>' . base64_decode($matches[2]) . '</pre>';
	}

}