<?php
/**
 * Ganon single file version - PHP5+ version
 * Generated on 11 Nov 2012
 *
 * @author Niels A.D.
 * @package Ganon
 * @link http://code.google.com/p/ganon/
 * @license http://dev.perl.org/licenses/artistic.html Artistic License
 */

//START ganon.php
function str_get_dom($str, $return_root = true) {
	$a = new HTML_Parser_HTML5($str);
	return (($return_root) ? $a->root : $a);
}
function file_get_dom($file, $return_root = true, $use_include_path = false, $context = null) {
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
		$f = file_get_contents($file, $use_include_path, $context);
	else {
		if ($context !== null)
			trigger_error('Context parameter not supported in this PHP version');
		$f = file_get_contents($file, $use_include_path);
	}
	return (($f === false) ? false : str_get_dom($f, $return_root));
}
function dom_format(&$root, $options = array()) {
	$formatter = new HTML_Formatter($options);
	return $formatter->format($root);
}
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	function str_split($string) {
		$res = array();
		$size = strlen($string);
		for ($i = 0; $i < $size; $i++) {
			$res[] = $string[$i];
		}
		return $res;
	}
}
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	function array_fill_keys($keys, $value) {
		$res = array();
		foreach($keys as $k) {
			$res[$k] = $value;
		}
		return $res;
	}
}
//END ganon.php

//START gan_tokenizer.php
class Tokenizer_Base {
	const TOK_NULL = 0;
	const TOK_UNKNOWN = 1;
	const TOK_WHITESPACE = 2;
	const TOK_IDENTIFIER = 3;
	var $doc = '';
	var $size = 0;
	var $pos = 0;
	var $line_pos = array(0, 0);
	var $token = self::TOK_NULL;
	var $token_start = null;
	var $whitespace = " \t\n\r\0\x0B";
	var $identifiers = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_';
	var $custom_char_map = array();
	var $char_map = array();
	var $errors = array();
	function __construct($doc = '', $pos = 0) {
		$this->setWhitespace($this->whitespace);
		$this->setIdentifiers($this->identifiers);
		$this->setDoc($doc, $pos);
	}
	function setDoc($doc, $pos = 0) {
		$this->doc = $doc;
		$this->size = strlen($doc);
		$this->setPos($pos);
	}
	function getDoc() {
		return $this->doc;
	}
	function setPos($pos = 0) {
		$this->pos = $pos - 1;
		$this->line_pos = array(0, 0);
		$this->next();
	}
	function getPos() {
		return $this->pos;
	}
	function getLinePos() {
		return array($this->line_pos[0], $this->pos - $this->line_pos[1]);
	}
	function getToken() {
		return $this->token;
	}
	function getTokenString($start_offset = 0, $end_offset = 0) {
		$token_start = ((is_int($this->token_start)) ? $this->token_start : $this->pos) + $start_offset;
		$len = $this->pos - $token_start + 1 + $end_offset;
		return (($len > 0) ? substr($this->doc, $token_start, $len) : '');
	}
	function setWhitespace($ws) {
		if (is_array($ws)) {
			$this->whitespace = array_fill_keys(array_values($ws), true);
			$this->buildCharMap();
		} else {
			$this->setWhiteSpace(str_split($ws));
		}
	}
	function getWhitespace($as_string = true) {
		$ws = array_keys($this->whitespace);
		return (($as_string) ? implode('', $ws) : $ws);
	}
	function setIdentifiers($ident) {
		if (is_array($ident)) {
			$this->identifiers = array_fill_keys(array_values($ident), true);
			$this->buildCharMap();
		} else {
			$this->setIdentifiers(str_split($ident));
		}
	}
	function getIdentifiers($as_string = true) {
		$ident = array_keys($this->identifiers);
		return (($as_string) ? implode('', $ident) : $ident);
	}
	function mapChar($char, $map) {
		$this->custom_char_map[$char] = $map;
		$this->buildCharMap();
	}
	function unmapChar($char) {
		unset($this->custom_char_map[$char]);
		$this->buildCharMap();
	}
	protected function buildCharMap() {
		$this->char_map = $this->custom_char_map;
		if (is_array($this->whitespace)) {
			foreach($this->whitespace as $w => $v) {
				$this->char_map[$w] = 'parse_whitespace';
			}
		}
		if (is_array($this->identifiers)) {
			foreach($this->identifiers as $i => $v) {
				$this->char_map[$i] = 'parse_identifier';
			}
		}
	}
	function addError($error) {
		$this->errors[] = htmlentities($error.' at '.($this->line_pos[0] + 1).', '.($this->pos - $this->line_pos[1] + 1).'!');
	}
	protected function parse_linebreak() {
		if($this->doc[$this->pos] === "\r") {
			++$this->line_pos[0];
			if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === "\n")) {
				++$this->pos;
			}
			$this->line_pos[1] = $this->pos;
		} elseif($this->doc[$this->pos] === "\n") {
			++$this->line_pos[0];
			$this->line_pos[1] = $this->pos;
		}
	}
	protected function parse_whitespace() {
		$this->token_start = $this->pos;
		while(++$this->pos < $this->size) {
			if (!isset($this->whitespace[$this->doc[$this->pos]])) {
				break;
			} else {
				$this->parse_linebreak();
			}
		}
		--$this->pos;
		return self::TOK_WHITESPACE;
	}
	protected function parse_identifier() {
		$this->token_start = $this->pos;
		while((++$this->pos < $this->size) && isset($this->identifiers[$this->doc[$this->pos]])) {}
		--$this->pos;
		return self::TOK_IDENTIFIER;
	}
	function next() {
		$this->token_start = null;
		if (++$this->pos < $this->size) {
			if (isset($this->char_map[$this->doc[$this->pos]])) {
				if (is_string($this->char_map[$this->doc[$this->pos]])) {
					return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
				} else {
					return ($this->token = $this->char_map[$this->doc[$this->pos]]);
				}
			} else {
				return ($this->token = self::TOK_UNKNOWN);
			}
		} else {
			return ($this->token = self::TOK_NULL);
		}
	}
	function next_no_whitespace() {
		$this->token_start = null;
		while (++$this->pos < $this->size) {
			if (!isset($this->whitespace[$this->doc[$this->pos]])) {
				if (isset($this->char_map[$this->doc[$this->pos]])) {
					if (is_string($this->char_map[$this->doc[$this->pos]])) {
						return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
					} else {
						return ($this->token = $this->char_map[$this->doc[$this->pos]]);
					}
				} else {
					return ($this->token = self::TOK_UNKNOWN);
				}
			} else {
				$this->parse_linebreak();
			}
		}
		return ($this->token = self::TOK_NULL);
	}
	function next_search($characters, $callback = true) {
		$this->token_start = $this->pos;
		if (!is_array($characters)) {
			$characters = array_fill_keys(str_split($characters), true);
		}
		while(++$this->pos < $this->size) {
			if (isset($characters[$this->doc[$this->pos]])) {
				if ($callback && isset($this->char_map[$this->doc[$this->pos]])) {
					if (is_string($this->char_map[$this->doc[$this->pos]])) {
						return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
					} else {
						return ($this->token = $this->char_map[$this->doc[$this->pos]]);
					}
				} else {
					return ($this->token = self::TOK_UNKNOWN);
				}
			} else {
				$this->parse_linebreak();
			}
		}
		return ($this->token = self::TOK_NULL);
	}
	function next_pos($needle, $callback = true) {
		$this->token_start = $this->pos;
		if (($this->pos < $this->size) && (($p = stripos($this->doc, $needle, $this->pos + 1)) !== false)) {
			$len = $p - $this->pos - 1;
			if ($len > 0) {
				$str = substr($this->doc, $this->pos + 1, $len);
				if (($l = strrpos($str, "\n")) !== false) {
					++$this->line_pos[0];
					$this->line_pos[1] = $l + $this->pos + 1;
					$len -= $l;
					if ($len > 0) {
						$str = substr($str, 0, -$len);
						$this->line_pos[0] += substr_count($str, "\n");
					}
				}
			}
			$this->pos = $p;
			if ($callback && isset($this->char_map[$this->doc[$this->pos]])) {
				if (is_string($this->char_map[$this->doc[$this->pos]])) {
					return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
				} else {
					return ($this->token = $this->char_map[$this->doc[$this->pos]]);
				}
			} else {
				return ($this->token = self::TOK_UNKNOWN);
			}
		} else {
			$this->pos = $this->size;
			return ($this->token = self::TOK_NULL);
		}
	}
	protected function expect($token, $do_next = true, $try_next = false, $next_on_match = 1) {
		if ($do_next) {
			if ($do_next === 1) {
				$this->next();
			} else {
				$this->next_no_whitespace();
			}
		}
		if (is_int($token)) {
			if (($this->token !== $token) && ((!$try_next) || ((($try_next === 1) && ($this->next() !== $token)) || (($try_next === true) && ($this->next_no_whitespace() !== $token))))) {
				$this->addError('Unexpected "'.$this->getTokenString().'"');
				return false;
			}
		} else {
			if (($this->doc[$this->pos] !== $token) && ((!$try_next) || (((($try_next === 1) && ($this->next() !== self::TOK_NULL)) || (($try_next === true) && ($this->next_no_whitespace() !== self::TOK_NULL))) && ($this->doc[$this->pos] !== $token)))) {
				$this->addError('Expected "'.$token.'", but found "'.$this->getTokenString().'"');
				return false;
			}
		}
		if ($next_on_match) {
			if ($next_on_match === 1) {
				$this->next();
			} else {
				$this->next_no_whitespace();
			}
		}
		return true;
	}
}
//END gan_tokenizer.php

