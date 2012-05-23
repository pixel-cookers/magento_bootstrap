<?php

/**
 * Copyright (c) 2008-12 Owebia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @website    http://www.owebia.com/
 * @project    Magento Owebia Shipping 2 module
 * @author     Antoine Lemoine
 * @license    http://www.opensource.org/licenses/MIT  The MIT License (MIT)
**/

class OwebiaShippingHelper
{
	const PRODUCT_REGEX = '(?:product|p)';
	const ATTRIBUTE_REGEX = '(?:attribute|attr|a)';
	const OPTION_REGEX = '(?:option|opt|o)';
	const ATTRIBUTE_SET_REGEX = '(?:attribute_set)';
	const CATEGORIES_REGEX = '(?:categories)';
	const CATEGORY_REGEX = '(?:category)';
	const FLOAT_REGEX = '[-]?\d+(?:[.]\d+)?';
	const POSITIVE_FLOAT_REGEX = '\d+(?:[.]\d+)?';
	const COUPLE_REGEX = '(?:[0-9.]+|\*) *(?:\[|\])? *\: *[0-9.]+';

	public static $DEBUG_INDEX_COUNTER = 0;
	public static $UNCOMPRESSED_STRINGS = array(
		' product.attribute.',
		' product.option.',
		'{product.attribute.',
		'{product.option.',
		'{product.',
		'{cart.',
		'{selection.',
	);
	public static $COMPRESSED_STRINGS = array(
		' p.a.',
		' p.o.',
		'{p.a.',
		'{p.o.',
		'{p.',
		'{c.',
		'{s.',
	);

	public static function toString($value) {
		if (!isset($value)) return 'null';
		else if (is_bool($value)) return $value ? 'true' : 'false';
		else if (is_float($value)) return str_replace(',', '.', (string)$value); // To avoid locale problems
		else return $value;
	}

	public static function parseSize($size) {
		$size = trim($size);
		$last = strtolower($size[strlen($size)-1]);
		switch ($last) {
			case 'g': $size *= 1024;
			case 'm': $size *= 1024;
			case 'k': $size *= 1024;
		}
		return (float)$size;
	}

	public static function formatSize($size) {
		$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		return self::toString(@round($size/pow(1024, ($i=floor(log($size, 1024)))), 2)).' '.$unit[$i];
	}

	public static function getDefaultProcessData() {
		$timestamp = time();
		$properties = array(
			'info.server.os' => PHP_OS,
			'info.server.software' => $_SERVER['SERVER_SOFTWARE'],
			'info.php.version' => PHP_VERSION,
			'info.memory.limit' => self::formatSize(self::parseSize(ini_get('memory_limit'))),
			'info.memory.usage' => self::formatSize(memory_get_usage(true)),
			'info.module.version' => '',
			'info.carrier.code' => '',
			'cart.price-tax+discount' => 0,
			'cart.price-tax-discount' => 0,
			'cart.price+tax+discount' => 0,
			'cart.price+tax-discount' => 0,
			'cart.price_excluding_tax' => 0,
			'cart.price_including_tax' => 0,
			'cart.weight' => 0,
			'cart.weight.for-charge' => 0,
			'cart.weight.unit' => 'kg',
			'cart.quantity' => 0,
			'cart.coupon' => null,
			'destination.country.code' => '',
			'destination.country.name' => '',
			'destination.region.code' => '',
			'destination.postcode' => '',
			'origin.country.code' => '',
			'origin.country.name' => '',
			'origin.region.code' => '',
			'origin.postcode' => '',
			'customer.group.id' => '',
			'customer.group.code' => '',
			'free_shipping' => false,
			'store.id' => '',
			'store.code' => '',
			'store.name' => '',
			'store.address' => '',
			'store.phone' => '',
			'date.timestamp' => $timestamp,
			'date.year' => (int)date('Y', $timestamp),
			'date.month' => (int)date('m', $timestamp),
			'date.day' => (int)date('d', $timestamp),
			'date.hour' => (int)date('H', $timestamp),
			'date.minute' => (int)date('i', $timestamp),
			'date.second' => (int)date('s', $timestamp),
		);
		return $properties;
	}

	protected $_input;
	protected $_config = array();
	protected $_messages = array();
	protected $_formula_cache = array();
	protected $_expression_cache = array();
	public $debug_code = null;
	public $debug_output = '';
	public $debug_header = null;
	protected $debug_prefix = '';

	public function __construct($input) {
		$this->_input = $input;
		$this->_parseInput();
	}

	public function addDebugIndent() {
		$this->debug_prefix .= '   ';
	}

	public function removeDebugIndent() {
		$this->debug_prefix = substr($this->debug_prefix, 0, strlen($this->debug_prefix)-3);
	}

	public function debug($text) {
		$this->debug_output .= "<p>".$this->debug_prefix.$text."</p>";
	}

	public function getDebug() {
		$index = $this->debug_code.'-'.self::$DEBUG_INDEX_COUNTER++;
		$output = "<style rel=\"stylesheet\" type=\"text/css\">"
		.".osh-debug{background:#000;color:#bbb;-webkit-opacity:0.9;-moz-opacity:0.9;opacity:0.9;text-align:left;white-space:pre-wrap;overflow:auto;}"
		.".osh-debug p{margin:2px 0;}"
		.".osh-formula{color:#f90;} .osh-key{color:#0099f7;} .osh-loop{color:#ff0;}"
		.".osh-error{color:#f00;} .osh-warning{color:#ff0;} .osh-info{color:#7bf700;}"
		.".osh-debug-content{padding:10px;}"
		.".osh-replacement{color:#ff3000;}"
		."</style>"
		."<div id=\"osh-debug-".$index."\" class=\"osh-debug\"><pre class=\"osh-debug-content\"><span style=\"float:right;cursor:pointer;\" onclick=\"document.getElementById('osh-debug-".$index."').style.display = 'none';\">[<span style=\"padding:0 5px;color:#f00;\">X</span>]</span>"
		."<p>".$this->debug_header."</p>".$this->debug_output."</pre></div>";
		return $output;
	}

	public function initDebug($code, $data) {
		$header = 'DEBUG OwebiaShippingHelper.php<br/>';
		foreach ($data as $key => $data) {
			$header .= '   <span class="osh-key">'.str_replace('.', '</span>.<span class="osh-key">', $key).'</span> = <span class="osh-formula">'.self::toString($data).'</span> ('.gettype($data).')<br/>';
		}
		$this->debug_code = $code;
		$this->debug_header = $header;
	}

	public function getConfig() {
		return $this->_config;
	}
	
	public function getMessages() {
		$messages = $this->_messages;
		$this->_messages = array();
		return $messages;
	}
	
	public function formatConfig($compress, $keys_to_remove=array()) {
		$output = '';
		foreach ($this->_config as $code => $row) {
			if (!isset($row['lines'])) {
				if (isset($row['*comment']['value'])) {
					$output .= trim($row['*comment']['value'])."\n";
				}
				$output .= '{'.($compress ? '' : "\n");
				foreach ($row as $key => $property) {
					if (substr($key, 0, 1)!='*' && !in_array($key, $keys_to_remove)) {
						$value = $property['value'];
						if (isset($property['comment'])) $output .= ($compress ? '' : "\t").'/* '.$property['comment'].' */'.($compress ? '' : "\n");
						$output .= ($compress ? '' : "\t").$key.':'.($compress ? '' : ' ');
						if (is_bool($value)) $output .= $value ? 'true' : 'false';
						else if ((string)((int)$value)==$value) $output .= $value;
						else if ((string)((float)$value)==$value) $output .= ($compress ? (float)$value : $value);
						else $output .= '"'.str_replace('"', '\\"', $value).'"';
						$output .= ','.($compress ? '' : "\n");
					}
				}
				if ($compress) $output = preg_replace('/,$/', '', $output);
				$output .= "}\n".($compress ? '' : "\n");
			} else {
				$output .= $row['lines']."\n";
			}
		}
		return $compress ? $this->compress($output) : $this->uncompress($output);
	}

	public function checkConfig() {
		$timestamp = time();
		$process = array(
			'cart.products' => array(),
			'config' => $this->_config,
			'data' => self::getDefaultProcessData(),
			'result' => null,
		);
		foreach ($this->_config as $code => &$row) {
			$this->processRow($process, $row, $check_all_conditions=true);
			foreach ($row as $property_key => $property_value) {
				if (substr($property_key, 0, 1)!='*') $this->getRowProperty($row, $property_key);
			}
		}
	}

