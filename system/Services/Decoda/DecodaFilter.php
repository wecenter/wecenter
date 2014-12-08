<?php
/**
 * DecodaFilter
 *
 * A filter defines the list of tags and its associative markup to parse out of a string.
 * Supports a wide range of parameters to customize the output of each tag.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

/**
 * @key					- (string) Decoda tag
 * @tag					- (string) HTML replacement tag
 * @template			- (string) Template file to use for rendering
 * @pattern				- (string) Regex pattern that the content or default attribute must pass
 * @testNoDefault		- (boolean) Will only test the pattern on the content if the default attribute doesn't exist
 * @type				- (constant) Type of HTML element: block or inline
 * @allowed				- (constant) What types of elements are allowed to be nested
 * @attributes			- (array) Custom attributes to parse out of the Decoda markup
 * @map					- (array) Map parsed attributes to different names
 * @html				- (array) Custom HTML attributes to append to the parsed tag
 * @alias				- (array) Map attribute names to another attribute name
 * @lineBreaks			- (boolean) Convert linebreaks within the content body
 * @autoClose			- (boolean) HTML tag is self closing
 * @preserveTags		- (boolean) Will not convert nested Decoda markup within this tag
 * @escapeAttributes	- (boolean) Escape HTML entities within the parsed attributes
 * @maxChildDepth		- (integer) Max depth for nested children of the same tag (-1 to disable)
 * @parent				- (array) List of Decoda keys that this tag can only be a direct child of
 * @children			- (array) List of Decoda keys for all the tags that can only be a direct descendant
 */
abstract class DecodaFilter extends DecodaAbstract {

	/**
	 * Type constants.
	 *
	 * TYPE_NONE	- Will not accept block or inline (for validating)
	 * TYPE_INLINE	- Inline element that can only contain child inlines
	 * TYPE_BLOCK	- Block element that can contain both inline and block
	 * TYPE_BOTH	- Will accept either type (for validating)
	 */
	const TYPE_NONE = 0;
	const TYPE_INLINE = 1;
	const TYPE_BLOCK = 2;
	const TYPE_BOTH = 3;

	/**
	 * Newline and carriage return formatting.
	 *
	 * NL_REMOVE	- Will be removed
	 * NL_PRESERVE	- Will be preserved as \n and \r
	 * NL_CONVERT	- Will be converted to <br> tags
	 */
	const NL_REMOVE = 0;
	const NL_PRESERVE = 1;
	const NL_CONVERT = 2;

	/**
	 * Supported tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array();

	/**
	 * Return a message string from the parser.
	 *
	 * @access public
	 * @param string $key
	 * @param array $vars
	 * @return string
	 */
	public function message($key, array $vars = array()) {
		return $this->getParser()->message($key, $vars);
	}

	/**
	 * Parse the node and its content into an HTML tag.
	 *
	 * @access public
	 * @param array $tag
	 * @param string $content
	 * @return string
	 */
	public function parse(array $tag, $content) {
		$setup = $this->tag($tag['tag']);
		$xhtml = $this->getParser()->config('xhtml');
		$content = trim($content);

		if (empty($setup)) {
			return;
		}

		// If content doesn't match the pattern, don't wrap in a tag
		if (!empty($setup['pattern'])) {
			if ($setup['testNoDefault'] && !isset($tag['attributes']['default']) && !preg_match($setup['pattern'], $content)) {
				return sprintf('(Invalid %s)', $tag['tag']);
			}
		}

		// Add linebreaks
		switch ($setup['lineBreaks']) {
			case self::NL_REMOVE:
				$content = str_replace(array("\n", "\r"), "", $content);
			break;
			case self::NL_CONVERT:
				$content = Decoda::nl2br($content, $xhtml);
			break;
		}

		// Format attributes
		$attributes = array();
		$attr = '';

		if (!empty($tag['attributes'])) {
			foreach ($tag['attributes'] as $key => $value) {
				if (isset($setup['map'][$key])) {
					$key = $setup['map'][$key];
				}

				if ($key === 'default' || substr($value, 0, 11) === 'javascript:') {
					continue;
				}

				if ($setup['escapeAttributes']) {
					$attributes[$key] = htmlentities($value, ENT_QUOTES, 'UTF-8');
				} else {
					$attributes[$key] = $value;
				}
			}
		}

		if (!empty($setup['html'])) {
			$attributes += $setup['html'];
		}

		// Use a template if it exists
		if (!empty($setup['template'])) {
			$tag['attributes'] = $attributes;

			$templateEngine = $this->getParser()->getTemplateEngine();
			$templateEngine->setFilter($this);

			$parsed = $templateEngine->render($tag, $content);

			if ($setup['lineBreaks'] !== self::NL_PRESERVE) {
				$parsed = str_replace(array("\n", "\r"), "", $parsed);
			}

			return $parsed;
		}

		foreach ($attributes as $key => $value) {
			$attr .= ' ' . $key . '="' . $value . '"';
		}

		// Build HTML tag
		$html = $setup['tag'];

		if (is_array($html)) {
			$html = $html[$xhtml];
		}

		$parsed = '<' . $html . $attr;

		if ($setup['autoClose']) {
			$parsed .= $xhtml ? '/>' : '>';
		} else {
			$parsed .= '>' . (!empty($tag['content']) ? $tag['content'] : $content) . '</' . $html . '>';
		}

		return $parsed;
	}

	/**
	 * Add any hook dependencies.
	 *
	 * @access public
	 * @param Decoda $decoda
	 * @return void
	 */
	public function setupHooks(Decoda $decoda) {
		return;
	}

	/**
	 * Return a tag if it exists, and merge with defaults.
	 *
	 * @access public
	 * @param string $tag
	 * @return array
	 */
	public function tag($tag) {
		$defaults = array(
			// Meta
			'key' => $tag,
			'tag' => '',
			'template' => '',
			'pattern' => '',
			'testNoDefault' => false,
			'type' => self::TYPE_BLOCK,
			'allowed' => self::TYPE_BOTH,

			// Attributes
			'attributes' => array(),
			'map' => array(),
			'html' => array(),
			'alias' => array(),

			// Processes
			'lineBreaks' => self::NL_CONVERT,
			'autoClose' => false,
			'preserveTags' => false,
			'escapeAttributes' => true,
			'maxChildDepth' => -1,

			// Hierarchy
			'parent' => array(),
			'children' => array()
		);

		if (isset($this->_tags[$tag])) {
			return $this->_tags[$tag] + $defaults;
		}

		return $defaults;
	}

	/**
	 * Return all tags.
	 *
	 * @access public
	 * @return array
	 */
	public function tags() {
		return $this->_tags;
	}

}