<?php
/**
 * Decoda
 *
 * A lightweight lexical string parser for simple markup syntax.
 * Provides a very powerful filter and hook system to extend the parsing cycle.
 *
 * @version     3.5
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

// Constants
if (!defined('DECODA')) {
	define('DECODA', dirname(__FILE__) . '/');
}

if (!defined('DECODA_HOOKS')) {
	define('DECODA_HOOKS', DECODA . 'hooks/');
}

if (!defined('DECODA_CONFIG')) {
	define('DECODA_CONFIG', DECODA . 'config/');
}

if (!defined('DECODA_FILTERS')) {
	define('DECODA_FILTERS', DECODA . 'filters/');
}

if (!defined('DECODA_EMOTICONS')) {
	define('DECODA_EMOTICONS', DECODA . 'emoticons/');
}

// Includes
include_once DECODA . 'DecodaAbstract.php';
include_once DECODA . 'DecodaHook.php';
include_once DECODA . 'DecodaFilter.php';
include_once DECODA . 'DecodaTemplateEngineInterface.php';

class Decoda {

	/**
	 * Tag type constants.
	 */
	const TAG_NONE = 0;
	const TAG_OPEN = 1;
	const TAG_CLOSE = 2;

	/**
	 * Error type constants.
	 */
	const ERROR_ALL = 0;
	const ERROR_NESTING = 1;
	const ERROR_CLOSING = 2;
	const ERROR_SCOPE = 3;

	/**
	 * Extracted chunks of text and tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_chunks = array();

	/**
	 * Configuration.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_config = array(
		'open' => '[',
		'close' => ']',
		'disabled' => false,
		'shorthand' => false,
		'xhtml' => true,
		'escape' => true,
		'strict' => true,
		'locale' => 'en-us'
	);

	/**
	 * Logged errors for incorrectly nested nodes and types.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * List of all instantiated filter objects.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_filters = array();

	/**
	 * Mapping of tags to its filter object.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_filterMap = array();

	/**
	 * List of all instantiated hook objects.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_hooks = array();

	/**
	 * Message strings for localization purposes.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_messages = array();

	/**
	 * Children nodes.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_nodes = array();

	/**
	 * The parsed string.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_parsed = '';

	/**
	 * The raw string before parsing.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_string = '';

	/**
	 * List of tags from filters.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array();

	/**
	 * The used template engine
	 *
	 * @access protected
	 * @var TemplateEngineInterface
	 */
	protected $_templateEngine = null;

	/**
	 * Whitelist of tags to parse.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_whitelist = array();

	/**
	 * Store the text and single instance configuration.
	 *
	 * @access public
	 * @param string $string
	 */
	public function __construct($string = '') {
		spl_autoload_register(array($this, 'loadFile'));

		$this->_messages = json_decode(file_get_contents(DECODA_CONFIG . 'messages.json'), true);
		$this->reset($string, true);
	}

	/**
	 * Add additional filters.
	 *
	 * @access public
	 * @param DecodaFilter $filter
	 * @return Decoda
	 * @chainable
	 */
	public function addFilter(DecodaFilter $filter) {
		$filter->setParser($this);

		$class = str_replace('Filter', '', get_class($filter));
		$tags = $filter->tags();

		$this->_filters[$class] = $filter;
		$this->_tags = $tags + $this->_tags;

		foreach ($tags as $tag => $options) {
			$this->_filterMap[$tag] = $class;
		}

		$filter->setupHooks($this);

		return $this;
	}

	/**
	 * Add hooks that are triggered at specific events.
	 *
	 * @access public
	 * @param DecodaHook $hook
	 * @return Decoda
	 * @chainable
	 */
	public function addHook(DecodaHook $hook) {
		$hook->setParser($this);

		$class = str_replace('Hook', '', get_class($hook));

		$this->_hooks[$class] = $hook;

		$hook->setupFilters($this);

		return $this;
	}

	/**
	 * Return a specific configuration key value.
	 *
	 * @access public
	 * @param string $key
	 * @return mixed
	 */
	public function config($key) {
		return isset($this->_config[$key]) ? $this->_config[$key] : null;
	}

	/**
	 * Apply default filters and hooks if none are set.
	 *
	 * @access public
	 * @return Decoda
	 * @chainable
	 */
	public function defaults() {
		$this->addFilter(new DefaultFilter());
		$this->addFilter(new EmailFilter());
		$this->addFilter(new ImageFilter());
		$this->addFilter(new UrlFilter());
		$this->addFilter(new TextFilter());
		$this->addFilter(new BlockFilter());
		$this->addFilter(new VideoFilter());
		$this->addFilter(new CodeFilter());
		$this->addFilter(new QuoteFilter());
		$this->addFilter(new ListFilter());

		$this->addHook(new CodeHook());
		$this->addHook(new CensorHook());
		$this->addHook(new ClickableHook());
		$this->addHook(new EmoticonHook());

		return $this;
	}

	/**
	 * Toggle parsing.
	 *
	 * @access public
	 * @param boolean $status
	 * @return Decoda
	 * @chainable
	 */
	public function disable($status = true) {
		$this->_config['disabled'] = (bool) $status;

		return $this;
	}

	/**
	 * Disable all filters.
	 *
	 * @access public
	 * @return Decoda
	 * @chainable
	 */
	public function disableFilters() {
		$this->_filters = array();
		$this->_filterMap = array();

		$this->addFilter(new EmptyFilter());

		return $this;
	}

	/**
	 * Disable all hooks.
	 *
	 * @access public
	 * @return Decoda
	 * @chainable
	 */
	public function disableHooks() {
		$this->_hooks = array();

		$this->addHook(new EmptyHook());

		return $this;
	}

	/**
	 * Return the parsing errors.
	 *
	 * @access public
	 * @param int $type
	 * @return array
	 */
	public function getErrors($type = self::ERROR_ALL) {
		if ($type === self::ERROR_ALL) {
			return $this->_errors;
		}

		$clean = array();

		if (!empty($this->_errors)) {
			foreach ($this->_errors as $error) {
				if ($error['type'] === self::ERROR_NESTING) {
					$clean[] = $error;

				} else if ($error['type'] === self::ERROR_CLOSING) {
					$clean[] = $error;

				} else if ($error['type'] === self::ERROR_SCOPE) {
					$clean[] = $error;
				}
			}
		}

		return $clean;
	}

	/**
	 * Return a specific filter based on class name.
	 *
	 * @access public
	 * @param string $filter
	 * @return DecodaFilter
	 */
	public function getFilter($filter) {
		return isset($this->_filters[$filter]) ? $this->_filters[$filter] : null;
	}

	/**
	 * Return a filter based on its supported tag.
	 *
	 * @access public
	 * @param string $tag
	 * @return DecodaFilter
	 */
	public function getFilterByTag($tag) {
		return isset($this->_filterMap[$tag]) ? $this->_filters[$this->_filterMap[$tag]] : null;
	}

	/**
	 * Return all filters.
	 *
	 * @access public
	 * @return array
	 */
	public function getFilters() {
		return $this->_filters;
	}

	/**
	 * Return a specific hook based on class name.
	 *
	 * @access public
	 * @param string $hook
	 * @return DecodaHook
	 */
	public function getHook($hook) {
		return isset($this->_hooks[$hook]) ? $this->_hooks[$hook] : null;
	}

	/**
	 * Return all hooks.
	 *
	 * @access public
	 * @return array
	 */
	public function getHooks() {
		return $this->_hooks;
	}

	/**
	 * Returns the current used template engine.
	 * In case no engine is set the default php engine gonna be used.
	 *
	 * @access public
	 * @return DecodaTemplateEngineInterface
	 */
	public function getTemplateEngine() {
		if ($this->_templateEngine === null) {
			// Include just necessary in case the default php engine gonna be used.
			include_once DECODA . 'DecodaPhpEngine.php';
			$this->_templateEngine = new DecodaPhpEngine();
		}

		return $this->_templateEngine;
	}

	/**
	 * Autoload filters and hooks.
	 *
	 * @access public
	 * @param string $class
	 * @return void
	 */
	public function loadFile($class) {
		if (class_exists($class) || interface_exists($class)) {
			return;
		}

		if (strpos($class, 'Filter') !== false) {
			include_once DECODA_FILTERS . $class . '.php';

		} else if (strpos($class, 'Hook') !== false) {
			include_once DECODA_HOOKS . $class . '.php';
		}
	}

	/**
	 * Return a message string if it exists.
	 *
	 * @access public
	 * @param string $key
	 * @param array $vars
	 * @return string
	 */
	public function message($key, array $vars = array()) {
		$locale = $this->config('locale');
		$string = isset($this->_messages[$locale][$key]) ? $this->_messages[$locale][$key] : '';

		if (!empty($vars)) {
			foreach ($vars as $key => $value) {
				$string = str_replace('{' . $key . '}', $value, $string);
			}
		}

		return $string;
	}

	/**
	 * Inserts HTML line breaks before all newlines in a string.
	 * If the server is running PHP 5.2, the second parameter will be ignored.
	 *
	 * @access public
	 * @param string $string
	 * @param boolean $xhtml
	 * @return string
	 */
	public static function nl2br($string, $xhtml = true) {
		if (version_compare(PHP_VERSION, '5.3.0', '<')) {
			return nl2br($string);
		} else {
			return nl2br($string, $xhtml);
		}
	}

	/**
	 * Parse the node list by looping through each one, validating, applying filters, building and finally concatenating the string.
	 *
	 * @access public
	 * @param boolean $echo
	 * @return string
	 */
	public function parse($echo = false) {
		if (!empty($this->_parsed)) {
			if ($echo) {
				echo $this->_parsed;
			} else {
				return $this->_parsed;
			}
		}

		ksort($this->_hooks);

		if ($this->config('escape')) {
			$this->_string = str_replace(array('<', '>'), array('&lt;', '&gt;'), $this->_string);
		}

		$this->_string = $this->_trigger('beforeParse', $this->_string);

		if (strpos($this->_string, $this->config('open')) !== false && strpos($this->_string, $this->config('close')) !== false) {
			$this->_extractChunks();
			$this->_parsed = $this->_parse($this->_nodes);
		} else {
			$this->_parsed = self::nl2br($this->_string, $this->config('xhtml'));
		}

		$this->_parsed = $this->_trigger('afterParse', $this->_parsed);

		if ($echo) {
			echo $this->_parsed;
		} else {
			return $this->_parsed;
		}
	}

	/**
	 * Remove filter(s).
	 *
	 * @access public
	 * @param string|array $filters
	 * @return Decoda
	 * @chainable
	 */
	public function removeFilter($filters) {
		if (!is_array($filters)) {
			$filters = array($filters);
		}

		foreach ($filters as $filter) {
			unset($this->_filters[$filter]);

			foreach ($this->_filterMap as $tag => $fil) {
				if ($fil === $filter) {
					unset($this->_filterMap[$tag]);
				}
			}
		}

		return $this;
	}

	/**
	 * Remove hook(s).
	 *
	 * @access public
	 * @param string|array $hooks
	 * @return Decoda
	 * @chainable
	 */
	public function removeHook($hooks) {
		if (!is_array($hooks)) {
			$hooks = array($hooks);
		}

		foreach ($hooks as $hook) {
			unset($this->_hooks[$hook]);
		}

		return $this;
	}

	/**
	 * Reset the parser to a new string.
	 *
	 * @access public
	 * @param string $string
	 * @param boolean $flush
	 * @return Decoda
	 * @chainable
	 */
	public function reset($string, $flush = false) {
		$this->_chunks = array();
		$this->_nodes = array();
		$this->_whitelist = array();
		$this->_string = (string) $string;
		$this->_parsed = '';

		if ($flush) {
			$this->_filters = array();
			$this->_filterMap = array();
			$this->_hooks = array();
			$this->_tags = array();
		}

		$this->addFilter(new EmptyFilter());

		return $this;
	}

	/**
	 * Change the open/close markup brackets.
	 *
	 * @access public
	 * @param string $open
	 * @param string $close
	 * @return Decoda
	 * @throws Exception
	 * @chainable
	 */
	public function setBrackets($open, $close) {
		if (empty($open) || empty($close)) {
			throw new Exception('Both the open and close brackets are required.');
		}

		$this->_config['open'] = (string) $open;
		$this->_config['close'] = (string) $close;

		return $this;
	}

	/**
	 * Toggle XSS escaping.
	 *
	 * @access public
	 * @param boolean $status
	 * @return Decoda
	 * @chainable
	 */
	public function setEscaping($status = true) {
		$this->_config['escape'] = (bool) $status;

		return $this;
	}

	/**
	 * Set the locale.
	 *
	 * @access public
	 * @param string $locale
	 * @return Decoda
	 * @throws Exception
	 * @chainable
	 */
	public function setLocale($locale) {
		if (empty($this->_messages[$locale])) {
			throw new Exception(sprintf('Localized strings for %s do not exist.', $locale));
		}

		$this->_config['locale'] = $locale;

		return $this;
	}

	/**
	 * Toggle shorthand syntax.
	 *
	 * @access public
	 * @param boolean $status
	 * @return Decoda
	 * @chainable
	 */
	public function setShorthand($status = true) {
		$this->_config['shorthand'] = (bool) $status;

		return $this;
	}

	/**
	 * Toggle strict parsing.
	 *
	 * @access public
	 * @param boolean $strict
	 * @return Decoda
	 * @chainable
	 */
	public function setStrict($strict = true) {
		$this->_config['strict'] = (bool) $strict;

		return $this;
	}

	/**
	 * Sets the template engine which gonna be used for all tags with templates.
	 *
	 * @access public
	 * @param DecodaTemplateEngineInterface $templateEngine
	 * @return Decoda
	 * @chainable
	 */
	public function setTemplateEngine(DecodaTemplateEngineInterface $templateEngine) {
		$this->_templateEngine = $templateEngine;

		return $this;
	}

	/**
	 * Toggle XHTML.
	 *
	 * @access public
	 * @param boolean $status
	 * @return Decoda
	 * @chainable
	 */
	public function setXhtml($status = true) {
		$this->_config['xhtml'] = (bool) $status;

		return $this;
	}

	/**
	 * Add tags to the whitelist.
	 *
	 * @access public
	 * @return Decoda
	 * @chainable
	 */
	public function whitelist() {
		$args = func_get_args();

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$this->_whitelist += array_map('strtolower', $args);
		$this->_whitelist = array_filter($this->_whitelist);

		return $this;
	}

	/**
	 * Determine if the string is an open or closing tag. If so, parse out the attributes.
	 *
	 * @access protected
	 * @param string $string
	 * @return array
	 */
	protected function _buildTag($string) {
		$disabled = $this->config('disabled');
		$tag = array(
			'tag' => '',
			'text' => $string,
			'attributes' => array()
		);

		// Closing tag
		if (substr($string, 1, 1) === '/') {
			$tag['tag'] = strtolower(substr($string, 2, strlen($string) - 3));
			$tag['type'] = self::TAG_CLOSE;

			if (!isset($this->_tags[$tag['tag']])) {
				return false;
			}

		// Opening tag
		} else {
			if (strpos($string, ' ') && (strpos($string, '=') === false)) {
				return false;
			}

			// Find tag
			$oe = preg_quote($this->config('open'));
			$ce = preg_quote($this->config('close'));

			if (preg_match('/' . $oe . '([a-z0-9]+)(.*?)' . $ce . '/i', $string, $matches)) {
				$tag['type'] = self::TAG_OPEN;
				$tag['tag'] = strtolower($matches[1]);
			}

			if (!isset($this->_tags[$tag['tag']])) {
				return false;
			}

			// Find attributes
			if (!$disabled) {
				$found = array();

				preg_match_all('/([a-z]+)=\"(.*?)\"/i', $string, $matches, PREG_SET_ORDER);

				if (!empty($matches)) {
					foreach ($matches as $match) {
						$found[$match[1]] = $match[2];
					}
				}

				// Find attributes that aren't surrounded by quotes
				if (!$this->config('strict')) {
					preg_match_all('/([a-z]+)=([^\s\]]+)/i', $string, $matches, PREG_SET_ORDER);

					if (!empty($matches)) {
						foreach ($matches as $match) {
							if (!isset($found[$match[1]])) {
								$found[$match[1]] = $match[2];
							}
						}
					}
				}

				if (!empty($found)) {
					$source = $this->_tags[$tag['tag']];

					foreach ($found as $key => $value) {
						$key = strtolower($key);
						$value = trim(trim($value), '"');

						if ($key === $tag['tag']) {
							$key = 'default';
						}

						if (isset($source['alias'][$key])) {
							$key = $source['alias'][$key];
						}

						if (isset($source['attributes'][$key])) {
							$pattern = $source['attributes'][$key];

							if (is_array($pattern)) {
								if (preg_match($pattern[0], $value)) {
									$tag['attributes'][$key] = str_replace('{' . $key . '}', $value, $pattern[1]);
								}
							} else {
								if (preg_match($pattern, $value)) {
									$tag['attributes'][$key] = $value;
								}
							}
						}
					}
				}
			}
		}

		if ($disabled || (!empty($this->_whitelist) && !in_array($tag['tag'], $this->_whitelist))) {
			$tag['type'] = self::TAG_NONE;
			$tag['text'] = '';
		}

		return $tag;
	}

	/**
	 * Clean the chunk list by verifying that open and closing tags are nested correctly.
	 *
	 * @access protected
	 * @param array $chunks
	 * @param array $wrapper
	 * @return string
	 */
	protected function _cleanChunks(array $chunks, array $wrapper = array()) {
		$clean = array();
		$openTags = array();
		$prevChunk = array();
		$disallowed = array();
		$parents = array();
		$depths = array();
		$count = count($chunks);
		$tag = '';
		$i = 0;

		if (!empty($wrapper)) {
			$parent = $this->getFilterByTag($wrapper['tag'])->tag($wrapper['tag']);
			$root = false;
		} else {
			$parent = $this->getFilter('Empty')->tag('root');
			$root = true;
		}

		while ($i < $count) {
			$chunk = $chunks[$i];
			$tag = isset($chunk['tag']) ? $chunk['tag'] : '';

			switch ($chunk['type']) {
				case self::TAG_NONE:
					if (empty($parent['children'])) {
						if (!empty($prevChunk) && $prevChunk['type'] === self::TAG_NONE) {
							$chunk['text'] = $prevChunk['text'] . $chunk['text'];
							array_pop($clean);
						}

						$clean[] = $chunk;
					}
				break;

				case self::TAG_OPEN:
					if ($parent['maxChildDepth'] >= 0 && !isset($depths[$tag])) {
						$depths[$tag] = 1;
						$parent['currentDepth'] = $depths[$tag];

					} else if (isset($depths[$tag])) {
						$depths[$tag] += 1;
						$parent['currentDepth'] = $depths[$tag];
					}

					if ($this->_isAllowed($parent, $tag)) {
						$prevParent = $parent;
						$parents[] = $parent;
						$parent = $this->getFilterByTag($tag)->tag($tag);

						if ($prevParent['preserveTags']) {
							$chunk['type'] = self::TAG_NONE;
							$parent['preserveTags'] = true;
						}

						$clean[] = $chunk;

						if ($root) {
							$openTags[] = array('tag' => $tag, 'index' => $i);
						}
					} else {
						$disallowed[] = array('tag' => $tag, 'index' => $i);
					}
				break;

				case self::TAG_CLOSE:
					// Reduce depth
					if (isset($depths[$tag])) {
						$depths[$tag] -= 1;
					}

					// If something is not allowed, skip the close tag
					if (!empty($disallowed)) {
						$last = end($disallowed);

						if ($last['tag'] === $tag) {
							array_pop($disallowed);
							continue;
						}
					}

					// Return to previous parent before allowing
					if (!empty($parents)) {
						$parent = array_pop($parents);
					}

					// Now check for open tags if the tag is allowed
					if ($this->_isAllowed($parent, $tag)) {
						if ($parent['preserveTags']) {
							$chunk['type'] = self::TAG_NONE;
						}

						$clean[] = $chunk;

						if ($root && !empty($openTags)) {
							$last = end($openTags);

							if ($last['tag'] === $tag) {
								array_pop($openTags);
							} else {
								while (!empty($openTags)) {
									$last = array_pop($openTags);

									if ($last['tag'] !== $tag) {
										$this->_errors[] = array(
											'type' => self::ERROR_NESTING,
											'tag' => $last['tag']
										);

										unset($clean[$last['index']]);
									}
								}
							}
						}
					}
				break;
			}

			$i++;
			$prevChunk = $chunk;
		}

		// Remove any unclosed tags
		while (!empty($openTags)) {
			$last = array_pop($openTags);

			$this->_errors[] = array(
				'type' => self::ERROR_CLOSING,
				'tag' => $last['tag']
			);

			unset($clean[$last['index']]);
		}

		return array_values($clean);
	}

	/**
	 * Scan the string stack and extract any tags and chunks of text that were detected.
	 *
	 * @access protected
	 * @return void
	 */
	protected function _extractChunks() {
		$str = $this->_string;
		$strPos = 0;
		$strLength = strlen($str);
		$openBracket = $this->config('open');
		$closeBracket = $this->config('close');

		while ($strPos < $strLength) {
			$tag = array();
			$openPos = strpos($str, $openBracket, $strPos);

			if ($openPos === false) {
				$openPos = $strLength;
				$nextOpenPos = $strLength;
			}

			if ($openPos + 1 > $strLength) {
				$nextOpenPos = $strLength;
			} else {
				$nextOpenPos = strpos($str, $openBracket, $openPos + 1);

				if ($nextOpenPos === false) {
					$nextOpenPos = $strLength;
				}
			}

			$closePos = strpos($str, $closeBracket, $strPos);

			if ($closePos === false) {
				$closePos = $strLength + 1;
			}

			// Possible tag found, lets look
			if ($openPos === $strPos) {

				// Child open tag before closing tag
				if ($nextOpenPos < $closePos) {
					$newPos = $nextOpenPos;
					$tag['text'] = substr($str, $strPos, ($nextOpenPos - $strPos));
					$tag['type'] = self::TAG_NONE;

				// Tag?
				} else {
					$newPos = $closePos + 1;
					$newTag = $this->_buildTag(substr($str, $strPos, (($closePos - $strPos) + 1)));

					// Valid tag
					if ($newTag !== false) {
						$tag = $newTag;

					// Not a valid tag
					} else {
						$tag['text'] = substr($str, $strPos, $closePos - $strPos + 1);
						$tag['type'] = self::TAG_NONE;
					}
				}

			// No tag, just text
			} else {
				$newPos = $openPos;

				$tag['text'] = substr($str, $strPos, ($openPos - $strPos));
				$tag['type'] = self::TAG_NONE;
			}

			// Join consecutive text elements
			if ($tag['type'] === self::TAG_NONE && isset($prev) && $prev['type'] === self::TAG_NONE) {
				$tag['text'] = $prev['text'] . $tag['text'];
				array_pop($this->_chunks);
			}

			$this->_chunks[] = $tag;
			$prev = $tag;
			$strPos = $newPos;
		}

		$this->_nodes = $this->_extractNodes($this->_chunks);
	}

	/**
	 * Convert the chunks into a child parent hierarchy of nodes.
	 *
	 * @access protected
	 * @param array $chunks
	 * @param array $wrapper
	 * @return array
	 */
	protected function _extractNodes(array $chunks, array $wrapper = array()) {
		$chunks = $this->_cleanChunks($chunks, $wrapper);
		$nodes = array();
		$tag = array();
		$openIndex = -1;
		$openCount = -1;
		$closeIndex = -1;
		$closeCount = -1;
		$count = count($chunks);
		$i = 0;

		while ($i < $count) {
			$chunk = $chunks[$i];

			if ($chunk['type'] === self::TAG_NONE && empty($tag)) {
				$nodes[] = $chunk['text'];

			} else if ($chunk['type'] === self::TAG_OPEN) {
				$openCount++;

				if (empty($tag)) {
					$openIndex = $i;
					$tag = $chunk;
				}

			} else if ($chunk['type'] === self::TAG_CLOSE) {
				$closeCount++;

				if ($openCount === $closeCount && $chunk['tag'] === $tag['tag']) {
					$closeIndex = $i;
					$index = ($closeIndex - $openIndex);
					$tag = array();

					// Only reduce if not last index
					if ($index !== $count) {
						$index = $index - 1;
					}

					// Slice a section of the array if the correct closing tag is found
					$node = $chunks[$openIndex];
					$node['children'] = $this->_extractNodes(array_slice($chunks, ($openIndex + 1), $index), $chunks[$openIndex]);
					$nodes[] = $node;
				}
			}

			$i++;
		}

		return $nodes;
	}

	/**
	 * Validate that the following child can be nested within the parent.
	 *
	 * @access protected
	 * @param array $parent
	 * @param string $tag
	 * @return boolean
	 */
	protected function _isAllowed($parent, $tag) {
		$filter = $this->getFilterByTag($tag);

		if (!$filter) {
			return false;
		}

		$child = $filter->tag($tag);

		// Remove children after a certain nested depth
		if (isset($parent['currentDepth']) && $parent['currentDepth'] > $parent['maxChildDepth']) {
			return false;

		// Children that can only be within a certain parent
		} else if (!empty($child['parent']) && !in_array($parent['key'], $child['parent'])) {
			return false;

		// Parents that can only have direct descendant children
		} else if (!empty($parent['children']) && !in_array($child['key'], $parent['children'])) {
			return false;

		// Block element that accepts both types
		} else if ($parent['allowed'] === DecodaFilter::TYPE_BOTH) {
			return true;

		// Inline elements can go within everything
		} else if (($parent['allowed'] === DecodaFilter::TYPE_INLINE || $parent['allowed'] === DecodaFilter::TYPE_BLOCK) && $child['type'] === DecodaFilter::TYPE_INLINE) {
			return true;
		}

		$this->_errors[] = array(
			'type' => self::ERROR_SCOPE,
			'parent' => $parent['key'],
			'child' => $child['key']
		);

		return false;
	}

	/**
	 * Cycle through the nodes and parse the string with the appropriate filter.
	 *
	 * @access protected
	 * @param array $nodes
	 * @param array $wrapper
	 * @return string
	 */
	protected function _parse(array $nodes, array $wrapper = array()) {
		$parsed = '';
		$xhtml = $this->config('xhtml');

		if (empty($nodes)) {
			return $parsed;
		}

		foreach ($nodes as $node) {
			if (is_string($node)) {
				if (empty($wrapper)) {
					$parsed .= self::nl2br($node, $xhtml);
				} else {
					$parsed .= $node;
				}
			} else {
				$parsed .= $this->getFilterByTag($node['tag'])->parse($node, $this->_parse($node['children'], $node));
			}
		}

		return $parsed;
	}

	/**
	 * Trigger all hooks at an event specified by the method name.
	 *
	 * @access protected
	 * @param string $method
	 * @param string $content
	 * @return string
	 */
	protected function _trigger($method, $content) {
		if (!empty($this->_hooks)) {
			foreach ($this->_hooks as $hook) {
				if (method_exists($hook, $method)) {
					$content = $hook->{$method}($content);
				}
			}
		}

		return $content;
	}

}