	public function processRow($process, &$row, $is_checking=false) {
		if (!isset($row['*code'])) return;

		$this->debug('process row <span class="osh-key">'.$row['*code'].'</span>');
		if (!isset($row['label']['value'])) $row['label']['value'] = '***';
		
		$enabled = $this->getRowProperty($row, 'enabled');
		if (isset($enabled)) {
			if (!$is_checking && !$enabled) {
				$this->addMessage('info', $row, 'enabled', 'Configuration disabled');
				return new OS_Result(false);
			}
		}

		$conditions = $this->getRowProperty($row, 'conditions');
		if (isset($conditions)) {
			$result = $this->_processFormula($process, $row, 'conditions', $conditions, $is_checking);
			if (!$is_checking) {
				if (!$result->success) return $result;
				if (!$result->result) {
					$this->addMessage('info', $row, 'conditions', "The cart doesn't match conditions");
					return new OS_Result(false);
				}
			}
		}

		$destination = $this->getRowProperty($row, 'destination');
		if (isset($destination)) {
			$destination_match = $this->_addressMatch($destination, array(
				'country_code' => $process['data']['destination.country.code'],
				'region_code' => $process['data']['destination.region.code'],
				'postcode' => $process['data']['destination.postcode']
			));
			if (!$is_checking && !$destination_match) {
				$this->addMessage('info', $row, 'destination', "The shipping method doesn't cover the zone");
				return new OS_Result(false);
			}
		}

		$origin = $this->getRowProperty($row, 'origin');
		if (isset($origin)) {
			$origin_match = $this->_addressMatch($origin, array(
				'country_code' => $process['data']['origin.country.code'],
				'region_code' => $process['data']['origin.region.code'],
				'postcode' => $process['data']['origin.postcode']
			));
			if (!$is_checking && !$origin_match) {
				$this->addMessage('info', $row, 'origin', "The shipping method doesn't match to shipping origin");
				return new OS_Result(false);
			}
		}

		$customer_groups = $this->getRowProperty($row, 'customer_groups');
		if (isset($customer_groups)) {
			$groups = explode(',', $customer_groups);
			$group_match = false;
			//$this->debug('code:'.$process['data']['customer.group.code'].', id:'.$process['data']['customer.group.id']);
			foreach ($groups as $group) {
				$group = trim($group);
				if ($group=='*' || $group==$process['data']['customer.group.code'] || ctype_digit($group) && $group==$process['data']['customer.group.id']) {
					$this->debug('      group <span class="osh-replacement">'.$process['data']['customer.group.code'].'</span>'
						.' (id:<span class="osh-replacement">'.$process['data']['customer.group.id'].'</span>) matches');
					$group_match = true;
					break;
				}
			}
			if (!$is_checking && !$group_match) {
				$this->addMessage('info', $row, 'customer_groups', "The shipping method doesn't match to customer group (%s)", $process['data']['customer.group.code']);
				return new OS_Result(false);
			}
		}

		$fees = $this->getRowProperty($row, 'fees');
		if (isset($fees)) {
			$result = $this->_processFormula($process, $row, 'fees', $fees, $is_checking);
			if (!$result->success) return $result;
			$this->debug('    &raquo; <span class="osh-info">result</span> = <span class="osh-formula">'.self::toString($result->result).'</span>');
			return new OS_Result(true, (float)$result->result);
		}
		return new OS_Result(false);
	}

