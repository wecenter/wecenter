<?php
/**
 * DecodaTemplateEngineInterface
 *
 * This interface represents the rendering engine for tags that use a template.
 * It contains the path were the templates are located and the logic to render these templates.
 *
 * @author      Miles Johnson - http://milesj.me
 * @author      Sean C. Koop - sean.koop@icans-gmbh.com
 * @copyright   Copyright 2006-2012, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

interface DecodaTemplateEngineInterface {

	/**
	 * Return the current filter.
	 *
	 * @access public
	 * @return DecodaFilter
	 */
	public function getFilter();

	/**
	 * Returns the path of the tag templates.
	 *
	 * @access public
	 * @return string
	 */
	public function getPath();

	/**
	 * Renders the tag by using the defined templates.
	 *
	 * @access public
	 * @param array $tag
	 * @param string $content
	 * @return string
	 * @throws Exception
	 */
	public function render(array $tag, $content);

	/**
	 * Sets the current used filter.
	 *
	 * @access public
	 * @param DecodaFilter $filter
	 * @return void
	 */
	public function setFilter(DecodaFilter $filter);

	/**
	 * Sets the path to the tag templates.
	 *
	 * @access public
	 * @param string $path
	 * @return void
	 */
	public function setPath($path);

}
