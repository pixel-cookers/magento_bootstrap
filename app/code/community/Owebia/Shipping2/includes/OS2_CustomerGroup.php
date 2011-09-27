<?php


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