	public function getRowProperty(&$row, $key, $original_row=null, $original_key=null) {
		$property = null;
		$output = null;
		if (isset($original_row) && isset($original_key) && $original_row['*code']==$row['*code'] && $original_key==$key) {
			$this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">{".$row['*code'].'.'.$key."}</span>");
			return array('error' => 'Infinite loop');
		}
		if (isset($row[$key]['value'])) {
			$property = $row[$key]['value'];
			$output = $property;
			$this->debug('   get <span class="osh-key">'.$row['*code'].'</span>.<span class="osh-key">'.$key.'</span> = <span class="osh-formula">'.self::toString($property).'</span>');
			preg_match_all('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i', $output, $result_set, PREG_SET_ORDER);
			foreach ($result_set as $result) {
				list($original, $ref_code, $ref_key) = $result;
				if (!in_array($ref_code, array('module', 'date', 'store', 'cart', 'product', 'selection', 'customvar'))) {
					if ($ref_code==$row['code']['value'] && $ref_key==$key) {
						$this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">".$original."</span>");
						return null;
					}
					if (isset($this->_config[$ref_code][$ref_key]['value'])) {
						$replacement = $this->getRowProperty($this->_config[$ref_code], $ref_key,
							isset($original_row) ? $original_row : $row, isset($original_key) ? $original_key : $key);
						if (is_array($replacement) && isset($replacement['error'])) {
							return isset($original_row) ? $replacement : 'false';
						}
					} else {
						//$this->addMessage('error', $row, $key, 'Non-existent property %s', "<span class=\"code\">".$original."</span>");
						$replacement = $original;//'null';
					}
					$output = $this->replace($original, $replacement, $output);
				}
			}
		} else {
			$this->debug('   get <span class="osh-key">'.$row['*code'].'</span>.<span class="osh-key">'.$key.'</span> = <span class="osh-formula">null</span>');
		}
		return $output;
	}
	
	protected function replace($from, $to, $input) {
		if ($from===$to) return $input;
		if (mb_strpos($input, $from)===false) return $input;
		$to = self::toString($to);
		$to = preg_replace('/[\r\n\t]+/', ' ', $to);
		$this->debug('      replace <span class="osh-replacement">'.self::toString($from).'</span> by <span class="osh-replacement">'.$to.'</span> =&gt; <span class="osh-formula">'.str_replace($from, '<span class="osh-replacement">'.$to.'</span>', $input).'</span>');
		return str_replace($from, $to, $input);
	}

	protected function _min() {
		$args = func_get_args();
		$min = null;
		foreach ($args as $arg) {
			if (isset($arg) && (!isset($min) || $min>$arg)) $min = $arg;
		}
		return $min;
	}

	protected function _max() {
		$args = func_get_args();
		$max = null;
		foreach ($args as $arg) {
			if (isset($arg) && (!isset($max) || $max<$arg)) $max = $arg;
		}
		return $max;
	}

	protected function _processFormula($process, &$row, $property_key, $formula_string, $is_checking, $use_cache=true)
	{
		$result = $this->_prepareFormula($process, $row, $property_key, $formula_string, $is_checking, $use_cache);
		if (!$result->success) return $result;

		$eval_result = $this->_evalFormula($result->result);
		if (!isset($eval_result)) {
			$this->addMessage('error', $row, $property_key, 'Invalid formula');
			$result = new OS_Result(false);
			if ($use_cache) $this->setCache($formula_string, $result);
			return $result;
		}
		$result = new OS_Result(true, $eval_result);
		if ($use_cache) $this->setCache($formula_string, $result);
		return $result;
	}

	public function evalInput($process, $row, $property_key, $input) {
		$result = $this->_prepareFormula($process, $row, $property_key, $input, $is_checking=false, $use_cache=true);
		return $result->success ? $result->result : $input;
	}

	protected function setCache($expression, $value) {
		if ($value instanceof OS_Result) {
			$this->_formula_cache[$expression] = $value;
			$this->debug('      cache <span class="osh-replacement">'.$expression.'</span> = <span class="osh-formula">'.self::toString($value->result).'</span> ('.gettype($value->result).')');
		} else {
			$this->_expression_cache[$expression] = $value; //self::toString($value); // In order to make isset work
			$this->debug('      cache <span class="osh-replacement">'.$expression.'</span> = <span class="osh-formula">'.self::toString($value).'</span> ('.gettype($value).')');
		}
	}

	protected function _getCachedExpression($original) {
		$replacement = $this->_expression_cache[$original];
		$this->debug('      get cached expression <span class="osh-replacement">'.$original.'</span> = <span class="osh-formula">'.self::toString($replacement).'</span> ('.gettype($replacement).')');
		return $replacement;
	}

	protected function _prepare_regexp($regexp) {
		if (!isset($this->constants)) {
			$reflector = new ReflectionClass(get_class($this));
			$this->constants = $reflector->getConstants();
		}
		foreach ($this->constants as $name => $value) {
			$regexp = str_replace('{'.$name.'}', $value, $regexp);
		}
		return $regexp;
	}

	protected function _preg_match($regexp, $input, &$result, $debug=false) {
		$regexp = $this->_prepare_regexp($regexp);
		if ($debug) $this->debug('      preg_match <span class="osh-replacement">'.$regexp.'</span>');
		return preg_match($regexp, $input, $result);
	}

	protected function _preg_match_all($regexp, $input, &$result, $debug=false) {
		$regexp = $this->_prepare_regexp($regexp);
		if ($debug) $this->debug('      preg_match_all <span class="osh-replacement">'.$regexp.'</span>');
		$return = preg_match_all($regexp, $input, $result, PREG_SET_ORDER);
	}
	
	protected function _loadValue($process, $key) {
		$first_product = isset($process['cart.products'][0]) ? $process['cart.products'][0] : null;
		switch ($key) {
			case 'product.attribute_set.id': return $first_product ? $first_product->getAttributeSet()->getId() : null;
			case 'product.attribute_set': return $first_product ? $first_product->getAttributeSet()->getName() : null;
			case 'product.category.id': return $first_product && $first_product->getCategory() ? $first_product->getCategory()->getId() : null;
			case 'product.category': return $first_product && $first_product->getCategory() ? $first_product->getCategory()->getName() : null;
			case 'product.quantity': return $first_product ? $first_product->getQuantity() : null;
			case 'product.weight': return $first_product ? $first_product->getAttribute('weight') : null;
		}
		return isset($process['data'][$key]) ? $process['data'][$key] : null;
	}

	protected function _prepareFormula($process, $row, $property_key, $formula_string, $is_checking, $use_cache=true)
	{
		if ($use_cache && isset($this->_formula_cache[$formula_string])) {
			$result = $this->_formula_cache[$formula_string];
			$this->debug('      get cached formula <span class="osh-replacement">'.$formula_string.'</span> = <span class="osh-formula">'.self::toString($result->result).'</span>');
			return $result;
		}
	
		$formula = $formula_string;
		//$this->debug('      formula = <span class="osh-formula">'.$formula.'</span>');

		// foreach
		while ($this->_preg_match("#{foreach {PRODUCT_REGEX}\.((?:{ATTRIBUTE_REGEX}|{OPTION_REGEX})\.(?:[a-z0-9_]+)|{CATEGORIES_REGEX}|{ATTRIBUTE_SET_REGEX})}(.*){/foreach}#i", $formula, $result)) {
			$original = $result[0];
			if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
				$replacement = $this->_getCachedExpression($original);
			} else {
				$replacement = 0;
				if ($result[1]=='categories') {
					$selections = array();
					$this->debug('      <span class="osh-loop">prepare foreach <span class="osh-key">categories</span></span>');
					$this->addDebugIndent();
					foreach ($process['cart.products'] as $product) {
						$categories = $product->getCategories();
						$categories_names = array();
						foreach ($categories as $category) {
							$key = $category->toString();
							$categories_names[] = self::toString($key);
							$sel = isset($selections[$key]) ? $selections[$key] : null;
							$selections[$key]['products'][] = $product;
							$selections[$key]['weight'] = (isset($sel['weight']) ? $sel['weight'] : 0)+$product->getAttribute('weight')*$product->getQuantity();
							$selections[$key]['quantity'] = (isset($sel['quantity']) ? $sel['quantity'] : 0)+$product->getQuantity();
						}
						$this->debug('      products[<span class="osh-formula">'.$product->toString().'</span>].<span class="osh-key">categories</span> = array(<span class="osh-formula">'.join('</span>, <span class="osh-formula">', $categories_names).'</span>)');
					}
					$this->removeDebugIndent();
				} else if ($result[1]=='attribute_set') {
					$selections = array();
					$this->debug('      <span class="osh-loop">prepare foreach <span class="osh-key">attribute_set</span></span>');
					$this->addDebugIndent();
					foreach ($process['cart.products'] as $product) {
						$attribute_set = $product->getAttributeSet();
						$key = $attribute_set->toString();
						$sel = isset($selections[$key]) ? $selections[$key] : null;
						$selections[$key]['products'][] = $product;
						$selections[$key]['weight'] = (isset($sel['weight']) ? $sel['weight'] : 0)+$product->getAttribute('weight')*$product->getQuantity();
						$selections[$key]['quantity'] = (isset($sel['quantity']) ? $sel['quantity'] : 0)+$product->getQuantity();
						$this->debug('      products[<span class="osh-formula">'.$product->toString().'</span>].<span class="osh-key">attribute_set</span> = <span class="osh-formula">'.$attribute_set->toString().'</span>');
					}
					$this->removeDebugIndent();
				} else {
					list($filter_property_type, $filter_property_name) = explode('.', $result[1]);
					$selections = array();
					$this->debug('      foreach <span class="osh-key">'.$filter_property_type.'</span>.<span class="osh-key">'.$filter_property_name.'</span>');
					$this->addDebugIndent();
					foreach ($process['cart.products'] as $product) {
						$tmp_value = $this->_getProductProperty($product, $filter_property_type, $filter_property_name, $get_by_id=false);
						$this->debug('      products[<span class="osh-formula">'.$product->toString().'</span>].<span class="osh-key">'.$filter_property_type.'</span>.<span class="osh-key">'.$filter_property_name.'</span> = <span class="osh-formula">'.self::toString($tmp_value).'</span>');
						$key = 'val_'.$tmp_value;
						$sel = isset($selections[$key]) ? $selections[$key] : null;
						$selections[$key]['products'][] = $product;
						$selections[$key]['weight'] = (isset($sel['weight']) ? $sel['weight'] : 0)+$product->getAttribute('weight')*$product->getQuantity();
						$selections[$key]['quantity'] = (isset($sel['quantity']) ? $sel['quantity'] : 0)+$product->getQuantity();
					}
					$this->removeDebugIndent();
				}
				$this->debug('      <span class="osh-loop">start foreach</span>');
				$this->addDebugIndent();
				foreach ($selections as $key => $selection) {
					$this->debug('     <span class="osh-loop">&bull; value</span> = <span class="osh-formula">'.$key.'</span>');
					$this->addDebugIndent();
					$process2 = $process;
					$process2['cart.products'] = $selection['products'];
					$process2['data']['selection.quantity'] = $selection['quantity'];
					$process2['data']['selection.weight'] = $selection['weight'];
					$process_result = $this->_processFormula($process2, $row, $property_key, $result[2], $is_checking, $tmp_use_cache=false);
					$replacement += $process_result->result;
					$this->debug('    &raquo; <span class="osh-info">foreach sum result</span> = <span class="osh-formula">'.self::toString($replacement).'</span>');
					$this->removeDebugIndent();
				}
				$this->removeDebugIndent();
				$this->debug('      <span class="osh-loop">end</span>');
				if ($use_cache) $this->setCache($original, $replacement);
			}
			$formula = $this->replace($original, $replacement, $formula);
		}

		// customvar, variables
		while ($this->_preg_match("#{customvar\.([a-z0-9_]+)}#i", $formula, $result)) {
			$original = $result[0];
			$replacement = isset($process['store_interface']) ? $process['store_interface']->getCustomVar($result[1]) : null;
			$formula = $this->replace($original, $replacement, $formula);
		}

		$first_product = isset($process['cart.products'][0]) ? $process['cart.products'][0] : null;
		if (!isset($process['data']['selection.weight'])) $process['data']['selection.weight'] = $process['data']['cart.weight'];
		if (!isset($process['data']['selection.quantity'])) $process['data']['selection.quantity'] = $process['data']['cart.quantity'];
		
		// Values loaded only if needed
		$process['data']['product.weight'] = 0;
		$process['data']['product.quantity'] = 0;
		$process['data']['product.category'] = null;
		$process['data']['product.category.id'] = null;
		$process['data']['product.attribute_set'] = null;
		$process['data']['product.attribute_set.id'] = null;

		$aliases = array(
			'p.' => 'product.',
			'c.' => 'cart.',
			's.' => 'selection.',
		);
		foreach ($process['data'] as $original => $replacement) {
			if (mb_strpos($formula, '{'.$original.'}')!==false) {
				// Load value only if needed
				$replacement = $this->_loadValue($process, $original);
				
				// With strings auto-escape
				$formula = $this->replace('{{'.$original.'}}', $this->_autoEscapeStrings($replacement), $formula);
				// Without strings auto-escape
				$formula = $this->replace('{'.$original.'}', $replacement, $formula);
			}
			foreach ($aliases as $from => $to) {
				if (mb_substr($original, 0, mb_strlen($to))==$to) {
					$original2 = $from.mb_substr($original, mb_strlen($to), mb_strlen($original));
					if (mb_strpos($formula, '{'.$original2.'}')!==false) {
						// Load value only if needed
						$replacement = $this->_loadValue($process, $original);

						// With strings auto-escape
						$formula = $this->replace('{{'.$original2.'}}', $this->_autoEscapeStrings($replacement), $formula);
						// Without strings auto-escape
						$formula = $this->replace('{'.$original2.'}', $replacement, $formula);
					}
				}
			}
		}

		if (isset($first_product)) {
			// With strings auto-escape
			while ($this->_preg_match("#{{{PRODUCT_REGEX}\.({ATTRIBUTE_REGEX}|{OPTION_REGEX}|stock)\.([a-z0-9_+-]+)}}#i", $formula, $result)) {
				$original = $result[0];
				$replacement = $this->_getProductProperty($first_product, $result[1], $result[2], $get_by_id=false);
				$formula = $this->replace($original, $this->_autoEscapeStrings($replacement), $formula);
			}
			// Without strings auto-escape
			while ($this->_preg_match("#{{PRODUCT_REGEX}\.({ATTRIBUTE_REGEX}|{OPTION_REGEX}|stock)\.([a-z0-9_+-]+)}#i", $formula, $result)) {
				$original = $result[0];
				$replacement = $this->_getProductProperty($first_product, $result[1], $result[2], $get_by_id=false);
				$formula = $this->replace($original, $replacement, $formula);
			}
		}

		// count, sum, min, max
		while ($this->_preg_match("/{(count) products(?: where ([^}]+))?}/i", $formula, $result)
			|| $this->_preg_match("/{(sum|min|max|count distinct) {PRODUCT_REGEX}\.({ATTRIBUTE_REGEX}|{OPTION_REGEX}|stock)\.([a-z0-9_+-]+)(?: where ([^}]+))?}/i", $formula, $result)
			|| $this->_preg_match("/{(sum|min|max|count distinct) {PRODUCT_REGEX}\.(quantity)()(?: where ([^}]+))?}/i", $formula, $result)
				) {
			$original = $result[0];
			if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
				$replacement = $this->_getCachedExpression($original);
			} else {
				$replacement = $this->_processProductProperty($process['cart.products'], $result);
				if ($use_cache) $this->setCache($result[0], $replacement);
			}
			$formula = $this->replace($original, $replacement, $formula);
		}
		
		// switch
		while (preg_match("/{switch ([^}]+) in ([^}]+)}/i", $formula, $result)) {
			$original = $result[0];
			if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
				$replacement = $this->_getCachedExpression($original);
			} else {
				$reference_value = $this->_evalFormula($result[1]);
				$fees_table_string = $result[2];
				
				$couple_regex = '[^}:]+ *\: *[0-9.]+ *';
				if (!preg_match('#^ *'.$couple_regex.'(?:, *'.$couple_regex.')*$#', $fees_table_string)) {
					$this->addMessage('error', $row, $property_key, 'Error in switch %s', '<span class="osh-formula">'.htmlentities($result[0]).'</span>');
					$result = new OS_Result(false);
					if ($use_cache) $this->setCache($formula_string, $result);
					return $result;
				}
				$fees_table = explode(',', $fees_table_string);
				
				$replacement = null;
				foreach ($fees_table as $item) {
					$fee_data = explode(':', $item);

					$fee = trim($fee_data[1]);
					$value = trim($fee_data[0]);
					$value = $value=='*' ? '*' : $this->_evalFormula($fee_data[0]);

					if ($value=='*' || $reference_value===$value) {
						$replacement = $fee;
						$this->debug('      compare <span class="osh-formula">'.self::toString($reference_value).'</span> and <span class="osh-formula">'.self::toString($value).'</span> = equals');
						break;
					}
					$this->debug('      compare <span class="osh-formula">'.self::toString($reference_value).'</span> and <span class="osh-formula">'.self::toString($value).'</span> = not equals');
				}
				//$replacement = self::toString($replacement);
				if ($use_cache) $this->setCache($original, $replacement);
			}
			$formula = $this->replace($original, $replacement, $formula);
		}

		// range table
		while (preg_match("/{table ([^}]+) in ([0-9\.:,\*\[\] ]+)}/i", $formula, $result)) {
			$original = $result[0];
			if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
				$replacement = $this->_getCachedExpression($original);
			} else {
				$reference_value = $this->_evalFormula($result[1]);
				$replacement = null;
				if (isset($reference_value)) {
					$fees_table_string = $result[2];
					
					if (!preg_match('#^'.self::COUPLE_REGEX.'(?:, *'.self::COUPLE_REGEX.')*$#', $fees_table_string)) {
						$this->addMessage('error', $row, $property_key, 'Error in table %s', '<span class="osh-formula">'.htmlentities($result[0]).'</span>');
						$result = new OS_Result(false);
						if ($use_cache) $this->setCache($formula_string, $result);
						return $result;
					}
					$fees_table = explode(',', $fees_table_string);
					foreach ($fees_table as $item) {
						$fee_data = explode(':', $item);

						$fee = trim($fee_data[1]);
						$max_value = trim($fee_data[0]);

						$last_char = $max_value{strlen($max_value)-1};
						if ($last_char=='[') $including_max_value = false;
						else if ($last_char==']') $including_max_value = true;
						else $including_max_value = true;

						$max_value = str_replace(array('[', ']'), '', $max_value);

						if ($max_value=='*' || $including_max_value && $reference_value<=$max_value || !$including_max_value && $reference_value<$max_value) {
							$replacement = $fee;
							break;
						}
					}
				}
				//$replacement = self::toString($replacement);
				if ($use_cache) $this->setCache($original, $replacement);
			}
			$formula = $this->replace($original, $replacement, $formula);
		}
		$result = new OS_Result(true, $formula);
		return $result;
	}

	protected function _evalFormula($formula) {
		if (is_bool($formula)) return $formula;
		if (!preg_match('/^(?:E|floor|ceil|round|max|min|rand|pow|pi|sqrt|log|exp|abs|int|float|true|false|null|and|or|in|substr|strtolower'
				.'|in_array\((?:\'(?:[^\']*)\'|(?:[^\']*)), *array\( *(?:(?:\'(?:[^\']+)\'|(?:[^\']+)) *(?: *, *(?:\'(?:[^\']+)\'|(?:[^\']+)))* *)?\) *\)'
				.'|\'[^\']*\'|[0-9,\'\.\-\(\)\*\/\?\:\+\<\>\=\&\|%! ])*$/', $formula)) {
			$errors = array(
				PREG_NO_ERROR => 'PREG_NO_ERROR',
				PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR',
				PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR',
				PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR',
				PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR',
				defined('PREG_BAD_UTF8_OFFSET_ERROR') ? PREG_BAD_UTF8_OFFSET_ERROR : 'PREG_BAD_UTF8_OFFSET_ERROR' => 'PREG_BAD_UTF8_OFFSET_ERROR',
			);
			$error = preg_last_error();
			if (isset($errors[$error])) $error = $errors[$error];
			$this->debug('      eval <span class="osh-formula">'.$formula.'</span>');
			$this->debug('      doesn\'t match ('.$error.')');
			return null;
		}
		$formula = str_replace(
			array('min', 'max'),
			array('$this->_min', '$this->_max'),
			$formula
		);
		$eval_result = null;
		@eval('$eval_result = ('.$formula.');');
		$this->debug('      evaluate <span class="osh-formula">'.$formula.'</span> = <span class="osh-replacement">'.self::toString($eval_result).'</span>');
		return $eval_result;
	}

	protected function _getOptionsAndData($string) {
		if (preg_match('/^(\\s*\(\\s*([^\] ]*)\\s*\)\\s*)/', $string, $result)) {
			$options = $result[2];
			$data = str_replace($result[1], '', $string);
		} else {
			$options = '';
			$data = $string;
		}
		return array(
			'options' => $options,
			'data' => $data,
		);
	}

	public function compress($input) {
		/*
		if (preg_match_all("/{table (.*) in (".self::COUPLE_REGEX."(?:, *".self::COUPLE_REGEX.")*)}/imsU", $input, $result, PREG_SET_ORDER)) {
			foreach ($result as $result_i) {
				$fees_table = explode(',', $result_i[2]);
				$value = null;
				foreach ($fees_table as $index => $item) {
					list($max_value, $fee) = explode(':', $item);
					$last_char = $max_value{strlen($max_value)-1};
					if (in_array($last_char, array('[', ']'))) {
						$including_char = $last_char;
						$max_value = str_replace(array('[', ']'), '', $max_value);
					} else $including_char = '';
					$fees_table[$index] = ((float)$max_value).$including_char.':'.((float)$fee);
				}
				$input = str_replace($result_i[2], implode(',', $fees_table), $input);
				$input = str_replace($result_i[1], trim($result_i[1]), $input);
			}
		}
		if (preg_match_all("#{foreach ([^}]*)}(.*){/foreach}#imsU", $input, $result, PREG_SET_ORDER)) {
			foreach ($result as $result_i) {
				$input = str_replace($result_i[1], trim($result_i[1]), $input);
				$input = str_replace($result_i[2], trim($result_i[2]), $input);
			}
		}
		*/
		$input = str_replace(
			self::$UNCOMPRESSED_STRINGS,
			self::$COMPRESSED_STRINGS,
			$input
		);

		if (function_exists('gzcompress') && function_exists('base64_encode')) {
			$input = 'gz64'.base64_encode(gzcompress($input));
		}
		return '$$'.$input;
	}
	
	public function uncompress($input) {
		if (substr($input, 0, 4)=='gz64' && function_exists('gzuncompress') && function_exists('base64_decode')) {
			$input = gzuncompress(base64_decode(substr($input, 4, strlen($input))));
		}

		/*
		if (preg_match_all("/{table (.*) in (".self::COUPLE_REGEX."(?:, *".self::COUPLE_REGEX.")*)}/iU", $input, $result, PREG_SET_ORDER)) {
			foreach ($result as $result_i) {
				$fees_table = explode(',', $result_i[2]);
				$value = null;
				foreach ($fees_table as $index => $item) {
					list($max_value, $fee) = explode(':', $item);
					$last_char = $max_value{strlen($max_value)-1};
					if (in_array($last_char, array('[', ']'))) {
						$including_char = $last_char;
						$max_value = str_replace(array('[', ']'), '', $max_value);
					} else $including_char = '';
					$max_value = (float)$max_value;
					$fee = (float)$fee;
					$new_max_value = number_format($max_value, 2, '.', '');
					$new_fee = number_format($fee, 2, '.', '');
					$fees_table[$index] = (((float)$new_max_value)==$max_value ? $new_max_value : $max_value).$including_char.':'
						.(((float)$new_fee)==$fee ? $new_fee : $fee);
				}
				$input = str_replace($result_i[2], implode(', ', $fees_table), $input);
				$input = str_replace($result_i[1], trim($result_i[1]), $input);
			}
		}
		if (preg_match_all("#{foreach ([^}]*)}(.*){/foreach}#iU", $input, $result, PREG_SET_ORDER)) {
			foreach ($result as $result_i) {
				$input = str_replace($result_i[1], trim($result_i[1]), $input);
				$input = str_replace($result_i[2], trim($result_i[2]), $input);
			}
		}
		*/
		return str_replace(
			self::$COMPRESSED_STRINGS,
			self::$UNCOMPRESSED_STRINGS,
			$input
		);
	}

	public function parseProperty($input) {
		$value = $input==='false' || $input==='true' ? $input=='true' : str_replace('\"', '"', preg_replace('/^(?:"|\')(.*)(?:"|\')$/s', '$1', $input));
		return $value==='' ? null : $value;
	}

	public function cleanProperty(&$row, $key) {
		$input = $row[$key]['value'];
		if (is_string($input)) {
			$input = str_replace(array("\n"), array(''), $input);
			while (preg_match('/({TABLE |{SUM |{COUNT | DISTINCT | IN )/', $input, $resi)) {
				$input = str_replace($resi[0], strtolower($resi[0]), $input);
			}

			while (preg_match('/{{customVar code=([a-zA-Z0-9_-]+)}}/', $input, $resi)) {
				$input = str_replace($resi[0], '{customvar.'.$resi[1].'}', $input);
			}

			$regex = "{(weight|products_quantity|price_including_tax|price_excluding_tax|country)}";
			if (preg_match('/'.$regex.'/', $input, $resi)) {
				$this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class="osh-formula">'.$resi[0].'</span>');
				while (preg_match('/'.$regex.'/', $input, $resi)) {
					switch ($resi[1]) {
						case 'price_including_tax':
						case 'price_excluding_tax':
						case 'weight':
							$input = str_replace($resi[0], "{cart.".$resi[1]."}", $input);
							break;
						case 'products_quantity': $input = str_replace($resi[0], "{cart.quantity}", $input); break;
						case 'country': $input = str_replace($resi[0], "{destination.country.name}", $input); break;
					}
				}
			}

			$regex1 = "{copy '([a-zA-Z0-9_]+)'\.'([a-zA-Z0-9_]+)'}";
			if (preg_match('/'.$regex1.'/', $input, $resi)) {
				$this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class="osh-formula">'.$resi[0].'</span>');
				while (preg_match('/'.$regex1.'/', $input, $resi)) $input = str_replace($resi[0], '{'.$resi[1].'.'.$resi[2].'}', $input);
			}

			$regex1 = "{(count|all|any) (attribute|option) '([^'\)]+)' ?((?:==|<=|>=|<|>|!=) ?(?:".self::FLOAT_REGEX."|true|false|'[^'\)]*'))}";
			$regex2 = "{(sum) (attribute|option) '([^'\)]+)'}";
			if (preg_match('/'.$regex1.'/', $input, $resi) || preg_match('/'.$regex2.'/', $input, $resi)) {
				$this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class="osh-formula">'.$resi[0].'</span>');
				while (preg_match('/'.$regex1.'/', $input, $resi) || preg_match('/'.$regex2.'/', $input, $resi)) {
					switch ($resi[1]) {
						case 'count':	$input = str_replace($resi[0], "{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}", $input); break;
						case 'all':		$input = str_replace($resi[0], "{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}=={products_quantity}", $input); break;
						case 'any':		$input = str_replace($resi[0], "{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}>0", $input); break;
						case 'sum':		$input = str_replace($resi[0], "{sum product.".$resi[2].".".$resi[3]."}", $input); break;
					}
				}
			}

			$regex = "((?:{| )product.(?:attribute|option))s.";
			if (preg_match('/'.$regex.'/', $input, $resi)) {
				$this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class="osh-formula">'.$resi[0].'</span>');
				while (preg_match('/'.$regex.'/', $input, $resi)) {
					$input = str_replace($resi[0], $resi[1].'.', $input);
				}
			}

			$regex = "{table '([^']+)' (".self::COUPLE_REGEX."(?:, *".self::COUPLE_REGEX.")*)}";
			if (preg_match('/'.$regex.'/', $input, $resi)) {
				$this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class="osh-formula">'.$resi[0].'</span>');
				while (preg_match('/'.$regex.'/', $input, $resi)) {
					switch ($resi[1]) {
						case 'products_quantity':
							$input = str_replace($resi[0], "{table {cart.weight} in ".$resi[2]."}*{cart.quantity}", $input);
							break;
						default:
							$input = str_replace($resi[0], "{table {cart.".$resi[1]."} in ".$resi[2]."}", $input);
							break;
					}
				}
			}
		}
		$row[$key]['value'] = $input;
	}

	protected function _parseInput() {
		$config_string = str_replace(
			array('&gt;', '&lt;', '“', '”', utf8_encode(chr(147)), utf8_encode(chr(148)), '&laquo;', '&raquo;', "\r\n", "\t"),
			array('>', '<', '"', '"', '"', '"', '"', '"', "\n", ' '),
			$this->_input
		);
		
		if (substr($config_string, 0, 2)=='$$') $config_string = $this->uncompress(substr($config_string, 2, strlen($config_string)));
		
		//echo ini_get('pcre.backtrack_limit');
		//exit;

		$row_regex = ' *([a-z0-9_]+)\\s*:\\s*("(?:(?:[^"]|\\\\")*[^\\\\])?"|'.self::FLOAT_REGEX.'|false|true)\\s*(,)? *(?:\\n)?';
		if (!preg_match_all('/((?:#+[^{\\n]*\\s+)*)\\s*(#)?{\\s*('.$row_regex.')+\\s*}/i', $config_string, $result, PREG_SET_ORDER)) {
			$errors = array(
				PREG_NO_ERROR => 'PREG_NO_ERROR',
				PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR',
				PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR',
				PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR',
				PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR',
				defined('PREG_BAD_UTF8_OFFSET_ERROR') ? PREG_BAD_UTF8_OFFSET_ERROR : 'PREG_BAD_UTF8_OFFSET_ERROR' => 'PREG_BAD_UTF8_OFFSET_ERROR',
			);
			$error = preg_last_error();
			if (isset($errors[$error])) $error = $errors[$error];
			$this->debug('      preg_match_all error ('.$error.')');
		}

		$this->_config = array();
		$available_keys = array(
			'code', 'label', 'enabled', 'description', 'fees', 'conditions', 'destination', 'origin', 'customer_groups', 'tracking_url',
			'fees_table', 'fees_formula', 'fixed_fees', 'reference_value',
			'prices_range', 'weights_range', 'product_properties',
			'free_shipping__fees_table', 'free_shipping__fees_formula', 'free_shipping__fixed_fees', 'free_shipping__label',
		);
		
		foreach ($result as $block) {
			$deprecated_properties = array();
			$unknown_properties = array();
			$missing_semicolon = array();
			$obsolete_disabling_method = array();

			//$before = strstr($config_string, $block[0], true); // Seulement compatible avec PHP 5.3.0
			list($before) = explode($block[0], $config_string, 2);
			if ($before!==false && trim($before)!='') {
				$config_string = substr($config_string, strlen($before));
				$this->_addIgnoredLines(trim($before));
				$row = null;
				$this->addMessage('info', $row, null, 'Ignored lines %s', '<div class="code">'.trim($before).'</div>');
			}

			$config_string = str_replace($block[0], '', $config_string);
			preg_match_all('/'.$row_regex.'/i', $block[0], $result2, PREG_SET_ORDER);
			$block_string = $block[0];

			$row = array();
			$i = 1;
			foreach ($result2 as $data) {
				$key = $data[1];
				if (in_array($key, $available_keys) || substr($key, 0, 1)=='_') {
					$property = $this->parseProperty($data[2]);
					if (isset($property)) {
						$row[$key] = array('value' => $property, 'original_value' => $property);
						$this->cleanProperty($row, $key);
					}
					if ($i>2) {
						$block_string = str_replace($data[0], $i==3 ? "...\n" : '', $block_string);
					}
					if ($i!=count($result2) && !isset($data[3]) || isset($data[3]) && $data[3]!=',') {
						if (preg_match('/^("|\')(.{40})(.*)("|\')$/s', $data[2], $resultx))
							$missing_semicolon[] = trim(str_replace($data[2], $resultx[1].$resultx[2].' ...'.$resultx[4], $data[0]));
						else $missing_semicolon[] = trim($data[0]);
					}
				} else {
					if (!in_array($key, $unknown_properties)) $unknown_properties[] = $key;
				}
				$i++;
			}
			if ($block[1]!='') $row['*comment']['value'] = $block[1];
			if ($block[2]=='#' && !isset($row['enabled'])) {
				$row['enabled'] = array('value' => false);
				$obsolete_disabling_method[] = $block_string;
			}

			$formula_fields_to_check = array();
			if (isset($row['conditions'])) $formula_fields_to_check[] = 'conditions';
			if (isset($row['fees'])) $formula_fields_to_check[] = 'fees';
			
			if (count($formula_fields_to_check)>0) {
				foreach ($formula_fields_to_check as $property) {
					$property_value = $row[$property]['value'];
					if (preg_match('/{ +/', $property_value)) {
						$this->addMessage('warning', $row, $property, 'There are unwanted spaces after char `%s`', '{');
						$property_value = preg_replace('/{ +/', '{', $property_value);
					}
					if (preg_match('/ +}/', $property_value)) {
						$this->addMessage('warning', $row, $property, 'There are unwanted spaces before char `%s`', '}');
						$property_value = preg_replace('/ +}/', '}', $property_value);
					}
					if (preg_match('/  +/', $property_value)) {
						$this->addMessage('warning', $row, $property, 'There are unwanted multiples spaces `%s`', preg_replace('/(  +)/', '<span class="osh-formula">*$1*</span>', $property_value));
						$property_value = preg_replace('/  +/', ' ', $property_value);
					}
					$row[$property]['value'] = trim($property_value);
				}
			}

			$float_value_regex = '\\s*('.self::POSITIVE_FLOAT_REGEX.'|\*)\\s*';
			$conditions = array();
			if (isset($row['prices_range'])) {
				if (!in_array('prices_range', $deprecated_properties)) $deprecated_properties[] = 'prices_range';

				$result = $this->_getOptionsAndData($row['prices_range']['value']);
				$options = $result['options'];
				$prices_range = $result['data'];

				if (($options=='' || in_array($options, array('incl.tax', 'ttc')))
					&& preg_match('/^\\s*(\[|\])?'.$float_value_regex.'=>'.$float_value_regex.'(\[|\])?\\s*$/', $prices_range, $result)) {
					$min_price_included = $result[1]=='[';
					$min_price = $result[2]=='*' ? -1 : (float)$result[2];
					$max_price = $result[3]=='*' ? -1 : (float)$result[3];
					$max_price_included = !isset($result[4]) || $result[4]==']' || $result[4]=='';

					$tax_included = $options!='' && in_array($options, array('incl.tax', 'ttc')) || isset($row['reference_value']) && $row['reference_value']['value']=='price_including_tax';
					$price = $tax_included ? '{cart.price_including_tax}' : '{cart.price_excluding_tax}';

					if ($min_price!=-1) $conditions[] = $price.'>'.($min_price_included ? '=' : '').$min_price;
					if ($max_price!=-1) $conditions[] = $price.'<'.($max_price_included ? '=' : '').$max_price;
				}
				else $this->addMessage('error', $row, null, 'Unrecognized value of deprecated property %s %s', '<span class="osh-key">prices_range</span>', '<span class="osh-formula">'.$row['prices_range']['value'].'</span>');
				unset($row['prices_range']);
			}
			if (isset($row['weights_range'])) {
				if (!in_array('weights_range', $deprecated_properties)) $deprecated_properties[] = 'weights_range';
				if (preg_match('/^\\s*(\[|\])?'.$float_value_regex.'=>'.$float_value_regex.'(\[|\])?\\s*$/', $row['weights_range']['value'], $result)) {
					$min_weight_included = $result[1]=='[';
					$min_weight = $result[2]=='*' ? -1 : (float)$result[2];
					$max_weight = $result[3]=='*' ? -1 : (float)$result[3];
					$max_weight_included = !isset($result[4]) || $result[4]==']' || $result[4]=='';

					if ($min_weight!=-1) $conditions[] = '{cart.weight}>'.($min_weight_included ? '=' : '').$min_weight;
					if ($max_weight!=-1) $conditions[] = '{cart.weight}<'.($max_weight_included ? '=' : '').$max_weight;
				}
				else $this->addMessage('error', $row, null, 'Unrecognized value of deprecated property %s %s', '<span class="osh-key">weights_range</span>', '<span class="osh-formula">'.$row['weights_range']['value'].'</span>');
				unset($row['weights_range']);
			}
			if (isset($row['product_properties'])) {
				if (!in_array('product_properties', $deprecated_properties)) $deprecated_properties[] = 'product_properties';
				$product_property_regex = "\\s*(and|or)? *\((?:(all|any|sum) )?(attribute|option) '([^'\)]+)' ?(==|=|<=|>=|<|>|!=) ?(".self::FLOAT_REGEX."|true|false|'[^'\)]*')\)\\s*";
				if (preg_match('/^('.$product_property_regex.')+$/', $row['product_properties']['value'], $result)) {
					preg_match_all('/'.$product_property_regex.'/', $row['product_properties']['value'], $results, PREG_SET_ORDER);
					$product_properties_condition = '';
					foreach ($results as $result) {
						$and_or = $result[1];
						if ($and_or=='') $and_or = 'and';
						$any_all_sum = $result[2];
						if ($any_all_sum=='') $any_all_sum = 'any';
						$property_type = $result[3];
						$property_name = $result[4];
						$cmp_symbol = $result[5];
						if ($cmp_symbol=='=') $cmp_symbol = '==';
						$cmp_value = $result[6];

						$product_properties_condition .= $product_properties_condition=='' ? '' : ' '.$and_or.' ';
						switch ($any_all_sum) {
							case 'sum':
								$product_properties_condition .= "{sum product.".$property_type.".".$property_name."}".$cmp_symbol.$cmp_value;
								break;
							case 'all':
								$product_properties_condition .= "{count products where product.".$property_type.".".$property_name.$cmp_symbol.$cmp_value."}=={products_quantity}";
								break;
							case 'any':
								$product_properties_condition .= "{count products where product.".$property_type.".".$property_name.$cmp_symbol.$cmp_value."}>0";
								break;
						}
					}
					if ($product_properties_condition!='') $conditions[] = $product_properties_condition;
				}
				else $this->addMessage('error', $row, null, 'Unrecognized value of deprecated property %s %s', '<span class="osh-key">product_properties</span>', '<span class="osh-formula">'.$row['product_properties']['value'].'</span>');
				unset($row['product_properties']);
			}
			if (count($conditions)>0) $row['conditions'] = array('value' => count($conditions)==1 ? $conditions[0] : '('.implode(') && (', $conditions).')');

			$fees = array();
			if (isset($row['fees_table'])) {
				if (!in_array('fees_table', $deprecated_properties)) $deprecated_properties[] = 'fees_table';
				$options_and_data = $this->_getOptionsAndData($row['fees_table']['value']);
				$options = $options_and_data['options'];
				$fees_table_string = $options_and_data['data'];
				
				$var = null;
				if ($options=='') $var = (isset($row['reference_value']) ? $row['reference_value']['value'] : 'weight');
				else if (in_array($options, array('incl.tax', 'ttc'))) $var = 'price_including_tax';
				else if (in_array($options, array('excl.tax', 'ht'))) $var = 'price_excluding_tax';
				
				if (isset($var)) {
					if ($var=='price') $var = 'price_excluding_tax';
					if ($var=='products_quantity') $var = 'quantity';
					if (preg_match('/^[[:space:]]*\*[[:space:]]*:[[:space:]]*('.$float_value_regex.')[[:space:]]*$/s', $fees_table_string, $result)) $fees[] = $result[1];
					else $fees[] = "{table {cart.".$var."} in ".str_replace(' ', '', $fees_table_string)."}".($var=='quantity' ? '*{cart.quantity}' : '');
				}
				else $this->addMessage('error', $row, null, 'Unrecognized value of deprecated property %s %s', '<span class="osh-key">fees_table</span>', '<span class="osh-formula">'.$row['fees_table']['value'].'</span>');
				unset($row['fees_table']);
			}
			if (isset($row['fees_formula'])) {
				if (!in_array('fees_formula', $deprecated_properties)) $deprecated_properties[] = 'fees_formula';
				$fees[] = str_replace(' ', '', $row['fees_formula']['value']);
				unset($row['fees_formula']);
			}
			if (isset($row['fixed_fees'])) {
				if (!in_array('fixed_fees', $deprecated_properties)) $deprecated_properties[] = 'fixed_fees';
				if ($row['fixed_fees']['value']!=0 || count($fees)==0) $fees[] = str_replace(' ', '', $row['fixed_fees']['value']);
				unset($row['fixed_fees']);
			}
			if (!isset($row['fees']) && count($fees)>0) $row['fees'] = array('value' => implode('+', $fees));

			$fs_fees = array();
			if (isset($row['free_shipping__fees_table'])) {
				if (!in_array('free_shipping__fees_table', $deprecated_properties)) $deprecated_properties[] = 'free_shipping__fees_table';
				$options_and_data = $this->_getOptionsAndData($row['free_shipping__fees_table']['value']);
				$options = $options_and_data['options'];
				$fees_table_string = $options_and_data['data'];
				
				$var = null;
				if ($options=='') $var = isset($row['reference_value']) ? $row['reference_value']['value'] : 'weight';
				else if (in_array($options, array('incl.tax', 'ttc'))) $var = 'price_including_tax';
				else if (in_array($options, array('excl.tax', 'ht'))) $var = 'price_excluding_tax';
				if ($var=='price') $var = 'price_excluding_tax';

				if (isset($var)) {
					if ($var=='price') $var = 'price_excluding_tax';
					if ($var=='products_quantity') $var = 'quantity';
					if (preg_match('/^[[:space:]]*\*[[:space:]]*:[[:space:]]*('.$float_value_regex.')[[:space:]]*$/s', $fees_table_string, $result)) $fs_fees[] = $result[1];
					else $fs_fees[] = "{table {cart.".$var."} in ".str_replace(' ', '', $fees_table_string)."}".($var=='quantity' ? '*{cart.quantity}' : '');
				}
				else $this->addMessage('error', $row, null, 'Unrecognized value of deprecated property %s %s', '<span class="osh-key">free_shipping__fees_table</span>', '<span class="osh-formula">'.$row['free_shipping__fees_table']['value'].'</span>');
				unset($row['free_shipping__fees_table']);
			}
			if (isset($row['free_shipping__fees_formula'])) {
				if (!in_array('free_shipping__fees_formula', $deprecated_properties)) $deprecated_properties[] = 'free_shipping__fees_formula';
				$fs_fees[] = str_replace(' ', '', $row['free_shipping__fees_formula']['value']);
				unset($row['free_shipping__fees_formula']);
			}
			if (isset($row['free_shipping__fixed_fees'])) {
				if (!in_array('free_shipping__fixed_fees', $deprecated_properties)) $deprecated_properties[] = 'free_shipping__fixed_fees';
				if ($row['free_shipping__fixed_fees']['value']!=0 || count($fees)==0) $fs_fees[] = str_replace(' ', '', $row['free_shipping__fixed_fees']['value']);
				unset($row['free_shipping__fixed_fees']);
			}

			if (isset($row['reference_value'])) {
				if (!in_array('reference_value', $deprecated_properties)) $deprecated_properties[] = 'reference_value';
				unset($row['reference_value']);
			}

			if (count($fs_fees)>0) {
				$row2 = $row;
				if (isset($row['code'])) $row2['code']['value'] = $row['code']['value'].'__free_shipping';
				$row2['fees']['value'] = implode('+', $fs_fees);
				$row2['conditions']['value'] = isset($row2['conditions']) ? '('.$row2['conditions']+') and {free_shipping}' : '{free_shipping}';
				$row['conditions']['value'] = isset($row['conditions']) ? '('.$row['conditions']+') and !{free_shipping}' : '!{free_shipping}';
				if (isset($row['free_shipping__label'])) {
					if (!in_array('free_shipping__label', $deprecated_properties)) $deprecated_properties[] = 'free_shipping__label';
					$row2['label']['value'] = $row['free_shipping__label']['value'];
					unset($row['free_shipping__label']);
					unset($row2['free_shipping__label']);
				}
				$this->_addRow($row2);
			}
			if (count($unknown_properties)>0) $this->addMessage('error', $row, null, 'Usage of unknown properties %s', ': <span class="osh-key">'.implode('</span>, <span class="osh-key">', $unknown_properties).'</span>');
			if (count($deprecated_properties)>0) $this->addMessage('warning', $row, null, 'Usage of deprecated properties %s', ': <span class="osh-key">'.implode('</span>, <span class="osh-key">', $deprecated_properties).'</span>');
			if (count($obsolete_disabling_method)>0) $this->addMessage('warning', $row, null, 'Usage of obsolete method to disabling a shipping method (`#` before `{`)%s', '<div class="code">'.implode('<br />', $obsolete_disabling_method).'</div>');
			if (count($missing_semicolon)>0) $this->addMessage('warning', $row, null, 'A semicolon is missing at the end of following lines %s', '<div class="code">'.implode('<br />', $missing_semicolon).'</div>');
			$this->_addRow($row);
		}
		if (trim($config_string)!='') {
			$this->_addIgnoredLines(trim($config_string));
			$row = null;
			$this->addMessage('info', $row, null, 'Ignored lines %s', '<div class="code">'.trim($config_string).'</div>');
		}
	}
	
	public function addMessage($type, &$row, $property) {
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		array_shift($args);
		$message = new OS_Message($type, $args);
		if (isset($row)) {
			if (isset($property)) {
				$row[$property]['messages'][] = $message;
			} else {
				$row['*messages'][] = $message;
			}
		}
		$this->_messages[] = $message;
		$this->debug('   => <span class="osh-'.$message->type.'">'.$message->toString().'</span>');
	}

	protected function _addRow(&$row) {
		if (isset($row['code'])) {
			$key = $row['code']['value'];
			if (isset($this->_config[$key])) $this->addMessage('error', $row, 'code', 'The property `code` must be unique, `%s` has been found twice', $key);
			while (isset($this->_config[$key])) $key .= rand(0, 9);
		} else {
			$i = 1;
			do {
				$key = 'code_auto'.sprintf('%03d', $i);
				$i++;
			} while (isset($this->_config[$key]));
		}
		$row['*code'] = $key;
		$this->_config[$key] = $row;
	}

	protected function _addIgnoredLines($lines) {
		$this->_config[] = array('lines' => $lines);
	}

	protected function _addressMatch($address_filter, $address) {
		$excluding = false;
		
		$address_filter = trim($address_filter);
		$address_filter = str_replace(
			array('\(', '\)', '\,'),
			array('__opening_parenthesis__', '__closing_parenthesis__', '__comma__'),
			$address_filter
		);

		if ($address_filter=='*') {
			$this->debug('      country code <span class="osh-replacement">'.$address['country_code'].'</span> matches');
			return true;
		}

		if (preg_match('#\* *- *\((.*)\)#s', $address_filter, $result)) {
			$address_filter = $result[1];
			$excluding = true;
		}

		$tmp_address_filter_array = explode(',', trim($address_filter));
		
		$concat = false;
		$concatened = '';
		$address_filter_array = array();
		$i = 0;
		
		foreach ($tmp_address_filter_array as $address_filter) {
			if ($concat) $concatened .= ','.$address_filter;
			else {
				if ($i<count($tmp_address_filter_array)-1 && preg_match('#\(#', $address_filter)) {
					$concat = true;
					$concatened .= $address_filter;
				} else $address_filter_array[] = $address_filter;
			}
			if (preg_match('#\)#', $address_filter)) {
				$address_filter_array[] = $concatened;
				$concatened = '';
				$concat = false;
			}
			$i++;
		}
		
		foreach ($address_filter_array as $address_filter) {
			$address_filter = trim($address_filter);
			if (preg_match('#([A-Z]{2}) *(-)? *(?:\( *(-)? *(.*)\))?#s', $address_filter, $result)) {
				$country_code = $result[1];
				if ($address['country_code']==$country_code) {
					$this->debug('      country code <span class="osh-replacement">'.$address['country_code'].'</span> matches');
					if (!isset($result[4]) || $result[4]=='') return !$excluding;
					else {
						$region_codes = explode(',', $result[4]);
						$in_array = false;
						for ($i=count($region_codes); --$i>=0;) {
							$code = trim(str_replace(
								array('__opening_parenthesis__', '__closing_parenthesis__', '__comma__'),
								array('(', ')', ','),
								$region_codes[$i]
							));
							$region_codes[$i] = $code;
							if ($address['region_code']===$code) {
								$this->debug('      region code <span class="osh-replacement">'.$address['region_code'].'</span> matches');
								$in_array = true;
							} else if ($address['postcode']===$code) {
								$this->debug('      postcode <span class="osh-replacement">'.$address['postcode'].'</span> matches');
								$in_array = true;
							} else if (mb_substr($code, 0, 1)=='/' && mb_substr($code, mb_strlen($code)-1, 1)=='/' && @preg_match($code, $address['postcode'])) {
								$this->debug('      postcode <span class="osh-replacement">'.$address['postcode'].'</span> matches <span class="osh-formula">'.htmlentities($code).'</span>');
								$in_array = true;
							} else if (mb_strpos($code, '*')!==false && preg_match('/^'.str_replace('*', '(?:.*)', $code).'$/', $address['postcode'])) {
								$this->debug('      postcode <span class="osh-replacement">'.$address['postcode'].'</span> matches <span class="osh-formula">'.htmlentities($code).'</span>');
								$in_array = true;
							}
							if ($in_array) break;
						}
						if (!$in_array) {
							$this->debug('      region code <span class="osh-replacement">'.$address['region_code'].'</span> and postcode <span class="osh-replacement">'.$address['postcode'].'</span> don\'t match');
						}
						// Vérification stricte
						$excluding_region = $result[2]=='-' || $result[3]=='-';
						if ($excluding_region && !$in_array || !$excluding_region && $in_array) return !$excluding;
					}
				}
			}
		}
		return $excluding;
	}

	protected function _getProductProperty($product, $property_type, $property_name, $get_by_id=false) {
		//$this->debug('      :: property_type <span class="osh-replacement">'.$property_type.'</span>');
		switch ($property_type) {
			case 'a':
			case 'attr':
			case 'attributes': 
			case 'attribute': return $product->getAttribute($property_name, $get_by_id);
			case 'o':
			case 'opt':
			case 'options':
			case 'option': return $product->getOption($property_name, $get_by_id);
			case 'stock': return $product->getStockData($property_name);
		}
		return null;
	}

	protected function _autoEscapeStrings($input) {
		if (is_array($input)) {
			$items = array();
			foreach ($input as $v) {
				$items[] = isset($v) && (is_string($v) || empty($v)) ? "'".$input."'" : self::toString($v);
			}
			return 'array('.join(',', $items).')';
		} else {
			return isset($input) && (is_string($input) || empty($input)) ? "'".$input."'" : self::toString($input);
		}
	}

	protected function _processProductProperty($products, $regex_result) {
		// count, sum, min, max, count distinct
		$operation = strtolower($regex_result[1]);
		$return_value = null;
		switch ($operation) {
			case 'sum':
			case 'min':
			case 'max':
			case 'count distinct':
				$property_type = $regex_result[2];
				$property_name = $regex_result[3];
				$conditions = isset($regex_result[4]) ? $regex_result[4] : null;
				break;
			case 'count':
				$conditions = isset($regex_result[2]) ? $regex_result[2] : null;
				break;
		}
		switch ($operation) {
			case 'sum':
			case 'count distinct':
			case 'count':
				$return_value = 0;
				break;
		}
		
		$this->debug('      <span class="osh-loop">start <span class="osh-replacement">'.$operation.'</span> <span class="osh-key">product</span>'
			.(isset($property_type) ? '.<span class="osh-key">'.$property_type.'</span>.<span class="osh-key">'.$property_name.'</span>' : '')
			.' where <span class="osh-replacement">'.$conditions.'</span></span>');
		$this->addDebugIndent();

		$properties = array();

		$this->_preg_match_all('/{PRODUCT_REGEX}\.({ATTRIBUTE_REGEX}|{OPTION_REGEX}|stock)\.([a-z0-9_+-]+)(?:\.(id))?/i', $conditions, $properties_regex_result);
		foreach ($properties_regex_result as $property_regex_result) {
			$key = $property_regex_result[0];
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}
		$this->_preg_match_all('/{PRODUCT_REGEX}\.({CATEGORY_REGEX})(?:\.(id))?/i', $conditions, $properties_regex_result);
		foreach ($properties_regex_result as $property_regex_result) {
			$key = $property_regex_result[0];
			$property_regex_result[1] = 'category';
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}
		$this->_preg_match_all('/{PRODUCT_REGEX}\.({CATEGORIES_REGEX})(?:\.(id))?/i', $conditions, $properties_regex_result);
		foreach ($properties_regex_result as $property_regex_result) {
			$key = $property_regex_result[0];
			$property_regex_result[1] = 'categories';
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}
		$this->_preg_match_all('/{PRODUCT_REGEX}\.({ATTRIBUTE_SET_REGEX})(?:\.(id))?/i', $conditions, $properties_regex_result);
		foreach ($properties_regex_result as $property_regex_result) {
			$key = $property_regex_result[0];
			$property_regex_result[1] = 'attribute_set';
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}
		$this->_preg_match_all('/{PRODUCT_REGEX}\.(quantity)/i', $conditions, $properties_regex_result);
		foreach ($properties_regex_result as $property_regex_result) {
			$key = $property_regex_result[0];
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}

		foreach ($products as $product) {
			$this->debug('     <span class="osh-loop">&bull; product</span> = <span class="osh-formula">'.$product->toString().'</span>');
			$this->addDebugIndent();
			if (isset($conditions) && $conditions!='') {
				$formula = $conditions;
				foreach ($properties as $property) {
					switch ($property[1]) {
						case 'quantity':
							$value = $product->getQuantity();
							break;
						case 'attribute_set':
							$get_by_id = isset($property[2]) && $property[2]=='id';
							$attribute_set = $product->getAttributeSet();
							$value = $attribute_set ? ($get_by_id ? $attribute_set->getId() : $attribute_set->getName()) : null;
							break;
						case 'category':
							$get_by_id = isset($property[2]) && $property[2]=='id';
							$category = $product->getCategory();
							$value = $category ? ($get_by_id ? $category->getId() : $category->getName()) : null;
							break;
						case 'categories':
							$get_by_id = isset($property[2]) && $property[2]=='id';
							$value = array();
							foreach ($product->getCategories() as $category) {
								$value[] = $get_by_id ? $category->getId() : $category->getName();
							}
							break;
						default:
							$value = $this->_getProductProperty(
								$product,
								$tmp_property_type = $property[1],
								$tmp_property_name = $property[2],
								$get_by_id = isset($property[3]) && $property[3]=='id'
							);
					}
					$from = $property[0];
					$to = $this->_autoEscapeStrings($value);
					$this->debug('      replace <span class="osh-replacement">'.$from.'</span> by <span class="osh-replacement">'.$to.'</span> =&gt; <span class="osh-formula">'.str_replace($from, '<span class="osh-replacement">'.$to.'</span>', $formula).'</span>');
					$formula = str_replace($from, $to, $formula);
				}
				$eval_result = $this->_evalFormula($formula);
				if (!isset($eval_result)) $return_value = 'null';
			}
			else $eval_result = true;

			if ($eval_result==true) {
				if ($operation=='count') {
					$return_value = (isset($return_value) ? $return_value : 0) + $product->getQuantity();
				} else {
					if ($property_type=='quantity') {
						$value = $product->getQuantity();
					} else {
						$value = $this->_getProductProperty($product, $property_type, $property_name);
					}
					switch ($operation) {
						case 'min':
							if (!isset($return_value) || $value<$return_value) $return_value = $value;
							break;
						case 'max':
							if (!isset($return_value) || $value>$return_value) $return_value = $value;
							break;
						case 'sum':
							//$this->debug($product->getSku().'.'.$property_type.'.'.$property_name.' = "'.$value.'" x '.$product->getQuantity());
							$return_value = (isset($return_value) ? $return_value : 0) + $value*$product->getQuantity();
							break;
						case 'count distinct':
							if (!isset($return_value)) $return_value = 0;
							if (!isset($distinct_values)) $distinct_values = array();
							if (!in_array($value, $distinct_values)) {
								$distinct_values[] = $value;
								$return_value++;
							}
							break;
					}
				}
			}
			$this->debug('    &raquo; <span class="osh-info">'.$operation.' result</span> = <span class="osh-formula">'.$return_value.'</span>');
			$this->removeDebugIndent();
		}
		
		$this->removeDebugIndent();
		$this->debug('      <span class="osh-loop">end</span>');

		return $return_value;
	}

}

interface OS_AttributeSet {
	public function getName();
	public function getId();
	public function toString();
}

interface OS_Category {
	public function getName();
	public function getId();
	public function toString();
}

interface OS_Product {
	public function getOption($option);
	public function getAttribute($attribute);
	public function getName();
	public function getId();
	public function getSku();
	public function getQuantity();
	public function getCategory();
	public function getStockData($key);
	
	public function toString();
}

class OS_Message {
	public $type;
	public $message;
	public $args;

	public function OS_Message($type, $args) {
		$this->type = $type;
		$this->message = array_shift($args);
		$this->args = $args;
	}
	
	public function toString() {
		return vsprintf($this->message, $this->args);
	}
}

class OS_Result {
	public $success;
	public $result;

	public function OS_Result($success, $result=null) {
		$this->success = $success;
		$this->result = $result;
	}

	public function __toString() {
		return OwebiaShippingHelper::toString($this->result);
	}
}


?>