//START gan_parser_html.php
class HTML_Parser_Base extends Tokenizer_Base {
	const TOK_TAG_OPEN = 100;
	const TOK_TAG_CLOSE = 101;
	const TOK_SLASH_FORWARD = 103;
	const TOK_SLASH_BACKWARD = 104;
	const TOK_STRING = 104;
	const TOK_EQUALS = 105;
	var $identifiers = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890:-_!?%';
	var $status = array();
	var $custom_char_map = array(
		'<' => self::TOK_TAG_OPEN,
		'>' => self::TOK_TAG_CLOSE,
		"'" => 'parse_string',
		'"' => 'parse_string',
		'/' => self::TOK_SLASH_FORWARD,
		'\\' => self::TOK_SLASH_BACKWARD,
		'=' => self::TOK_EQUALS
	);
	function __construct($doc = '', $pos = 0) {
		parent::__construct($doc, $pos);
		$this->parse_all();
	}
	var $tag_map = array(
		'!doctype' => 'parse_doctype',
		'?' => 'parse_php',
		'?php' => 'parse_php',
		'%' => 'parse_asp',
		'style' => 'parse_style',
		'script' => 'parse_script'
	);
	protected function parse_string() {
		if ($this->next_pos($this->doc[$this->pos], false) !== self::TOK_UNKNOWN) {
			--$this->pos;
		}
		return self::TOK_STRING;
	}
	function parse_text() {
		$len = $this->pos - 1 - $this->status['last_pos'];
		$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
	}
	function parse_comment() {
		$this->pos += 3;
		if ($this->next_pos('-->', false) !== self::TOK_UNKNOWN) {
			$this->status['comment'] = $this->getTokenString(1, -1);
			--$this->pos;
		} else {
			$this->status['comment'] = $this->getTokenString(1, -1);
			$this->pos += 2;
		}
		$this->status['last_pos'] = $this->pos;
		return true;
	}
	function parse_doctype() {
		$start = $this->pos;
		if ($this->next_search('[>', false) === self::TOK_UNKNOWN)  {
			if ($this->doc[$this->pos] === '[') {
				if (($this->next_pos(']', false) !== self::TOK_UNKNOWN) || ($this->next_pos('>', false) !== self::TOK_UNKNOWN)) {
					$this->addError('Invalid doctype');
					return false;
				}
			}
			$this->token_start = $start;
			$this->status['dtd'] = $this->getTokenString(2, -1);
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('Invalid doctype');
			return false;
		}
	}
	function parse_cdata() {
		if ($this->next_pos(']]>', false) === self::TOK_UNKNOWN) {
			$this->status['cdata'] = $this->getTokenString(9, -1);
			$this->status['last_pos'] = $this->pos + 2;
			return true;
		} else {
			$this->addError('Invalid cdata tag');
			return false;
		}
	}
	function parse_php() {
		$start = $this->pos;
		if ($this->next_pos('?>', false) !== self::TOK_UNKNOWN) {
			$this->pos -= 2; 
		}
		$len = $this->pos - 1 - $start;
		$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
		$this->status['last_pos'] = ++$this->pos;
		return true;
	}
	function parse_asp() {
		$start = $this->pos;
		if ($this->next_pos('%>', false) !== self::TOK_UNKNOWN) {
			$this->pos -= 2; 
		}
		$len = $this->pos - 1 - $start;
		$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
		$this->status['last_pos'] = ++$this->pos;
		return true;
	}
	function parse_style() {
		if ($this->parse_attributes() && ($this->token === self::TOK_TAG_CLOSE) && ($start = $this->pos) && ($this->next_pos('</style>', false) === self::TOK_UNKNOWN)) {
			$len = $this->pos - 1 - $start;
			$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
			$this->pos += 7;
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('No end for style tag found');
			return false;
		}
	}
	function parse_script() {
		if ($this->parse_attributes() && ($this->token === self::TOK_TAG_CLOSE) && ($start = $this->pos) && ($this->next_pos('</script>', false) === self::TOK_UNKNOWN)) {
			$len = $this->pos - 1 - $start;
			$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
			$this->pos += 8;
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('No end for script tag found');
			return false;
		}
	}
	function parse_conditional() {
		if ($this->status['closing_tag']) {
			$this->pos += 8;
		} else {
			$this->pos += (($this->status['comment']) ? 5 : 3);
			if ($this->next_pos(']', false) !== self::TOK_UNKNOWN) {
				$this->addError('"]" not found in conditional tag');
				return false;
			}
			$this->status['tag_condition'] = $this->getTokenString(0, -1);
		}
		if ($this->next_no_whitespace() !== self::TOK_TAG_CLOSE) {
			$this->addError('No ">" tag found 2 for conditional tag');
			return false;
		}
		if ($this->status['comment']) {
			$this->status['last_pos'] = $this->pos;
			if ($this->next_pos('-->', false) !== self::TOK_UNKNOWN) {
				$this->addError('No ending tag found for conditional tag');
				$this->pos = $this->size - 1;
				$len = $this->pos - 1 - $this->status['last_pos'];
				$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
			} else {
				$len = $this->pos - 10 - $this->status['last_pos'];
				$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
				$this->pos += 2;
			}
		}
		$this->status['last_pos'] = $this->pos;
		return true;
	}
	function parse_attributes() {
		$this->status['attributes'] = array();
		while ($this->next_no_whitespace() === self::TOK_IDENTIFIER) {
			$attr = $this->getTokenString();
			if (($attr === '?') || ($attr === '%')) {
				break;
			}
			if ($this->next_no_whitespace() === self::TOK_EQUALS) {
				if ($this->next_no_whitespace() === self::TOK_STRING) {
					$val = $this->getTokenString(1, -1);
				} else {
					if (!isset($stop)) {
						$stop = $this->whitespace;
						$stop['<'] = true;
						$stop['>'] = true;
					}
					while ((++$this->pos < $this->size) && (!isset($stop[$this->doc[$this->pos]]))) {}
					--$this->pos;
					$val = $this->getTokenString();
					if (trim($val) === '') {
						$this->addError('Invalid attribute value');
						return false;
					}
				}
			} else {
				$val = $attr;
				$this->pos = (($this->token_start) ? $this->token_start : $this->pos) - 1;
			}
			$this->status['attributes'][$attr] = $val;
		}
		return true;
	}
	function parse_tag_default() {
		if ($this->status['closing_tag']) {
			$this->status['attributes'] = array();
			$this->next_no_whitespace();
		} else {
			if (!$this->parse_attributes()) {
				return false;
			}
		}
		if ($this->token !== self::TOK_TAG_CLOSE) {
			if ($this->token === self::TOK_SLASH_FORWARD) {
				$this->status['self_close'] = true;
				$this->next();
			} elseif ((($this->status['tag_name'][0] === '?') && ($this->doc[$this->pos] === '?')) || (($this->status['tag_name'][0] === '%') && ($this->doc[$this->pos] === '%'))) {
				$this->status['self_close'] = true;
				$this->pos++;
				if (isset($this->char_map[$this->doc[$this->pos]]) && (!is_string($this->char_map[$this->doc[$this->pos]]))) {
					$this->token = $this->char_map[$this->doc[$this->pos]];
				} else {
					$this->token = self::TOK_UNKNOWN;
				}
			}
		}
		if ($this->token !== self::TOK_TAG_CLOSE) {
			$this->addError('Expected ">", but found "'.$this->getTokenString().'"');
			if ($this->next_pos('>', false) !== self::TOK_UNKNOWN) {
				$this->addError('No ">" tag found for "'.$this->status['tag_name'].'" tag');
				return false;
			}
		}
		return true;
	}
	function parse_tag() {
		$start = $this->pos;
		$this->status['self_close'] = false;
		$this->parse_text();
		$next = (($this->pos + 1) < $this->size) ? $this->doc[$this->pos + 1] : '';
		if ($next === '!') {
			$this->status['closing_tag'] = false;
			if (substr($this->doc, $this->pos + 2, 2) === '--') {
				$this->status['comment'] = true;
				if (($this->doc[$this->pos + 4] === '[') && (strcasecmp(substr($this->doc, $this->pos + 5, 2), 'if') === 0)) {
					return $this->parse_conditional();
				} else {
					return $this->parse_comment();
				}
			} else {
				$this->status['comment'] = false;
				if ($this->doc[$this->pos + 2] === '[') {
					if (strcasecmp(substr($this->doc, $this->pos + 3, 2), 'if') === 0) {
						return $this->parse_conditional();
					} elseif (strcasecmp(substr($this->doc, $this->pos + 3, 5), 'endif') === 0) {
						$this->status['closing_tag'] = true;
						return $this->parse_conditional();
					} elseif (strcasecmp(substr($this->doc, $this->pos + 3, 5), 'cdata') === 0) {
						return $this->parse_cdata();
					}
				}
			}
		} elseif ($next === '/') {
			$this->status['closing_tag'] = true;
			++$this->pos;
		} else {
			$this->status['closing_tag'] = false;
		}
		if ($this->next() !== self::TOK_IDENTIFIER) {
			$this->addError('Tagname expected');
				$this->status['last_pos'] = $start - 1;
				return true;
		}
		$tag = $this->getTokenString();
		$this->status['tag_name'] = $tag;
		$tag = strtolower($tag);
		if (isset($this->tag_map[$tag])) {
			$res = $this->{$this->tag_map[$tag]}();
		} else {
			$res = $this->parse_tag_default();
		}
		$this->status['last_pos'] = $this->pos;
		return $res;
	}
	function parse_all() {
		$this->errors = array();
		$this->status['last_pos'] = -1;
		if (($this->token === self::TOK_TAG_OPEN) || ($this->next_pos('<', false) === self::TOK_UNKNOWN)) {
			do {
				if (!$this->parse_tag()) {
					return false;
				}
			} while ($this->next_pos('<') !== self::TOK_NULL);
		}
		$this->pos = $this->size;
		$this->parse_text();
		return true;
	}
}
class HTML_Parser extends HTML_Parser_Base {
	var $root = 'HTML_Node';
	var $hierarchy = array();
	var	$tags_selfclose = array(
		'area'		=> true,
		'base'		=> true,
		'basefont'	=> true,
		'br'		=> true,
		'col'		=> true,
		'command'	=> true,
		'embed'		=> true,
		'frame'		=> true,
		'hr'		=> true,
		'img'		=> true,
		'input'		=> true,
		'ins'		=> true,
		'keygen'	=> true,
		'link'		=> true,
		'meta'		=> true,
		'param'		=> true,
		'source'	=> true,
		'track'		=> true,
		'wbr'		=> true
	);
	function __construct($doc = '', $pos = 0, $root = null) {
		if ($root === null) {
			$root = new $this->root('~root~', null);
		}
		$this->root =& $root;
		parent::__construct($doc, $pos);
	}
	function __invoke($query = '*') {
		return $this->select($query);
	}
	function __toString() {
		return $this->root->getInnerText();
	}
	function select($query = '*', $index = false, $recursive = true, $check_self = false) {
		return $this->root->select($query, $index, $recursive, $check_self);
	}
	protected function parse_hierarchy($self_close = null) {
		if ($self_close === null) {
			$this->status['self_close'] = ($self_close = isset($this->tags_selfclose[strtolower($this->status['tag_name'])]));
		}
		if ($self_close) {
			if ($this->status['closing_tag']) {
				$c = $this->hierarchy[count($this->hierarchy) - 1]->children;
				$found = false;
				for ($count = count($c), $i = $count - 1; $i >= 0; $i--) {
					if (strcasecmp($c[$i]->tag, $this->status['tag_name']) === 0) {
						for($ii = $i + 1; $ii < $count; $ii++) {
							$index = null; 
							$c[$i + 1]->changeParent($c[$i], $index);
						}
						$c[$i]->self_close = false;
						$found = true;
						break;
					}
				}
				if (!$found) {
					$this->addError('Closing tag "'.$this->status['tag_name'].'" which is not open');
				}
			} elseif ($this->status['tag_name'][0] === '?') {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addXML($this->status['tag_name'], '', $this->status['attributes'], $index);
			} elseif ($this->status['tag_name'][0] === '%') {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addASP($this->status['tag_name'], '', $this->status['attributes'], $index);
			} else {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
			}
		} elseif ($this->status['closing_tag']) {
			$found = false;
			for ($count = count($this->hierarchy), $i = $count - 1; $i >= 0; $i--) {
				if (strcasecmp($this->hierarchy[$i]->tag, $this->status['tag_name']) === 0) {
					for($ii = ($count - $i - 1); $ii >= 0; $ii--) {
						$e = array_pop($this->hierarchy);
						if ($ii > 0) {
							$this->addError('Closing tag "'.$this->status['tag_name'].'" while "'.$e->tag.'" is not closed yet');
						}
					}
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addError('Closing tag "'.$this->status['tag_name'].'" which is not open');
			}
		} else {
			$index = null; 
			$this->hierarchy[] = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);	
		}
	}
	function parse_cdata() {
		if (!parent::parse_cdata()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addCDATA($this->status['cdata'], $index);
		return true;
	}
	function parse_comment() {
		if (!parent::parse_comment()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addComment($this->status['comment'], $index);
		return true;
	}
	function parse_conditional() {
		if (!parent::parse_conditional()) {return false;}
		if ($this->status['comment']) {
			$index = null; 
			$e = $this->hierarchy[count($this->hierarchy) - 1]->addConditional($this->status['tag_condition'], true, $index);
			if ($this->status['text'] !== '') {
				$index = null; 
				$e->addText($this->status['text'], $index);
			}
		} else {
			if ($this->status['closing_tag']) {
				$this->parse_hierarchy(false);
			} else {
				$index = null; 
				$this->hierarchy[] = $this->hierarchy[count($this->hierarchy) - 1]->addConditional($this->status['tag_condition'], false, $index);
			}
		}
		return true;
	}
	function parse_doctype() {
		if (!parent::parse_doctype()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addDoctype($this->status['dtd'], $index);
		return true;
	}
	function parse_php() {
		if (!parent::parse_php()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addXML('php', $this->status['text'], $index);
		return true;
	}
	function parse_asp() {
		if (!parent::parse_asp()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addASP('', $this->status['text'], $index);
		return true;
	}
	function parse_script() {
		if (!parent::parse_script()) {return false;}
		$index = null; 
		$e = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
		if ($this->status['text'] !== '') {
			$index = null; 
			$e->addText($this->status['text'], $index);
		}
		return true;
	}
	function parse_style() {
		if (!parent::parse_style()) {return false;}
		$index = null; 
		$e = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
		if ($this->status['text'] !== '') {
			$index = null; 
			$e->addText($this->status['text'], $index);
		}
		return true;
	}
	function parse_tag_default() {
		if (!parent::parse_tag_default()) {return false;}
		$this->parse_hierarchy(($this->status['self_close']) ? true : null);
		return true;
	}
	function parse_text() {
		parent::parse_text();
		if ($this->status['text'] !== '') {
			$index = null; 
			$this->hierarchy[count($this->hierarchy) - 1]->addText($this->status['text'], $index);
		}
	}
	function parse_all() {
		$this->hierarchy = array(&$this->root);
		return ((parent::parse_all()) ? $this->root : false);
	}
}
class HTML_Parser_HTML5 extends HTML_Parser {
	var $tags_optional_close = array(
		'li' 			=> array('li' => true),
		'dt' 			=> array('dt' => true, 'dd' => true),
		'dd' 			=> array('dt' => true, 'dd' => true),
		'address' 		=> array('p' => true),
		'article' 		=> array('p' => true),
		'aside' 		=> array('p' => true),
		'blockquote' 	=> array('p' => true),
		'dir' 			=> array('p' => true),
		'div' 			=> array('p' => true),
		'dl' 			=> array('p' => true),
		'fieldset' 		=> array('p' => true),
		'footer' 		=> array('p' => true),
		'form' 			=> array('p' => true),
		'h1' 			=> array('p' => true),
		'h2' 			=> array('p' => true),
		'h3' 			=> array('p' => true),
		'h4' 			=> array('p' => true),
		'h5' 			=> array('p' => true),
		'h6' 			=> array('p' => true),
		'header' 		=> array('p' => true),
		'hgroup' 		=> array('p' => true),
		'hr' 			=> array('p' => true),
		'menu' 			=> array('p' => true),
		'nav' 			=> array('p' => true),
		'ol' 			=> array('p' => true),
		'p' 			=> array('p' => true),
		'pre' 			=> array('p' => true),
		'section' 		=> array('p' => true),
		'table' 		=> array('p' => true),
		'ul' 			=> array('p' => true),
		'rt'			=> array('rt' => true, 'rp' => true),
		'rp'			=> array('rt' => true, 'rp' => true),
		'optgroup'		=> array('optgroup' => true, 'option' => true),
		'option'		=> array('option'),
		'tbody'			=> array('thread' => true, 'tbody' => true, 'tfoot' => true),
		'tfoot'			=> array('thread' => true, 'tbody' => true),
		'tr'			=> array('tr' => true),
		'td'			=> array('td' => true, 'th' => true),
		'th'			=> array('td' => true, 'th' => true),
		'body'			=> array('head' => true)
	);
	protected function parse_hierarchy($self_close = null) {
		$tag_curr = strtolower($this->status['tag_name']);
		if ($self_close === null) {
			$this->status['self_close'] = ($self_close = isset($this->tags_selfclose[$tag_curr]));
		}
		if (! ($self_close || $this->status['closing_tag'])) {
			$tag_prev = strtolower($this->hierarchy[count($this->hierarchy) - 1]->tag);			
			if (isset($this->tags_optional_close[$tag_curr]) && isset($this->tags_optional_close[$tag_curr][$tag_prev])) {
				array_pop($this->hierarchy);
			}
		}
		return parent::parse_hierarchy($self_close);
	}
}
//END gan_parser_html.php

//START gan_node_html.php
class HTML_Node {
	const NODE_ELEMENT = 0;
	const NODE_TEXT = 1;
	const NODE_COMMENT = 2;
	const NODE_CONDITIONAL = 3;
	const NODE_CDATA = 4;
	const NODE_DOCTYPE = 5;
	const NODE_XML = 6;
	const NODE_ASP = 7;
	const NODE_TYPE = self::NODE_ELEMENT;
	var $selectClass = 'HTML_Selector';
	var $parserClass = 'HTML_Parser_HTML5';
	var $childClass = __CLASS__;
	var $childClass_Text = 'HTML_Node_TEXT';
	var $childClass_Comment = 'HTML_Node_COMMENT';
	var $childClass_Conditional = 'HTML_Node_CONDITIONAL';
	var $childClass_CDATA = 'HTML_Node_CDATA';
	var $childClass_Doctype = 'HTML_Node_DOCTYPE';
	var $childClass_XML = 'HTML_Node_XML';
	var $childClass_ASP = 'HTML_Node_ASP';
	var $parent = null;
	var $attributes = array();
	var $attributes_ns = null;
	var $children = array();
	var $tag = '';
	var $tag_ns = null;
	var $self_close = false;
	var $self_close_str = ' /';
	var $attribute_shorttag = true;
	var $filter_map = array(
		'root' => 'filter_root',
		'nth-child' => 'filter_nchild',
		'eq' => 'filter_nchild', 
		'gt' => 'filter_gt',
		'lt' => 'filter_lt',
		'nth-last-child' => 'filter_nlastchild',
		'nth-of-type' => 'filter_ntype',
		'nth-last-of-type' => 'filter_nlastype',
		'odd' => 'filter_odd',
		'even' => 'filter_even',
		'every' => 'filter_every',
		'first-child' => 'filter_first',
		'last-child' => 'filter_last',
		'first-of-type' => 'filter_firsttype',
		'last-of-type' => 'filter_lasttype',
		'only-child' => 'filter_onlychild',
		'only-of-type' => 'filter_onlytype',
		'empty' => 'filter_empty',
		'not-empty' => 'filter_notempty',
		'has-text' => 'filter_hastext',
		'no-text' => 'filter_notext',
		'lang' => 'filter_lang',
		'contains' => 'filter_contains',
		'has' => 'filter_has',
		'not' => 'filter_not',
		'element' => 'filter_element',
		'text' => 'filter_text',
		'comment' => 'filter_comment'
	);
	function __construct($tag, $parent) {
		$this->parent = $parent;
		if (is_string($tag)) {
			$this->tag = $tag;
		} else {
			$this->tag = $tag['tag_name'];
			$this->self_close = $tag['self_close'];
			$this->attributes = $tag['attributes'];
		}
	}
	function __destruct() {
		$this->delete();
	}
	function __toString() {
		return (($this->tag === '~root~') ? $this->toString(true, true, 1) : $this->tag);
	}
	function __get($attribute) {
		return $this->getAttribute($attribute);
	}
	function __set($attribute, $value) {
		$this->setAttribute($attribute, $value);
	}
	function __isset($attribute) {
		return $this->hasAttribute($attribute);
	}
	function __unset($attribute) {
		return $this->deleteAttribute($attribute);
	}
	function __invoke($query = '*', $index = false, $recursive = true, $check_self = false) {
		return $this->select($query, $index, $recursive, $check_self);
	}
	 function dumpLocation() {
		return (($this->parent) ? (($p = $this->parent->dumpLocation()) ? $p.' > ' : '').$this->tag.'('.$this->typeIndex().')' : '');
	 }
	protected function toString_attributes() {
		$s = '';
		foreach($this->attributes as $a => $v) {
			$s .= ' '.$a.(((!$this->attribute_shorttag) || ($v !== $a)) ? '="'.htmlspecialchars($v, ENT_QUOTES, '', false).'"' : '');
		}
		return $s;
	}
	protected function toString_content($attributes = true, $recursive = true, $content_only = false) {
		$s = '';
		foreach($this->children as $c) {
			$s .= $c->toString($attributes, $recursive, $content_only);
		}
		return $s;
	}
	function toString($attributes = true, $recursive = true, $content_only = false) {
		if ($content_only) {
			if (is_int($content_only)) {
				--$content_only;
			}
			return $this->toString_content($attributes, $recursive, $content_only);
		}
		$s = '<'.$this->tag;
		if ($attributes) {
			$s .= $this->toString_attributes();
		}
		if ($this->self_close) {
			$s .= $this->self_close_str.'>';
		} else {
			$s .= '>';
			if($recursive) {
				$s .= $this->toString_content($attributes);
			}
			$s .= '</'.$this->tag.'>';
		}
		return $s;
	}
	function getOuterText() {
		return html_entity_decode($this->toString(), ENT_QUOTES);
	}
	function setOuterText($text, $parser = null) {
		if (trim($text)) {
			$index = $this->index();
			if ($parser === null) {
				$parser = new $this->parserClass();
			}
			$parser->setDoc($text);
			$parser->parse_all();
			$parser->root->moveChildren($this->parent, $index);
		}
		$this->delete();
		return (($parser && $parser->errors) ? $parser->errors : true);
	}
	function html() {
		return $this->toString();
	}
	function getInnerText() {
		return html_entity_decode($this->toString(true, true, 1), ENT_QUOTES);
	}
	function setInnerText($text, $parser = null) {
		$this->clear();
		if (trim($text)) {
			if ($parser === null) {
				$parser = new $this->parserClass();
			}
			$parser->root =& $this;
			$parser->setDoc($text);
			$parser->parse_all();
		}
		return (($parser && $parser->errors) ? $parser->errors : true);
	}
	function getPlainText() {
		return preg_replace('`\s+`', ' ', html_entity_decode($this->toString(true, true, true), ENT_QUOTES));
	}
	function getPlainTextUTF8() {
		$txt = $this->getPlainText();
		$enc = $this->getEncoding();
		if ($enc !== false) {
			$txt = mb_convert_encoding($txt, "UTF-8", $enc);
		}
		return $txt;
	}
	function setPlainText($text) {
		$this->clear();
		if (trim($text)) {
			$this->addText(htmlentities($text, ENT_QUOTES));
		}
	}
	function delete() {
		if (($p = $this->parent) !== null) {
			$this->parent = null;
			$p->deleteChild($this);
		} else {
			$this->clear();
		}
	}
	function detach($move_children_up = false) {
		if (($p = $this->parent) !== null) {
			$index = $this->index();
			$this->parent = null;
			if ($move_children_up) {
				$this->moveChildren($p, $index);
			}
			$p->deleteChild($this, true);
		}
	}
	function clear() {
		foreach($this->children as $c) {
			$c->parent = null;
			$c->delete();
		}
		$this->children = array();
	}
	function getRoot() {
		$r = $this->parent;
		$n = ($r === null) ? null : $r->parent;
		while ($n !== null) {
			$r = $n;
			$n = $r->parent;
		}
		return $r;
	}
	function changeParent($to, &$index = null) {
		if ($this->parent !== null) {
			$this->parent->deleteChild($this, true);
		}
		$this->parent = $to;
		if ($index !== false) {
			$new_index = $this->index();
			if (!(is_int($new_index) && ($new_index >= 0))) {
				$this->parent->addChild($this, $index);
			}
		}
	}
	function hasParent($tag = null, $recursive = false) {
		if ($this->parent !== null) {
			if ($tag === null) {
				return true;
			} elseif (is_string($tag)) {
				return (($this->parent->tag === $tag) || ($recursive && $this->parent->hasParent($tag)));
			} elseif (is_object($tag)) {
				return (($this->parent === $tag) || ($recursive && $this->parent->hasParent($tag)));
			}
		}
		return false;
	}
	function isParent($tag, $recursive = false) {
		return ($this->hasParent($tag, $recursive) === ($tag !== null));
	}
	function isText() {
		return false;
	}
	function isComment() {
		return false;
	}
	function isTextOrComment() {
		return false;
	}
	function move($to, &$new_index = -1) {
		$this->changeParent($to, $new_index);
	}
	function moveChildren($to, &$new_index = -1, $start = 0, $end = -1) {
		if ($end < 0) {
			$end += count($this->children);
		}
		for ($i = $start; $i <= $end; $i++) {
			$this->children[$start]->changeParent($to, $new_index);
		}
	}
	function index($count_all = true) {
		if (!$this->parent) {
			return -1;
		} elseif ($count_all) {
			return $this->parent->findChild($this);
		} else{
			$index = -1;
			foreach(array_keys($this->parent->children) as $k) {
				if (!$this->parent->children[$k]->isTextOrComment()) {
					++$index;
				}
				if ($this->parent->children[$k] === $this) {
					return $index;
				}
			}
			return -1;
		}
	}
	function setIndex($index) {
		if ($this->parent) {
			if ($index > $this->index()) {
				--$index;
			}
			$this->delete();
			$this->parent->addChild($this, $index);
		}
	}
	function typeIndex() {
		if (!$this->parent) {
			return -1;
		} else {
			$index = -1;
			foreach(array_keys($this->parent->children) as $k) {
				if (strcasecmp($this->tag, $this->parent->children[$k]->tag) === 0) {
					++$index;
				}
				if ($this->parent->children[$k] === $this) {
					return $index;
				}
			}
			return -1;
		}
	}
	function indent() {
		return (($this->parent) ? $this->parent->indent() + 1 : -1);
	}
	function getSibling($offset = 1) {
		$index = $this->index() + $offset;
		if (($index >= 0) && ($index < $this->parent->childCount())) {
			return $this->parent->getChild($index);
		} else {
			return null;
		}
	}
	function getNextSibling($skip_text_comments = true) {
		$offset = 1;
		while (($n = $this->getSibling($offset)) !== null) {
			if ($skip_text_comments && ($n->tag[0] === '~')) {
				++$offset;
			} else {
				break;
			}
		}
		return $n;
	}
	function getPreviousSibling($skip_text_comments = true) {
		$offset = -1;
		while (($n = $this->getSibling($offset)) !== null) {
			if ($skip_text_comments && ($n->tag[0] === '~')) {
				--$offset;
			} else {
				break;
			}
		}
		return $n;
	}
	function getNamespace() {
		if ($this->tag_ns === null) {
			$a = explode(':', $this->tag, 2);
			if (empty($a[1])) {
				$this->tag_ns = array('', $a[0]);
			} else {
				$this->tag_ns = array($a[0], $a[1]);
			}
		}
		return $this->tag_ns[0];
	}
	function setNamespace($ns) {
		if ($this->getNamespace() !== $ns) {
			$this->tag_ns[0] = $ns;
			$this->tag = $ns.':'.$this->tag_ns[1];
		}
	}
	function getTag() {
		if ($this->tag_ns === null) {
			$this->getNamespace();
		}
		return $this->tag_ns[1];
	}
	function setTag($tag, $with_ns = false) {
		$with_ns = $with_ns || (strpos($tag, ':') !== false);
		if ($with_ns) {
			$this->tag = $tag;
			$this->tag_ns = null;
		} elseif ($this->getTag() !== $tag) {
			$this->tag_ns[1] = $tag;
			$this->tag = (($this->tag_ns[0]) ? $this->tag_ns[0].':' : '').$tag;
		}
	}
	function getEncoding() {
		$root = $this->getRoot();
		if ($root !== null) {
			if ($enc = $root->select('meta[charset]', 0, true, true)) {
				return $enc->getAttribute("charset");
			} elseif ($enc = $root->select('"?xml"[encoding]', 0, true, true)) {
				return $enc->getAttribute("encoding");
			} elseif ($enc = $root->select('meta[content*="charset="]', 0, true, true)) {
				$enc = $enc->getAttribute("content");
				return substr($enc, strpos($enc, "charset=")+8);
			}
		}
		return false;
	}	
	function childCount($ignore_text_comments = false) {
		if (!$ignore_text_comments) {
			return count($this->children);
		} else{
			$count = 0;
			foreach(array_keys($this->children) as $k) {
				if (!$this->children[$k]->isTextOrComment()) {
					++$count;
				}
			}
			return $count;
		}
	}
	function findChild($child) {
		return array_search($child, $this->children, true);
	}
	function hasChild($child) {
		return ((bool) findChild($child));
	}
	function &getChild($child, $ignore_text_comments = false) {
		if (!is_int($child)) {
			$child = $this->findChild($child);
		} elseif ($child < 0) {
			$child += $this->childCount($ignore_text_comments);
		}
		if ($ignore_text_comments) {
			$count = 0;
			$last = null;
			foreach(array_keys($this->children) as $k) {
				if (!$this->children[$k]->isTextOrComment()) {
					if ($count++ === $child) {
						return $this->children[$k];
					}
					$last = $this->children[$k];
				}
			}
			return (($child > $count) ? $last : null);
		} else {
			return $this->children[$child];
		}
	}
	function &addChild($tag, &$offset = null) {
		if (!is_object($tag)) {
			$tag = new $this->childClass($tag, $this);
		} elseif ($tag->parent !== $this) {
			$index = false; 
			$tag->changeParent($this, $index);
		}
		if (is_int($offset) && ($offset < count($this->children)) && ($offset !== -1)) {
			if ($offset < 0) {
				$offset += count($this->children);
			}
			array_splice($this->children, $offset++, 0, array(&$tag));
		} else {
			$this->children[] =& $tag;
		}
		return $tag;
	}
	function &firstChild($ignore_text_comments = false) {
		return $this->getChild(0, $ignore_text_comments);
	}
	function &lastChild($ignore_text_comments = false) {
		return $this->getChild(-1, $ignore_text_comments);
	}
	function &insertChild($tag, $index) {
		return $this->addChild($tag, $index);
	}
	function &addText($text, &$offset = null) {
		return $this->addChild(new $this->childClass_Text($this, $text), $offset);
	}
	function &addComment($text, &$offset = null) {
		return $this->addChild(new $this->childClass_Comment($this, $text), $offset);
	}
	function &addConditional($condition, $hidden = true, &$offset = null) {
		return $this->addChild(new $this->childClass_Conditional($this, $condition, $hidden), $offset);
	}
	function &addCDATA($text, &$offset = null) {
		return $this->addChild(new $this->childClass_CDATA($this, $text), $offset);
	}
	function &addDoctype($dtd, &$offset = null) {
		return $this->addChild(new $this->childClass_Doctype($this, $dtd), $offset);
	}
	function &addXML($tag = 'xml', $text = '', $attributes = array(), &$offset = null) {
		return $this->addChild(new $this->childClass_XML($this, $tag, $text, $attributes), $offset);
	}
	function &addASP($tag = '', $text = '', $attributes = array(), &$offset = null) {
		return $this->addChild(new $this->childClass_ASP($this, $tag, $text, $attributes), $offset);
	}
	function deleteChild($child, $soft_delete = false) {
		if (is_object($child)) {
			$child = $this->findChild($child);
		} elseif ($child < 0) {
			$child += count($this->children);
		}
		if (!$soft_delete) {
			$this->children[$child]->delete();
		}
		unset($this->children[$child]);
		$tmp = array();
		foreach(array_keys($this->children) as $k) {
			$tmp[] =& $this->children[$k];
		}
		$this->children = $tmp;
	}
	function wrap($node, $wrap_index = -1, $node_index = null) {
		if ($node_index === null) {
			$node_index = $this->index();
		}
		if (!is_object($node)) {
			$node = $this->parent->addChild($node, $node_index);
		} elseif ($node->parent !== $this->parent) {
			$node->changeParent($this->parent, $node_index);
		}
		$this->changeParent($node, $wrap_index);
		return $node;
	}
	function wrapInner($node, $start = 0, $end = -1, $wrap_index = -1, $node_index = null) {
		if ($end < 0) {
			$end += count($this->children);
		}
		if ($node_index === null) {
			$node_index = $end + 1;
		}
		if (!is_object($node)) {
			$node = $this->addChild($node, $node_index);
		} elseif ($node->parent !== $this) {
			$node->changeParent($this->parent, $node_index);
		}
		$this->moveChildren($node, $wrap_index, $start, $end);
		return $node;
	}
	function attributeCount() {
		return count($this->attributes);
	}
	protected function findAttribute($attr, $compare = 'total', $case_sensitive = false) {
		if (is_int($attr)) {
			if ($attr < 0) {
				$attr += count($this->attributes);
			}
			$keys = array_keys($this->attributes);
			return $this->findAttribute($keys[$attr], 'total', true);
		} else if ($compare === 'total') {
			$b = explode(':', $attr, 2);
			if ($case_sensitive) {
				$t =& $this->attributes;
			} else {
				$t = array_change_key_case($this->attributes);
				$attr = strtolower($attr);
			}
			if (isset($t[$attr])) {
				$index = 0;
				foreach($this->attributes as $a => $v) {
					if (($v === $t[$attr]) && (strcasecmp($a, $attr) === 0)) {
						$attr = $a;
						$b = explode(':', $attr, 2);
						break;
					}
					++$index;
				}
				if (empty($b[1])) {
					return array(array('', $b[0], $attr, $index));
				} else {
					return array(array($b[0], $b[1], $attr, $index));
				}
			} else {
				return false;
			}
		} else {
			if ($this->attributes_ns === null) {
				$index = 0;
				foreach($this->attributes as $a => $v) {
					$b = explode(':', $a, 2);
					if (empty($b[1])) {
						$this->attributes_ns[$b[0]][] = array('', $b[0], $a, $index);
					} else {
						$this->attributes_ns[$b[1]][] = array($b[0], $b[1], $a, $index);
					}
					++$index;
				}
			}
			if ($case_sensitive) {
				$t =& $this->attributes_ns;
			} else {
				$t = array_change_key_case($this->attributes_ns);
				$attr = strtolower($attr);
			}
			if ($compare === 'namespace') {
				$res = array();
				foreach($t as $ar) {
					foreach($ar as $a) {
						if ($a[0] === $attr) {
							$res[] = $a;
						}
					}
				}
				return $res;
			} elseif ($compare === 'name') {
				return ((isset($t[$attr])) ? $t[$attr] : false);
			} else {
				trigger_error('Unknown comparison mode');
			}
		}
	}
	function hasAttribute($attr, $compare = 'total', $case_sensitive = false) {
		return ((bool) $this->findAttribute($attr, $compare, $case_sensitive));
	}
	function getAttributeNS($attr, $compare = 'name', $case_sensitive = false) {
		$f = $this->findAttribute($attr, $compare, $case_sensitive);
		if (is_array($f) && $f) {
			if (count($f) === 1) {
				return $this->attributes[$f[0][0]];
			} else {
				$res = array();
				foreach($f as $a) {
					$res[] = $a[0];
				}
				return $res;
			}
		} else {
			return false;
		}
	}
	function setAttributeNS($attr, $namespace, $compare = 'name', $case_sensitive = false) {
		$f = $this->findAttribute($attr, $compare, $case_sensitive);
		if (is_array($f) && $f) {
			if ($namespace) {
				$namespace .= ':';
			}
			foreach($f as $a) {
				$val = $this->attributes[$a[2]];
				unset($this->attributes[$a[2]]);
				$this->attributes[$namespace.$a[1]] = $val;
			}
			$this->attributes_ns = null;
			return true;
		} else {
			return false;
		}
	}
	function getAttribute($attr, $compare = 'total', $case_sensitive = false) {
		$f = $this->findAttribute($attr, $compare, $case_sensitive);
		if (is_array($f) && $f){
			if (count($f) === 1) {
				return $this->attributes[$f[0][2]];
			} else {
				$res = array();
				foreach($f as $a) {
					$res[] = $this->attributes[$a[2]];
				}
				return $res;
			}
		} else {
			return null;
		}
	}
	function setAttribute($attr, $val, $compare = 'total', $case_sensitive = false) {
		if ($val === null) {
			return $this->deleteAttribute($attr, $compare, $case_sensitive);
		}
		$f = $this->findAttribute($attr, $compare, $case_sensitive);
		if (is_array($f) && $f) {
			foreach($f as $a) {
				$this->attributes[$a[2]] = (string) $val;
			}
		} else {
			$this->attributes[$attr] = (string) $val;
		}
	}
	function addAttribute($attr, $val) {
		$this->setAttribute($attr, $val, 'total', true);
	}
	function deleteAttribute($attr, $compare = 'total', $case_sensitive = false) {
		$f = $this->findAttribute($attr, $compare, $case_sensitive);
		if (is_array($f) && $f) {
			foreach($f as $a) {
				unset($this->attributes[$a[2]]);
				if ($this->attributes_ns !== null) {
					unset($this->attributes_ns[$a[1]]);
				}
			}
		}
	}
	function hasClass($className) {
		return ($className && preg_match('`\b'.preg_quote($className).'\b`si', $this->class));
	}
	function addClass($className) {
		if (!is_array($className)) {
			$className = array($className);
		}
		$class = $this->class;
		foreach ($className as $c) {
			if (!(preg_match('`\b'.preg_quote($c).'\b`si', $class) > 0)) {
				$class .= ' '.$c;
			}
		}
		 $this->class = $class;
	}
	function removeClass($className) {
		if (!is_array($className)) {
			$className = array($className);
		}
		$class = $this->class;
		foreach ($className as $c) {
			$class = reg_replace('`\b'.preg_quote($c).'\b`si', '', $class);
		}
		if ($class) {
			$this->class = $class;
		} else {
			unset($this->class);
		}
	}
	function getChildrenByCallback($callback, $recursive = true, $check_self = false) {
		$count = $this->childCount();
		if ($check_self && $callback($this)) {
			$res = array($this);
		} else {
			$res = array();
		}
		if ($count > 0) {
			if (is_int($recursive)) {
				$recursive = (($recursive > 1) ? $recursive - 1 : false);
			}
			for ($i = 0; $i < $count; $i++) {
				if ($callback($this->children[$i])) {
					$res[] = $this->children[$i];
				}
				if ($recursive) {
					$res = array_merge($res, $this->children[$i]->getChildrenByCallback($callback, $recursive));
				}
			}
		}
		return $res;
	}
	function getChildrenByMatch($conditions, $recursive = true, $check_self = false, $custom_filters = array()) {
		$count = $this->childCount();
		if ($check_self && $this->match($conditions, true, $custom_filters)) {
			$res = array($this);
		} else {
			$res = array();
		}
		if ($count > 0) {
			if (is_int($recursive)) {
				$recursive = (($recursive > 1) ? $recursive - 1 : false);
			}
			for ($i = 0; $i < $count; $i++) {
				if ($this->children[$i]->match($conditions, true, $custom_filters)) {
					$res[] = $this->children[$i];
				}
				if ($recursive) {
					$res = array_merge($res, $this->children[$i]->getChildrenByMatch($conditions, $recursive, false, $custom_filters));
				}
			}
		}
		return $res;
	}
	protected function match_tags($tags) {
		$res = false;
		foreach($tags as $tag => $match) {
			if (!is_array($match)) {
				$match = array(
					'match' => $match,
					'operator' => 'or',
					'compare' => 'total',
					'case_sensitive' => false
				);
			} else {
				if (is_int($tag)) {
					$tag = $match['tag'];
				}
				if (!isset($match['match'])) {
					$match['match'] = true;
				}
				if (!isset($match['operator'])) {
					$match['operator'] = 'or';
				}
				if (!isset($match['compare'])) {
					$match['compare'] = 'total';
				}
				if (!isset($match['case_sensitive'])) {
					$match['case_sensitive'] = false;
				}
			}
			if (($match['operator'] === 'and') && (!$res)) {
				return false;
			} elseif (!($res && ($match['operator'] === 'or'))) {
				if ($match['compare'] === 'total') {
					$a = $this->tag;
				} elseif ($match['compare'] === 'namespace') {
					$a = $this->getNamespace();
				} elseif ($match['compare'] === 'name') {
					$a = $this->getTag();
				}
				if ($match['case_sensitive']) {
					$res = (($a === $tag) === $match['match']);
				} else {
					$res = ((strcasecmp($a, $tag) === 0) === $match['match']);
				}
			}
		}
		return $res;
	}
	protected function match_attributes($attributes) {
		$res = false;
		foreach($attributes as $attribute => $match) {
			if (!is_array($match)) {
				$match = array(
					'operator_value' => 'equals',
					'value' => $match,
					'match' => true,
					'operator_result' => 'or',
					'compare' => 'total',
					'case_sensitive' => false
				);
			} else {
				if (is_int($attribute)) {
					$attribute = $match['attribute'];
				}
				if (!isset($match['match'])) {
					$match['match'] = true;
				}
				if (!isset($match['operator_result'])) {
					$match['operator_result'] = 'or';
				}
				if (!isset($match['compare'])) {
					$match['compare'] = 'total';
				}
				if (!isset($match['case_sensitive'])) {
					$match['case_sensitive'] = false;
				}
			}
			if (is_string($match['value']) && (!$match['case_sensitive'])) {
				$match['value'] = strtolower($match['value']);
			}
			if (($match['operator_result'] === 'and') && (!$res)) {
				return false;
			} elseif (!($res && ($match['operator_result'] === 'or'))) {
				$possibles = $this->findAttribute($attribute, $match['compare'], $match['case_sensitive']);
				$has = (is_array($possibles) && $possibles);
				$res = (($match['value'] === $has) || (($match['match'] === false) && ($has === $match['match'])));
				if ((!$res) && $has && is_string($match['value'])) {
					foreach($possibles as $a) {
						$val = $this->attributes[$a[2]];
						if (is_string($val) && (!$match['case_sensitive'])) {
							$val = strtolower($val);
						}
						switch($match['operator_value']) {
							case '%=':
							case 'contains_regex':
								$res = ((preg_match('`'.$match['value'].'`s', $val) > 0) === $match['match']);
								if ($res) break 1; else break 2;
							case '|=':
							case 'contains_prefix':
								$res = ((preg_match('`\b'.preg_quote($match['value']).'[\-\s]`s', $val) > 0) === $match['match']);
								if ($res) break 1; else break 2;
							case '~=':
							case 'contains_word':
								$res = ((preg_match('`\s'.preg_quote($match['value']).'\s`s', " $val ") > 0) === $match['match']);
								if ($res) break 1; else break 2;
							case '*=':
							case 'contains':
								$res = ((strpos($val, $match['value']) !== false) === $match['match']);
								if ($res) break 1; else break 2;
							case '$=':
							case 'ends_with':
								$res = ((substr($val, -strlen($match['value'])) === $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							case '^=':
							case 'starts_with':
								$res = ((substr($val, 0, strlen($match['value'])) === $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							case '!=':
							case 'not_equal':
								$res = (($val !== $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							case '=':
							case 'equals':
								$res = (($val === $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							case '>=':
							case 'bigger_than':
								$res = (($val >= $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							case '<=':
							case 'smaller_than':
								$res = (($val >= $match['value']) === $match['match']);
								if ($res) break 1; else break 2;
							default:
								trigger_error('Unknown operator "'.$match['operator_value'].'" to match attributes!');
								return false;
						}
					}
				}
			}
		}
		return $res;
	}
	protected function match_filters($conditions, $custom_filters = array()) {
		foreach($conditions as $c) {
			$c['filter'] = strtolower($c['filter']);
			if (isset($this->filter_map[$c['filter']])) {
				if (!$this->{$this->filter_map[$c['filter']]}($c['params'])) {
					return false;
				}
			} elseif (isset($custom_filters[$c['filter']])) {
				if (!call_user_func($custom_filters[$c['filter']], $this, $c['params'])) {
					return false;
				}
			} else {
				trigger_error('Unknown filter "'.$c['filter'].'"!');
				return false;
			}
		}
		return true;
	}
	function match($conditions, $match = true, $custom_filters = array()) {
		$t = isset($conditions['tags']);
		$a = isset($conditions['attributes']);
		$f = isset($conditions['filters']);
		if (!($t || $a || $f)) {
			if (is_array($conditions) && $conditions) {
				foreach($conditions as $c) {
					if ($this->match($c, $match)) {
						return true;
					}
				}
			}
			return false;
		} else {
			if (($t && (!$this->match_tags($conditions['tags']))) === $match) {
				return false;
			}
			if (($a && (!$this->match_attributes($conditions['attributes']))) === $match) {
				return false;
			}
			if (($f && (!$this->match_filters($conditions['filters'], $custom_filters))) === $match) {
				return false;
			}
			return true;
		}
	}
	function getChildrenByAttribute($attribute, $value, $mode = 'equals', $compare = 'total', $recursive = true) {
		if ($this->childCount() < 1) {
			return array();
		}
		$mode = explode(' ', strtolower($mode));
		$match = ((isset($mode[1]) && ($mode[1] === 'not')) ? 'false' : 'true');
		return $this->getChildrenByMatch(
			array(
				'attributes' => array(
					$attribute => array(
						'operator_value' => $mode[0],
						'value' => $value,
						'match' => $match,
						'compare' => $compare
					)
				)
			),
			$recursive
		);
	}
	function getChildrenByTag($tag, $compare = 'total', $recursive = true) {
		if ($this->childCount() < 1) {
			return array();
		}
		$tag = explode(' ', strtolower($tag));
		$match = ((isset($tag[1]) && ($tag[1] === 'not')) ? 'false' : 'true');
		return $this->getChildrenByMatch(
			array(
				'tags' => array(
					$tag[0] => array(
						'match' => $match,
						'compare' => $compare
					)
				)
			),
			$recursive
		);
	}
	function getChildrenByID($id, $recursive = true) {
		return $this->getChildrenByAttribute('id', $id, 'equals', 'total', $recursive);
	}
	function getChildrenByClass($class, $recursive = true) {
		return $this->getChildrenByAttribute('class', $class, 'equals', 'total', $recursive);
	}
	function getChildrenByName($name, $recursive = true) {
		return $this->getChildrenByAttribute('name', $name, 'equals', 'total', $recursive);
	}
	function select($query = '*', $index = false, $recursive = true, $check_self = false) {
		$s = new $this->selectClass($this, $query, $check_self, $recursive);
		$res = $s->result;
		unset($s);
		if (is_array($res) && ($index === true) && (count($res) === 1)) {
			return $res[0];
		} elseif (is_int($index) && is_array($res)) {
			if ($index < 0) {
				$index += count($res);
			}
			return ($index < count($res)) ? $res[$index] : null;
		} else {
			return $res;
		}
	}
	protected function filter_root() {
		return (strtolower($this->tag) === 'html');
	}
	protected function filter_nchild($n) {
		return ($this->index(false) === (int) $n);
	}
	protected function filter_gt($n) {
		return ($this->index(false) > (int) $n);
	}
	protected function filter_lt($n) {
		return ($this->index(false) < (int) $n);
	}
	protected function filter_nlastchild($n) {
		if ($this->parent === null) {
			return false;
		} else {
			return ($this->parent->childCount(true) - 1 - $this->index(false) === (int) $n);
		}
	}
	protected function filter_ntype($n) {
		return ($this->typeIndex() === (int) $n);
	}
	protected function filter_nlastype($n) {
		if ($this->parent === null) {
			return false;
		} else {
			return (count($this->parent->getChildrenByTag($this->tag, 'total', false)) - 1 - $this->typeIndex() === (int) $n);
		}
	}
	protected function filter_odd() {
		return (($this->index(false) & 1) === 1);
	}
	protected function filter_even() {
		return (($this->index(false) & 1) === 0);
	}
	protected function filter_every($n) {
		return (($this->index(false) % (int) $n) === 0);
	}
	protected function filter_first() {
		return ($this->index(false) === 0);
	}
	protected function filter_last() {
		if ($this->parent === null) {
			return false;
		} else {
			return ($this->parent->childCount(true) - 1 === $this->index(false));
		}
	}
	protected function filter_firsttype() {
		return ($this->typeIndex() === 0);
	}
	protected function filter_lasttype() {
		if ($this->parent === null) {
			return false;
		} else {
			return (count($this->parent->getChildrenByTag($this->tag, 'total', false)) - 1 === $this->typeIndex());
		}
	}
	protected function filter_onlychild() {
		if ($this->parent === null) {
			return false;
		} else {
			return ($this->parent->childCount(true) === 1);
		}
	}
	protected function filter_onlytype() {
		if ($this->parent === null) {
			return false;
		} else {
			return (count($this->parent->getChildrenByTag($this->tag, 'total', false)) === 1);
		}
	}
	protected function filter_empty() {
		return ($this->childCount() === 0);
	}
	protected function filter_notempty() {
		return ($this->childCount() !== 0);
	}
	protected function filter_hastext() {
		return ($this->getPlainText() !== '');
	}
	protected function filter_notext() {
		return ($this->getPlainText() === '');
	}
	protected function filter_lang($lang) {
		return ($this->lang === $lang);
	}
	protected function filter_contains($text) {
		return (strpos($this->getPlainText(), $text) !== false);
	}
	protected function filter_has($selector) {
		$s = $this->select((string) $selector, false);
		return (is_array($s) && (count($s) > 0));
	}
	protected function filter_not($selector) {
		$s = $this->select((string) $selector, false, true, true);
		return ((!is_array($s)) || (array_search($this, $s, true) === false));
	}
	protected function filter_element() {
		return true;
	}
	protected function filter_text() {
		return false;
	}
	protected function filter_comment() {
		return false;
	}
}
class HTML_NODE_TEXT extends HTML_Node {
	const NODE_TYPE = self::NODE_TEXT;
	var $tag = '~text~';
	var $text = '';
	function __construct($parent, $text = '') {
		$this->parent = $parent;
		$this->text = $text;
	}
	function isText() {return true;}
	function isTextOrComment() {return true;}
	protected function filter_element() {return false;}
	protected function filter_text() {return true;}
	function toString_attributes() {return '';}
	function toString_content($attributes = true, $recursive = true, $content_only = false) {return $this->text;}
	function toString($attributes = true, $recursive = true, $content_only = false) {return $this->text;}
}
class HTML_NODE_COMMENT extends HTML_Node {
	const NODE_TYPE = self::NODE_COMMENT;
	var $tag = '~comment~';
	var $text = '';
	function __construct($parent, $text = '') {
		$this->parent = $parent;
		$this->text = $text;
	}
	function isComment() {return true;}
	function isTextOrComment() {return true;}	
	protected function filter_element() {return false;}
	protected function filter_comment() {return true;}
	function toString_attributes() {return '';}
	function toString_content($attributes = true, $recursive = true, $content_only = false) {return $this->text;}
	function toString($attributes = true, $recursive = true, $content_only = false) {return '<!--'.$this->text.'-->';}
}
class HTML_NODE_CONDITIONAL extends HTML_Node {
	const NODE_TYPE = self::NODE_CONDITIONAL;
	var $tag = '~conditional~';
	var $condition = '';
	function __construct($parent, $condition = '', $hidden = true) {
		$this->parent = $parent;
		$this->hidden = $hidden;
		$this->condition = $condition;
	}
	protected function filter_element() {return false;}
	function toString_attributes() {return '';}
	function toString($attributes = true, $recursive = true, $content_only = false) {
		if ($content_only) {
			if (is_int($content_only)) {
				--$content_only;
			}
			return $this->toString_content($attributes, $recursive, $content_only);
		}
		$s = '<!'.(($this->hidden) ? '--' : '').'['.$this->condition.']>';
		if($recursive) {
			$s .= $this->toString_content($attributes);
		}
		$s .= '<![endif]'.(($this->hidden) ? '--' : '').'>';
		return $s;
	}
}
class HTML_NODE_CDATA extends HTML_Node {
	const NODE_TYPE = self::NODE_CDATA;
	var $tag = '~cdata~';
	var $text = '';
	function __construct($parent, $text = '') {
		$this->parent = $parent;
		$this->text = $text;
	}
	protected function filter_element() {return false;}
	function toString_attributes() {return '';}
	function toString_content($attributes = true, $recursive = true, $content_only = false) {return $this->text;}
	function toString($attributes = true, $recursive = true, $content_only = false) {return '<![CDATA['.$this->text.']]>';}
}
class HTML_NODE_DOCTYPE extends HTML_Node {
	const NODE_TYPE = self::NODE_DOCTYPE;
	var $tag = '!DOCTYPE';
	var $dtd = '';
	function __construct($parent, $dtd = '') {
		$this->parent = $parent;
		$this->dtd = $dtd;
	}
	protected function filter_element() {return false;}
	function toString_attributes() {return '';}
	function toString_content($attributes = true, $recursive = true, $content_only = false) {return $this->text;}
	function toString($attributes = true, $recursive = true, $content_only = false) {return '<'.$this->tag.' '.$this->dtd.'>';}
}
class HTML_NODE_EMBEDDED extends HTML_Node {
	var $tag_char = '';
	var $text = '';
	function __construct($parent, $tag_char = '', $tag = '', $text = '', $attributes = array()) {
		$this->parent = $parent;
		$this->tag_char = $tag_char;
		if ($tag[0] !== $this->tag_char) {
			$tag = $this->tag_char.$tag;
		}
		$this->tag = $tag;
		$this->text = $text;
		$this->attributes = $attributes;
		$this->self_close_str = $tag_char;
	}
	protected function filter_element() {return false;}
	function toString($attributes = true, $recursive = true, $content_only = false) {
		$s = '<'.$this->tag;
		if ($attributes) {
			$s .= $this->toString_attributes();
		}
		$s .= $this->text.$this->self_close_str.'>';
		return $s;
	}
}
class HTML_NODE_XML extends HTML_NODE_EMBEDDED {
	const NODE_TYPE = self::NODE_XML;
	function __construct($parent, $tag = 'xml', $text = '', $attributes = array()) {
		return parent::__construct($parent, '?', $tag, $text, $attributes);
	}
}
class HTML_NODE_ASP extends HTML_NODE_EMBEDDED {
	const NODE_TYPE = self::NODE_ASP;
	function __construct($parent, $tag = '', $text = '', $attributes = array()) {
		return parent::__construct($parent, '%', $tag, $text, $attributes);
	}
}
//END gan_node_html.php

//START gan_selector_html.php
class Tokenizer_CSSQuery extends Tokenizer_Base {
	const TOK_BRACKET_OPEN = 100;
	const TOK_BRACKET_CLOSE = 101;
	const TOK_BRACE_OPEN = 102;
	const TOK_BRACE_CLOSE = 103;
	const TOK_STRING = 104;
	const TOK_COLON = 105;
	const TOK_COMMA = 106;
	const TOK_NOT = 107;
	const TOK_ALL = 108;
	const TOK_PIPE = 109;
	const TOK_PLUS = 110;
	const TOK_SIBLING = 111;
	const TOK_CLASS = 112;
	const TOK_ID = 113;
	const TOK_CHILD = 114;
	const TOK_COMPARE_PREFIX = 115;
	const TOK_COMPARE_CONTAINS = 116;
	const TOK_COMPARE_CONTAINS_WORD = 117;
	const TOK_COMPARE_ENDS = 118;
	const TOK_COMPARE_EQUALS = 119;
	const TOK_COMPARE_NOT_EQUAL = 120;
	const TOK_COMPARE_BIGGER_THAN = 121;
	const TOK_COMPARE_SMALLER_THAN = 122;
	const TOK_COMPARE_REGEX = 123;
	const TOK_COMPARE_STARTS = 124;
	var $identifiers = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_-?';
	var $custom_char_map = array(
		'.' => self::TOK_CLASS,
		'#' => self::TOK_ID,
		',' => self::TOK_COMMA,
		'>' => 'parse_gt',
		'+' => self::TOK_PLUS,
		'~' => 'parse_sibling',
		'|' => 'parse_pipe',
		'*' => 'parse_star',
		'$' => 'parse_compare',
		'=' => self::TOK_COMPARE_EQUALS,
		'!' => 'parse_not',
		'%' => 'parse_compare',
		'^' => 'parse_compare',
		'<' => 'parse_compare',
		'"' => 'parse_string',
		"'" => 'parse_string',
		'(' => self::TOK_BRACE_OPEN,
		')' => self::TOK_BRACE_CLOSE,
		'[' => self::TOK_BRACKET_OPEN,
		']' => self::TOK_BRACKET_CLOSE,
		':' => self::TOK_COLON
	);
	protected function parse_gt() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			++$this->pos;
			return ($this->token = self::TOK_COMPARE_BIGGER_THAN);
		} else {
			return ($this->token = self::TOK_CHILD);
		}
	}
	protected function parse_sibling() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			++$this->pos;
			return ($this->token = self::TOK_COMPARE_CONTAINS_WORD);
		} else {
			return ($this->token = self::TOK_SIBLING);
		}
	}
	protected function parse_pipe() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			++$this->pos;
			return ($this->token = self::TOK_COMPARE_PREFIX);
		} else {
			return ($this->token = self::TOK_PIPE);
		}
	}
	protected function parse_star() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			++$this->pos;
			return ($this->token = self::TOK_COMPARE_CONTAINS);
		} else {
			return ($this->token = self::TOK_ALL);
		}
	}
	protected function parse_not() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			++$this->pos;
			return ($this->token = self::TOK_COMPARE_NOT_EQUAL);
		} else {
			return ($this->token = self::TOK_NOT);
		}
	}
	protected function parse_compare() {
		if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === '=')) {
			switch($this->doc[$this->pos++]) {
				case '$':
					return ($this->token = self::TOK_COMPARE_ENDS);
				case '%':
					return ($this->token = self::TOK_COMPARE_REGEX);
				case '^':
					return ($this->token = self::TOK_COMPARE_STARTS);
				case '<':
					return ($this->token = self::TOK_COMPARE_SMALLER_THAN);
			}
		}
		return false;
	}
	protected function parse_string() {
		$char = $this->doc[$this->pos];
		while (true) {
			if ($this->next_search($char.'\\', false) !== self::TOK_NULL) {
				if($this->doc[$this->pos] === $char) {
					break;
				} else {
					++$this->pos;
				}
			} else {
				$this->pos = $this->size - 1;
				break;
			}
		}
		return ($this->token = self::TOK_STRING);
	}
}
class HTML_Selector {
	var $parser = 'Tokenizer_CSSQuery';
	var $root = null;
	var $query = '';
	var $result = array();
	var $search_root = false;
	var $search_recursive = true;
	var $custom_filter_map = array();
	function __construct($root, $query = '*', $search_root = false, $search_recursive = true, $parser = null) {
		if ($parser === null) {
			$parser = new $this->parser();
		}
		$this->parser = $parser;
		$this->root =& $root;
		$this->search_root = $search_root;
		$this->search_recursive = $search_recursive;
		$this->select($query);
	}
	function __toString() {
		return $this->query;
	}
	function __invoke($query = '*') {
		return $this->select($query);
	}
	function select($query = '*') {
		$this->parser->setDoc($query);
		$this->query = $query;
		return (($this->parse()) ? $this->result : false);
	}
	protected function error($error) {
		$error = htmlentities(str_replace(
			array('%tok%', '%pos%'),
			array($this->parser->getTokenString(), (int) $this->parser->getPos()),
			$error
		));
		trigger_error($error);
	}
	protected function parse_getIdentifier($do_error = true) {
		$p =& $this->parser;
		$tok = $p->token;
		if ($tok === Tokenizer_CSSQuery::TOK_IDENTIFIER) {
			return $p->getTokenString();
		} elseif($tok === Tokenizer_CSSQuery::TOK_STRING) {
			return str_replace(array('\\\'', '\\"', '\\\\'), array('\'', '"', '\\'), $p->getTokenString(1, -1));
		} elseif ($do_error) {
			$this->error('Expected identifier at %pos%!');
		}
		return false;
	}
	protected function parse_conditions() {
		$p =& $this->parser;
		$tok = $p->token;
		if ($tok === Tokenizer_CSSQuery::TOK_NULL) {
			$this->error('Invalid search pattern(1): Empty string!');
			return false;
		}
		$conditions_all = array();
		while ($tok !== Tokenizer_CSSQuery::TOK_NULL) {
			$conditions = array('tags' => array(), 'attributes' => array());
			if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
				$tok = $p->next();
				if (($tok === Tokenizer_CSSQuery::TOK_PIPE) && ($tok = $p->next()) && ($tok !== Tokenizer_CSSQuery::TOK_ALL)) {
					if (($tag = $this->parse_getIdentifier()) === false) {
						return false;
					}
					$conditions['tags'][] = array(
						'tag' => $tag,
						'compare' => 'name'
					);
					$tok = $p->next_no_whitespace();
				} else {
					$conditions['tags'][''] = array(
						'tag' => '',
						'match' => false
					);
					if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
						$tok = $p->next_no_whitespace();
					}
				}
			} elseif ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
				$tok = $p->next();
				if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
					$conditions['tags'][] = array(
						'tag' => '',
						'compare' => 'namespace',
					);
				} elseif (($tag = $this->parse_getIdentifier()) !== false) {
					$conditions['tags'][] = array(
						'tag' => $tag,
						'compare' => 'total',
					);
				} else {
					return false;
				}
				$tok = $p->next_no_whitespace();
			} elseif ($tok === Tokenizer_CSSQuery::TOK_BRACE_OPEN) {
				$tok = $p->next_no_whitespace();
				$last_mode = 'or';
				while (true) {
					$match = true;
					$compare = 'total';
					if ($tok === Tokenizer_CSSQuery::TOK_NOT) {
						$match = false;
						$tok = $p->next_no_whitespace();
					}
					if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
						$tok = $p->next();
						if ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
							$this->next();
							$compare = 'name';
							if (($tag = $this->parse_getIdentifier()) === false) {
								return false;
							}
						}
					} elseif ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
						$tok = $p->next();
						if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
							$tag = '';
							$compare = 'namespace';
						} elseif (($tag = $this->parse_getIdentifier()) === false) {
							return false;
						}
						$tok = $p->next_no_whitespace();
					} else {
						if (($tag = $this->parse_getIdentifier()) === false) {
							return false;
						}
						$tok = $p->next();
						if ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
							$tok = $p->next();
							if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
								$compare = 'namespace';
							} elseif (($tag_name = $this->parse_getIdentifier()) !== false) {
								$tag = $tag.':'.$tag_name;
							} else {
								return false;
							}
							$tok = $p->next_no_whitespace();
						}
					}
					if ($tok === Tokenizer_CSSQuery::TOK_WHITESPACE) {
						$tok = $p->next_no_whitespace();
					}
					$conditions['tags'][] = array(
						'tag' => $tag,
						'match' => $match,
						'operator' => $last_mode,
						'compare' => $compare
					);
					switch($tok) {
						case Tokenizer_CSSQuery::TOK_COMMA:
							$tok = $p->next_no_whitespace();
							$last_mode = 'or';
							continue 2;
						case Tokenizer_CSSQuery::TOK_PLUS:
							$tok = $p->next_no_whitespace();
							$last_mode = 'and';
							continue 2;
						case Tokenizer_CSSQuery::TOK_BRACE_CLOSE:
							$tok = $p->next();
							break 2;
						default:
							$this->error('Expected closing brace or comma at pos %pos%!');
							return false;
					}
				}
			} elseif (($tag = $this->parse_getIdentifier(false)) !== false) {
				$tok = $p->next();
				if ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
					$tok = $p->next();
					if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
						$conditions['tags'][] = array(
							'tag' => $tag,
							'compare' => 'namespace'
						);
					} elseif (($tag_name = $this->parse_getIdentifier()) !== false) {
						$tag = $tag.':'.$tag_name;
						$conditions['tags'][] = array(
							'tag' => $tag,
							'match' => true
						);
					} else {
						return false;
					}
					$tok = $p->next();
				} else {
					$conditions['tags'][] = array(
						'tag' => $tag,
						'match' => true
					);
				}
			} else {
				unset($conditions['tags']);
			}
			$last_mode = 'or';
			if ($tok === Tokenizer_CSSQuery::TOK_CLASS) {
				$p->next();
				if (($class = $this->parse_getIdentifier()) === false) {
					return false;
				}
				$conditions['attributes'][] = array(
					'attribute' => 'class',
					'operator_value' => 'contains_word',
					'value' => $class,
					'operator_result' => $last_mode
				);
				$last_mode = 'and';
				$tok = $p->next();
			}
			if ($tok === Tokenizer_CSSQuery::TOK_ID) {
				$p->next();
				if (($id = $this->parse_getIdentifier()) === false) {
					return false;
				}
				$conditions['attributes'][] = array(
					'attribute' => 'id',
					'operator_value' => 'equals',
					'value' => $id,
					'operator_result' => $last_mode
				);
				$last_mode = 'and';
				$tok = $p->next();
			}
			if ($tok === Tokenizer_CSSQuery::TOK_BRACKET_OPEN) {
				$tok = $p->next_no_whitespace();
				while (true) {
					$match = true;
					$compare = 'total';
					if ($tok === Tokenizer_CSSQuery::TOK_NOT) {
						$match = false;
						$tok = $p->next_no_whitespace();
					}
					if ($tok === Tokenizer_CSSQuery::TOK_ALL) {
						$tok = $p->next();
						if ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
							$tok = $p->next();
							if (($attribute = $this->parse_getIdentifier()) === false) {
								return false;
							}
							$compare = 'name';
							$tok = $p->next();
						} else {
							$this->error('Expected pipe at pos %pos%!');
							return false;
						}
					} elseif ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
						$tok = $p->next();
						if (($tag = $this->parse_getIdentifier()) === false) {
							return false;
						}
						$tok = $p->next_no_whitespace();
					} elseif (($attribute = $this->parse_getIdentifier()) !== false) {
						$tok = $p->next();
						if ($tok === Tokenizer_CSSQuery::TOK_PIPE) {
							$tok = $p->next();
							if (($attribute_name = $this->parse_getIdentifier()) !== false) {
								$attribute = $attribute.':'.$attribute_name;
							} else {
								return false;
							}
							$tok = $p->next();
						}
					} else {
						return false;
					}
					if ($tok === Tokenizer_CSSQuery::TOK_WHITESPACE) {
						$tok = $p->next_no_whitespace();
					}
					$operator_value = '';
					$val = '';
					switch($tok) {
						case Tokenizer_CSSQuery::TOK_COMPARE_PREFIX:
						case Tokenizer_CSSQuery::TOK_COMPARE_CONTAINS:
						case Tokenizer_CSSQuery::TOK_COMPARE_CONTAINS_WORD:
						case Tokenizer_CSSQuery::TOK_COMPARE_ENDS:
						case Tokenizer_CSSQuery::TOK_COMPARE_EQUALS:
						case Tokenizer_CSSQuery::TOK_COMPARE_NOT_EQUAL:
						case Tokenizer_CSSQuery::TOK_COMPARE_REGEX:
						case Tokenizer_CSSQuery::TOK_COMPARE_STARTS:
						case Tokenizer_CSSQuery::TOK_COMPARE_BIGGER_THAN:
						case Tokenizer_CSSQuery::TOK_COMPARE_SMALLER_THAN:
							$operator_value = $p->getTokenString(($tok === Tokenizer_CSSQuery::TOK_COMPARE_EQUALS) ? 0 : -1);
							$p->next_no_whitespace();
							if (($val = $this->parse_getIdentifier()) === false) {
								return false;
							}
							$tok = $p->next_no_whitespace();
							break;
					}
					if ($operator_value && $val) {
						$conditions['attributes'][] = array(
							'attribute' => $attribute,
							'operator_value' => $operator_value,
							'value' => $val,
							'match' => $match,
							'operator_result' => $last_mode,
							'compare' => $compare
						);
					} else {
						$conditions['attributes'][] = array(
							'attribute' => $attribute,
							'value' => $match,
							'operator_result' => $last_mode,
							'compare' => $compare
						);
					}
					switch($tok) {
						case Tokenizer_CSSQuery::TOK_COMMA:
							$tok = $p->next_no_whitespace();
							$last_mode = 'or';
							continue 2;
						case Tokenizer_CSSQuery::TOK_PLUS:
							$tok = $p->next_no_whitespace();
							$last_mode = 'and';
							continue 2;
						case Tokenizer_CSSQuery::TOK_BRACKET_CLOSE:
							$tok = $p->next();
							break 2;
						default:
							$this->error('Expected closing bracket or comma at pos %pos%!');
							return false;
					}
				}
			}
			if (count($conditions['attributes']) < 1) {
				unset($conditions['attributes']);
			}
			while($tok === Tokenizer_CSSQuery::TOK_COLON) {
				if (count($conditions) < 1) {
					$conditions['tags'] = array(array(
						'tag' => '',
						'match' => false
					));
				}
				$tok = $p->next();
				if (($filter = $this->parse_getIdentifier()) === false) {
					return false;
				}
				if (($tok = $p->next()) === Tokenizer_CSSQuery::TOK_BRACE_OPEN) {
					$start = $p->pos;
					$count = 1;
					while ((($tok = $p->next()) !== Tokenizer_CSSQuery::TOK_NULL) && !(($tok === Tokenizer_CSSQuery::TOK_BRACE_CLOSE) && (--$count === 0))) {
						if ($tok === Tokenizer_CSSQuery::TOK_BRACE_OPEN) {
							++$count;
						}
					}
					if ($tok !== Tokenizer_CSSQuery::TOK_BRACE_CLOSE) {
						$this->error('Expected closing brace at pos %pos%!');
						return false;
					}
					$len = $p->pos - 1 - $start;
					$params = (($len > 0) ? substr($p->doc, $start + 1, $len) : '');
					$tok = $p->next();
				} else {
					$params = '';
				}
				$conditions['filters'][] = array('filter' => $filter, 'params' => $params);
			}
			if (count($conditions) < 1) {
				$this->error('Invalid search pattern(2): No conditions found!');
				return false;
			}
			$conditions_all[] = $conditions;
			if ($tok === Tokenizer_CSSQuery::TOK_WHITESPACE) {
				$tok = $p->next_no_whitespace();
			}
			if ($tok === Tokenizer_CSSQuery::TOK_COMMA) {
				$tok = $p->next_no_whitespace();
				continue;
			} else {
				break;
			}
		}
		return $conditions_all;
	}
	protected function parse_callback($conditions, $recursive = true, $check_root = false) {
		return ($this->result = $this->root->getChildrenByMatch(
			$conditions,
			$recursive,
			$check_root,
			$this->custom_filter_map
		));
	}
	protected function parse_single($recursive = true) {
		if (($c = $this->parse_conditions()) === false) {
			return false;
		}
		$this->parse_callback($c, $recursive, $this->search_root);
		return true;
	}
	protected function parse_adjacent() {
		$tmp = $this->result;
		$this->result = array();
		if (($c = $this->parse_conditions()) === false) {
			return false;
		}
		foreach($tmp as $t) {
			if (($sibling = $t->getNextSibling()) !== false) {
				if ($sibling->match($c, true, $this->custom_filter_map)) {
					$this->result[] = $sibling;
				}
			}
		}
		return true;
	}
	protected function parse_result($parent = false, $recursive = true) {
		$tmp = $this->result;
		$tmp_res = array();
		if (($c = $this->parse_conditions()) === false) {
			return false;
		}
		foreach(array_keys($tmp) as $t) {
			$this->root = (($parent) ? $tmp[$t]->parent : $tmp[$t]);
			$this->parse_callback($c, $recursive);
			foreach(array_keys($this->result) as $r) {
				if (!in_array($this->result[$r], $tmp_res, true)) {
					$tmp_res[] = $this->result[$r];
				}
			}
		}
		$this->result = $tmp_res;
		return true;
	}
	protected function parse() {
		$p =& $this->parser;
		$p->setPos(0);
		$this->result = array();
		if (!$this->parse_single()) {
			return false;
		}
		while (count($this->result) > 0) {
			switch($p->token) {
				case Tokenizer_CSSQuery::TOK_CHILD:
					$this->parser->next_no_whitespace();
					if (!$this->parse_result(false, 1)) {
						return false;
					}
					break;
				case Tokenizer_CSSQuery::TOK_SIBLING:
					$this->parser->next_no_whitespace();
					if (!$this->parse_result(true, 1)) {
						return false;
					}
					break;
				case Tokenizer_CSSQuery::TOK_PLUS:
					$this->parser->next_no_whitespace();
					if (!$this->parse_adjacent()) {
						return false;
					}
					break;
				case Tokenizer_CSSQuery::TOK_ALL:
				case Tokenizer_CSSQuery::TOK_IDENTIFIER:
				case Tokenizer_CSSQuery::TOK_STRING:
				case Tokenizer_CSSQuery::TOK_BRACE_OPEN:
				case Tokenizer_CSSQuery::TOK_BRACKET_OPEN:
				case Tokenizer_CSSQuery::TOK_ID:
				case Tokenizer_CSSQuery::TOK_CLASS:
				case Tokenizer_CSSQuery::TOK_COLON:
					if (!$this->parse_result()) {
						return false;
					}
					break;
				case Tokenizer_CSSQuery::TOK_NULL:
					break 2;
				default:
					$this->error('Invalid search pattern(3): No result modifier found!');
					return false;
			}
		}
		return true;
	}
}
//END gan_selector_html.php

