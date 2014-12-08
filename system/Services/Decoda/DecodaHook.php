<?php
/**
 * DecodaHook
 *
 * A hook allows you to inject functionality during certain events in the parsing cycle.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

abstract class DecodaHook extends DecodaAbstract {

	/**
	 * Process the content after the parsing has finished.
	 *
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public function afterParse($content) {
		return $content;
	}

	/**
	 * Process the content before the parsing begins.
	 *
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public function beforeParse($content) {
		return $content;
	}

	/**
	 * Add any filter dependencies.
	 *
	 * @access public
	 * @param Decoda $decoda
	 * @return void
	 */
	public function setupFilters(Decoda $decoda) {
		return;
	}

}