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

class CustomerGroup
{
	private static $CUSTOMER_GROUP_COLLECTION;

	private $input;
	private $customer_group;
	private $classes;
	private $label;

	public static function getCustomerGroups() {
		if (isset(self::$CUSTOMER_GROUP_COLLECTION)) return self::$CUSTOMER_GROUP_COLLECTION;
		else return self::$CUSTOMER_GROUP_COLLECTION = Mage::getResourceModel('customer/group_collection');
	}

	public function CustomerGroup($input) {
		$this->input = $input;
		$this->label = null;
		$this->classes = array();
		$this->customer_group = null;
		$this->parse();
	}

	private function setCustomerGroup($customer_group) {
		$this->customer_group = $customer_group;
		$this->classes = array('known');
		$this->label = $customer_group->getCode();
	}

	public function parse() {
		$input = trim($this->input);
		$customer_groups = self::getCustomerGroups();
		if (ctype_digit($input)) {
			$customer_id = (int)$input;
			foreach ($customer_groups as $group) {
				if ($group->getId()==$customer_id) {
					$this->setCustomerGroup($group);
					break;
				}
			}
		}
		if (!isset($this->customer_group)) {
			$customer_code = $input;
			foreach ($customer_groups as $group) {
				if ($group->getCode()==$customer_code) {
					$this->setCustomerGroup($group);
					break;
				}
			}
		}
		if (!isset($this->customer_group)) {
			$this->classes = array('unknown');
			$this->label = $this->input;
		}
	}
	
	public function hasClass($class) {
		return in_array($class,$this->classes);
	}
	
	public function __toString() {
		$output = '';
		if (isset($this->customer_group)) {
			$compact_value = $this->customer_group->getId();
			$full_value = $this->customer_group->getCode();
		} else {
			$compact_value = $this->input;
			$full_value = $this->input;
		}
		$output .= '<span class="preview-item customer-group '.implode(' ',$this->classes)
			.'" full-value="'.$full_value.'" compact-value="'.$compact_value.'" original-value="'.$this->input.'">'
			.$this->label.'</span>';
		return $output;
	}
}

?>