//START gan_formatter.php
function indent_text($text, $indent, $indent_string = '  ') {
	if ($indent && $indent_string) {
		return str_replace("\n", "\n".str_repeat($indent_string, $indent), $text);
	} else {
		return $text;
	}
}
class HTML_Formatter {
	var $block_elements = array(
		'p' =>			array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h1' => 		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h2' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h3' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h4' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h5' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'h6' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'form' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'fieldset' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'legend' =>  	array('new_line' => true,  'as_block' => false, 'format_inside' => true),
		'dl' =>  		array('new_line' => true,  'as_block' => false, 'format_inside' => true),
		'dt' =>  		array('new_line' => true,  'as_block' => false, 'format_inside' => true),
		'dd' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'ol' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'ul' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'li' =>  		array('new_line' => true,  'as_block' => false, 'format_inside' => true),
		'table' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'tr' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'dir' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'menu' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'address' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'blockquote' => array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'center' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'del' =>  		array('new_line' => true,  'as_block' => false, 'format_inside' => true),
		'hr' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'ins' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'noscript' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'pre' =>  		array('new_line' => true,  'as_block' => true,  'format_inside' => false),
		'script' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'style' =>  	array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'html' => 		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'head' => 		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'body' => 		array('new_line' => true,  'as_block' => true,  'format_inside' => true),
		'title' => 		array('new_line' => true,  'as_block' => false, 'format_inside' => false)
	);
	var $whitespace = array(
		' ' => false,
		"\t" => false,
		"\x0B" => false,
		"\0" => false,
		"\n" => true,
		"\r" => true
	);
	var $indent_string = ' ';
	var $linebreak_string = "\n";
	var $options = array(
		'img_alt' => '',
		'self_close_str' => null,
		'attribute_shorttag' => false,
		'sort_attributes' => false,
		'attributes_case' => CASE_LOWER,
		'minify_script' => true
	);
	var $errors = array();
	function __construct($options = array()) {
		$this->options = array_merge($this->options, $options);
	}
	function __invoke(&$node) {
		return $this->format($node);
	}
	static function minify_html(&$root, $strip_comments = true, $recursive = true) {
		if ($strip_comments) {
			foreach($root->select(':comment', false, $recursive, true) as $c) {
				$prev = $c->getSibling(-1);
				$next = $c->getSibling(1);
				$c->delete();
				if ($prev && $next && ($prev->isText()) && ($next->isText())) {
					$prev->text .= $next->text;
					$next->delete();
				}
			}
		}
		foreach($root->select('(!pre + !xmp + !style + !script + !"?php" + !"~text~" + !"~comment~"):not-empty > "~text~"', false, $recursive, true) as $c) {
			$c->text = preg_replace('`\s+`', ' ', $c->text);
		}
	}
	static function minify_javascript(&$root, $indent_string = ' ', $wrap_comment = true, $recursive = true) {
		include_once('third party/jsminplus.php');
		$errors = array();
		foreach($root->select('script:not-empty > "~text~"', false, $recursive, true) as $c) {
			try {
				$text = $c->text;
				while ($text) {
					$text = trim($text);
					if (substr($text, 0, 4) === '<!--') {
						$text = substr($text, 5);
						continue;
					} elseif (strtolower(substr($text, 0, 9)) === '<![cdata[') {
						$text = substr($text, 10);
						continue;
					}
					if (($end = substr($text, -3)) && (($end === '-->') || ($end === ']]>'))) {
						$text = substr($text, 0, -3);
						continue;
					}
					break;
				}
				if (trim($text)) {
					$text = JSMinPlus::minify($text);
					if ($wrap_comment) {
						$text = "<!--\n".$text."\n//-->";
					}
					if ($indent_string && ($wrap_comment || (strpos($text, "\n") !== false))) {
						$text = indent_text("\n".$text, $c->indent(), $indent_string);
					}
				}
				$c->text = $text;
			} catch (Exception $e) {
				$errors[] = array($e, $c->parent->dumpLocation());
			}
		}
		return (($errors) ? $errors : true);
	}
	function format_html(&$root, $recursive = null) {
		if ($recursive === null) {
			$recursive = true;
			self::minify_html($root);
		} elseif (is_int($recursive)) {
			$recursive = (($recursive > 1) ? $recursive - 1 : false);
		}
		$root_tag = strtolower($root->tag);
		$in_block = isset($this->block_elements[$root_tag]) && $this->block_elements[$root_tag]['as_block'];
		$child_count = count($root->children);
		if (isset($this->options['attributes_case']) && $this->options['attributes_case']) {
			$root->attributes = array_change_key_case($root->attributes, $this->options['attributes_case']);
			$root->attributes_ns = null;
		}	
		if (isset($this->options['sort_attributes']) && $this->options['sort_attributes']) {
			if ($this->options['sort_attributes'] === 'reverse') {
				krsort($root->attributes);
			} else {
				ksort($root->attributes);
			}
		}
		if ($root->select(':element', true, false, true)) {
			$root->setTag(strtolower($root->tag), true);
			if (($this->options['img_alt'] !== null) && ($root_tag === 'img') && (!isset($root->alt))) {
				$root->alt = $this->options['img_alt'];
			}
		}
		if ($this->options['self_close_str'] !== null) {
			$root->self_close_str = $this->options['self_close_str'];
		}
		if ($this->options['attribute_shorttag'] !== null) {
			$root->attribute_shorttag = $this->options['attribute_shorttag'];
		}
		$prev = null;
		$n_tag = '';
		$prev_tag = '';
		$as_block = false;
		$prev_asblock = false;
		for($i = 0; $i < $child_count; $i++) {
			$n =& $root->children[$i];
			$indent = $n->indent();
			if (!$n->isText()) {
				$n_tag = strtolower($n->tag);
				$new_line = isset($this->block_elements[$n_tag]) && $this->block_elements[$n_tag]['new_line'];
				$as_block = isset($this->block_elements[$n_tag]) && $this->block_elements[$n_tag]['as_block'];
				$format_inside = ((!isset($this->block_elements[$n_tag])) || $this->block_elements[$n_tag]['format_inside']);
				if ($prev && ($prev->isText()) && $prev->text && ($char = $prev->text[strlen($prev->text) - 1]) && isset($this->whitespace[$char])) {
					if ($this->whitespace[$char]) {
						$prev->text .= str_repeat($this->indent_string, $indent);
					} else {
						$prev->text = substr_replace($prev->text, $this->linebreak_string.str_repeat($this->indent_string, $indent), -1, 1);
					}
				} elseif (($new_line || $prev_asblock || ($in_block && ($i === 0)))){
					if ($prev && ($prev->isText())) {
						$prev->text .= $this->linebreak_string.str_repeat($this->indent_string, $indent);
					} else {
						$root->addText($this->linebreak_string.str_repeat($this->indent_string, $indent), $i);
						++$child_count;
					}
				}
				if ($format_inside && count($n->children)) {
					$last = $n->children[count($n->children) - 1];
					$last_tag = ($last) ? strtolower($last->tag) : '';
					$last_asblock = ($last_tag && isset($this->block_elements[$last_tag]) && $this->block_elements[$last_tag]['as_block']);
					if (($n->childCount(true) > 0) || (trim($n->getPlainText()))) {
						if ($last && ($last->isText()) && $last->text && ($char = $last->text[strlen($last->text) - 1]) && isset($this->whitespace[$char])) {
							if ($as_block || ($last->index() > 0) || isset($this->whitespace[$last->text[0]])) {
								if ($this->whitespace[$char]) {
									$last->text .= str_repeat($this->indent_string, $indent);
								} else {
									$last->text = substr_replace($last->text, $this->linebreak_string.str_repeat($this->indent_string, $indent), -1, 1);
								}
							}
						} elseif (($as_block || $last_asblock || ($in_block && ($i === 0))) && $last) {
							if ($last && ($last->isText())) {
								$last->text .= $this->linebreak_string.str_repeat($this->indent_string, $indent);
							} else {
								$n->addText($this->linebreak_string.str_repeat($this->indent_string, $indent));
							}
						}
					} elseif (!trim($n->getInnerText())) {
						$n->clear();
					}
					if ($recursive) {
						$this->format_html($n, $recursive);
					}
				}
			} elseif (trim($n->text) && ((($i - 1 < $child_count) && ($char = $n->text[0]) && isset($this->whitespace[$char])) || ($in_block && ($i === 0)))) {
				if (isset($this->whitespace[$char])) {
					if ($this->whitespace[$char]) {
						$n->text = str_repeat($this->indent_string, $indent).$n->text;
					} else {
						$n->text = substr_replace($n->text, $this->linebreak_string.str_repeat($this->indent_string, $indent), 0, 1);
					}
				} else {
					$n->text = $this->linebreak_string.str_repeat($this->indent_string, $indent).$n->text;
				}
			}
			$prev = $n;
			$prev_tag = $n_tag;
			$prev_asblock = $as_block;
		}
		return true;
	}
	function format(&$node) {
		$this->errors = array();
		if ($this->options['minify_script']) {
			$a = self::minify_javascript($node, $this->indent_string, true, true);
			if (is_array($a)) {
				foreach($a as $error) {
					$this->errors[] = $error[0]->getMessage().' >>> '.$error[1];
				}
			}
		}
		return $this->format_html($node);
	}
}
//END gan_formatter.php

?>