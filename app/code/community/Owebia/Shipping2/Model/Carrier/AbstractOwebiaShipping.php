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

// Pour gérer les cas où il y a eu compilation
if (file_exists(dirname(__FILE__).'/Owebia_Shipping2_includes_OwebiaShippingHelper.php')) include_once 'Owebia_Shipping2_includes_OwebiaShippingHelper.php';
else include_once Mage::getBaseDir('code').'/community/Owebia/Shipping2/includes/OwebiaShippingHelper.php';


class Magento_AttributeSet implements OS_AttributeSet {
	private $id;
	private $loaded_attribute_set;
	public function __construct($id) {
		$this->id = (int)$id;
	}
	private function _loadAttributeSet() {
		if (!isset($this->loaded_attribute_set)) $this->loaded_attribute_set = Mage::getModel('eav/entity_attribute_set')->load($this->id);
		return $this->loaded_attribute_set;
	}
	public function getId() {
		return $this->id;
	}
	public function getName() {
		$attribute_set = $this->_loadAttributeSet();
		return $attribute_set->getAttributeSetName();
	}
	public function toString() {
		return $this->getName().' (id:'.$this->getId().')';
	}
}

class Magento_Category implements OS_Category {
	private $id;
	private $loaded_category;
	public function __construct($id) {
		$this->id = (int)$id;
	}
	private function _loadCategory() {
		if (!isset($this->loaded_category)) $this->loaded_category = Mage::getModel('catalog/category')->load($this->id);
		return $this->loaded_category;
	}
	public function getId() {
		return $this->id;
	}
	public function getName() {
		$category = $this->_loadCategory();
		return $category->getName();
	}
	public function toString() {
		return $this->getName().' (id:'.$this->getId().', url_key:'.$this->_loadCategory()->getUrlKey().')';
	}
}

class Magento_Product implements OS_Product {
	private $parent_cart_item;
	private $cart_item;
	private $cart_product;
	private $loaded_product;
	private $type;
	private $quantity;
	private $options;
	private $categories;
	
	public function __construct($cart_item, $parent_cart_item) {
		$this->cart_item = $cart_item;
		$this->parent_cart_item = $parent_cart_item;
		$this->type = $parent_cart_item ? $parent_cart_item->getProduct()->getTypeId() : $cart_item->getProduct()->getTypeId();
		$this->quantity = $parent_cart_item ? $parent_cart_item->getQty() : $cart_item->getQty();
		if ($this->type=='bundle') $this->cart_product = $parent_cart_item->getProduct();
		else $this->cart_product = $cart_item->getProduct();
	}

	private function getProductOptions() {
		if (isset($this->options)) return $this->options;
		$item = isset($this->parent_cart_item) ? $this->parent_cart_item : $this->cart_item; // For configurable products with options
		$options = array();
		if ($optionIds = $item->getOptionByCode('option_ids')) {
			foreach (explode(',', $optionIds->getValue()) as $optionId) {
				if ($option = $item->getProduct()->getOptionById($optionId)) {
					$quoteItemOption = $item->getOptionByCode('option_' . $option->getId());

					$group = $option->groupFactory($option->getType())
						->setOption($option)
						->setQuoteItemOption($quoteItemOption);

					$label = $option->getTitle();
					$options[$label] = array(
						'label' => $label,
						'value' => $group->getFormattedOptionValue($quoteItemOption->getValue()),
						'print_value' => $group->getPrintableOptionValue($quoteItemOption->getValue()),
						'value_id' => $quoteItemOption->getValue(),
						'option_id' => $option->getId(),
						'option_type' => $option->getType(),
						'custom_view' => $group->isCustomizedView()
					);
				}
			}
		}
		if ($addOptions = $item->getOptionByCode('additional_options')) {
			$options = array_merge($options, unserialize($addOptions->getValue()));
		}
		$this->options = $options;
		return $this->options;
	}
	
	public function getOption($option_name, $get_by_id=false) {
		$value = null;
		$product = $this->cart_product;
		$options = $this->getProductOptions();
		if (isset($options[$option_name])) return $get_by_id ? $options[$option_name]['value_id'] : $options[$option_name]['value'];
		else return $value;
	}
	
	public function getAttribute($attribute_name, $get_by_id=false) {
		$value = null;
		$product = $this->_loadProduct();

		if ($attribute_name=='price-tax+discount') {
			return $this->cart_item['base_original_price']-$this->cart_item['discount_amount']/$this->quantity;
		} else if ($attribute_name=='price-tax-discount') {
			return $this->cart_item['base_original_price'];
		} else if ($attribute_name=='price+tax+discount') {
			return $this->cart_item['base_original_price']+($this->cart_item['tax_amount']-$this->cart_item['discount_amount'])/$this->quantity;
		} else if ($attribute_name=='price+tax-discount') {
			return $this->cart_item['price_incl_tax'];
			//return Mage::helper('checkout')->getPriceInclTax($this->cart_item);
		}
		// Dynamic weight for bundle product
		if ($this->type=='bundle' && $attribute_name=='weight' && $product->getData('weight_type')==0) {
			// !!! Use cart_product and not product
			return $this->cart_product->getTypeInstance(true)->getWeight($this->cart_product);
		}

		$attribute = $product->getResource()->getAttribute($attribute_name);
		if ($attribute) {
			$input_type = $attribute->getFrontend()->getInputType();
			switch ($input_type) {
				case 'select' :
					$value = $get_by_id ? $product->getData($attribute_name) : $product->getAttributeText($attribute_name);
					break;
				default :
					$value = $product->getData($attribute_name);
					break;
			}
		}
		return $value;
	}

	private function _loadProduct() {
		if (!isset($this->loaded_product)) $this->loaded_product = Mage::getModel('catalog/product')->load($this->cart_product->getId());
		return $this->loaded_product;
	}

	public function toString() {
		return $this->getName().' (id:'.$this->getId().', sku:'.$this->getSku().')';
	}

	public function getQuantity() {
		return $this->quantity;
	}

	public function getName() {
		return $this->cart_product->getName();
	}

	public function getId() {
		return $this->cart_product->getId();
	}

	public function getAttributeSet() {
		if (!isset($this->attribute_set)) {
			$product = $this->_loadProduct();
			$this->attribute_set = new Magento_AttributeSet($product->getAttributeSetId());
		}
		return $this->attribute_set;
	}

	public function getCategory() {
		$categories = $this->getCategories();
		return $categories ? $categories[0] : null;
	}

	public function getCategories() {
		if (!isset($this->categories)) {
			$product = $this->_loadProduct();
			$ids = $product->getCategoryIds();
			$this->categories = array();
			foreach ($ids as $id) {
				$this->categories[] = new Magento_Category($id);
			}
		}
		return $this->categories;
	}

	public function getWeight() {
		return $this->cart_product->getWeight();
	}

	public function getSku() {
		return $this->cart_product->getSku();
	}

	public function getStockData($key) {
		$stock = $this->cart_product->getStockItem();
		switch ($key) {
			case 'is_in_stock':
				return (bool)$stock->getIsInStock();
			case 'quantity':
				$quantity = $stock->getQty();
				return $stock->getIsQtyDecimal() ? (float)$quantity : (int)$quantity;
		}
		return null;
	}
}

abstract class Owebia_Shipping2_Model_Carrier_AbstractOwebiaShipping extends Mage_Shipping_Model_Carrier_Abstract
{
	protected $_translate_inline;
	protected $_result;
	protected $_config;
	protected $_countries;
	protected $_helper;
	protected $_messages;

	/**
	 * Collect rates for this shipping method based on information in $request
	 *
	 * @param Mage_Shipping_Model_Rate_Request $data
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		//setlocale(LC_NUMERIC, 'fr_FR');
	
		// skip if not enabled
		if (!$this->__getConfigData('active')) return false;

		//$this->display($request->_data);
		
		$process = $this->__getDefaultProcess();
		$process['data'] = array_merge($this->__getDefaultProcessData($request->_data['store_id']),array(
			'cart.price-tax+discount' => $request->_data['package_value_with_discount'],
			'cart.price-tax-discount' => $request->_data['package_value'],
			'cart.weight' => $request->_data['package_weight'],
			'cart.quantity' => $request->_data['package_qty'],
			'destination.country.code' => $request->_data['dest_country_id'],
			'destination.country.name' => $this->__getCountryName($request->_data['dest_country_id']),
			'destination.region.code' => $request->_data['dest_region_code'],
			'destination.postcode' => $request->_data['dest_postcode'],
			'origin.country.code' => $request->_data['country_id'],
			'origin.country.name' => $this->__getCountryName($request->_data['country_id']),
			'origin.region.code' => $request->_data['region_id'],
			'origin.postcode' => $request->_data['postcode'],
			'free_shipping' => $request->getFreeShipping(),
		));

		$tax_amount = 0;
		$full_price = 0;
		$cart_items = array();
		$items = $request->getAllItems();
		$quote_total_collected = false;
		for ($i=0, $n=count($items); $i<$n; $i++) {
			$item = $items[$i];
			if ($item->getProduct() instanceof Mage_Catalog_Model_Product) {
				switch (get_class($item)) {
					case 'Mage_Sales_Model_Quote_Address_Item': // Multishipping
						$key = $item->getQuoteItemId();
						break;
					case 'Mage_Sales_Model_Quote_Item': // Onepage checkout
					default:
						$key = $item->getId();
						break;
				}
				
				$cart_items[$key] = $item;
				$tax_amount += $item->getData('tax_amount');
				$full_price += Mage::helper('checkout')->getSubtotalInclTax($item); // ok
			}
		}

		$process['data']['cart.price+tax+discount'] = $tax_amount+$process['data']['cart.price-tax+discount'];
		$process['data']['cart.price+tax-discount'] = $full_price;
		$process['data']['cart.price_excluding_tax'] = $process['data']['cart.price-tax+discount'];
		$process['data']['cart.price_including_tax'] = $process['data']['cart.price+tax+discount'];
		$process['data']['cart.weight.for-charge'] = $process['data']['cart.weight'];

		foreach ($cart_items as $item) {
			if ($item->getProduct()->getTypeId()!='configurable' && $item->getProduct()->getTypeId()!='bundle') {
				$parent_item_id = $item->getParentItemId();
				$magento_product = new Magento_Product($item, isset($cart_items[$parent_item_id]) ? $cart_items[$parent_item_id] : null);
				$process['cart.products'][] = $magento_product;
				if ($item->getFreeShipping()) $process['data']['cart.weight.for-charge'] -= $magento_product->getWeight() * $magento_product->getQuantity();
			}
		}

		if (!$process['data']['free_shipping']) {
			foreach ($cart_items as $item) {
				if ($item->getProduct() instanceof Mage_Catalog_Model_Product) {
					if ($item->getFreeShipping()) $process['data']['free_shipping'] = true;
					else {
						$process['data']['free_shipping'] = false;
						break;
					}
				}
			}
		}

		return $this->getRates($process);
	}
	
	public function display($var) {
		$i = 0;
		foreach ($var as $name => $value) {
			//if ($i>20)
				echo "{$name} => {$value}<br/>";
				//$this->_helper->debug($name.' => '.$value.'<br/>');
			$i++;
		}
	}

	public function getRates($process) {
		$this->_process($process);
		return $process['result'];
	}

	public function getAllowedMethods() {
		$process = array();
		$config = $this->_getConfig();
		$allowed_methods = array();
		if (count($config)>0) {
			foreach ($config as $row) $allowed_methods[$row['*code']] = isset($row['label']) ? $row['label']['value'] : 'No label';
		}
		return $allowed_methods;
	}

	public function isTrackingAvailable() {
		return true;
	}

	public function getTrackingInfo($tracking_number) {
		$original_tracking_number = $tracking_number;
		$global_tracking_url = $this->__getConfigData('tracking_view_url');
		$tracking_url = $global_tracking_url;
		$parts = explode(':',$tracking_number);
		if (count($parts)>=2) {
			$tracking_number = $parts[1];

			$process = array();
			$config = $this->_getConfig();
			
			if (isset($config[$parts[0]]['tracking_url'])) {
				$row = $config[$parts[0]];
				$tmp_tracking_url = $this->_helper->getRowProperty($row,'tracking_url');
				if (isset($tmp_tracking_url)) $tracking_url = $tmp_tracking_url;
			}
		}

		$tracking_status = Mage::getModel('shipping/tracking_result_status')
			->setCarrier($this->_code)
			->setCarrierTitle($this->__getConfigData('title'))
			->setTracking($tracking_number)
			->addData(
				array(
					'status'=> $tracking_url ? '<a target="_blank" href="'.str_replace('{tracking_number}',$tracking_number,$tracking_url).'">'.__('track the package').'</a>' : "suivi non disponible pour le colis {$tracking_number} (original_tracking_number='{$original_tracking_number}', global_tracking_url='{$global_tracking_url}'".(isset($row) ? ", tmp_tracking_url='{$tmp_tracking_url}'" : '').")"
				)
			)
		;
		$tracking_result = Mage::getModel('shipping/tracking_result')
			->append($tracking_status)
		;

		if ($trackings = $tracking_result->getAllTrackings()) return $trackings[0];
		return false;
	}
	
	public function getCustomVar($var) {
		return Mage::getModel('core/variable')->loadByCode($var)->getValue('text');
	}

	/***************************************************************************************************************************/

	protected function _process(&$process) {
		$timestamp = $process['data']['date.timestamp'];
		$process['data'] = array_merge($process['data'],array(
			'date.year' => (int)date('Y',$timestamp),
			'date.month' => (int)date('m',$timestamp),
			'date.day' => (int)date('d',$timestamp),
			'date.hour' => (int)date('H',$timestamp),
			'date.minute' => (int)date('i',$timestamp),
			'date.second' => (int)date('s',$timestamp),
		));

		$debug = (bool)(isset($_GET['debug']) ? $_GET['debug'] : $this->__getConfigData('debug'));
		if ($debug) $this->_helper->initDebug($this->_code,$process['data']);

		$value_found = false;
		foreach ($process['config'] as $row) {
			$result = $this->_helper->processRow($process,$row);
			if ($result->success) {
				$value_found = true;
				$this->__appendMethod($process,$row,$result->result);
				if ($process['stop_to_first_match']) break;
			}
		}
		
		$http_request = Mage::app()->getFrontController()->getRequest();
		if ($debug && $this->__checkRequest($http_request,'checkout/cart/index')) {
			Mage::getSingleton('core/session')->addNotice('DEBUG'.$this->_helper->getDebug());
		}
	}

	protected function _getConfig() {
		if (!isset($this->_config)) {
			$this->_helper = new OwebiaShippingHelper($this->__getConfigData('config'));
			$this->_config = $this->_helper->getConfig();
		}
		return $this->_config;
	}

	protected function _getMethodText($process, $row, $property) {
		if (!isset($row[$property])) return '';

		$output = '';
		return $output . ' '.$this->_helper->evalInput($process,$row,$property,str_replace(
			array(
				'{cart.weight}',
				'{cart.price_including_tax}',
				'{cart.price_excluding_tax}',
				'{cart.price-tax+discount}',
				'{cart.price-tax-discount}',
				'{cart.price+tax+discount}',
				'{cart.price+tax-discount}',
			),
			array(
				$process['data']['cart.weight'].$process['data']['cart.weight.unit'],
				$this->__formatPrice($process['data']['cart.price_including_tax']),
				$this->__formatPrice($process['data']['cart.price_excluding_tax']),
				$this->__formatPrice($process['data']['cart.price-tax+discount']),
				$this->__formatPrice($process['data']['cart.price-tax-discount']),
				$this->__formatPrice($process['data']['cart.price+tax+discount']),
				$this->__formatPrice($process['data']['cart.price+tax-discount']),
			),
			$this->_helper->getRowProperty($row, $property)
		));
	}

	/***************************************************************************************************************************/

	protected function __checkRequest($http_request, $path) {
		list($router,$controller,$action) = explode('/',$path);
		return $http_request->getRouteName()==$router && $http_request->getControllerName()==$controller && $http_request->getActionName()==$action;
	}

	protected function __getDefaultProcess() {
		$process = array(
			'cart.products' => array(),
			'config' => $this->_getConfig(),
			'data' => null,
			'result' => Mage::getModel('shipping/rate_result'),
			'stop_to_first_match' => $this->__getConfigData('stop_to_first_match'),
			'store_interface' => $this,
		);
		return $process;
	}

	protected function __getDefaultProcessData($store_id=null) {
		if (!isset($store_id)) $store = Mage::app()->getStore();
		else $store = Mage::app()->getStore($store_id);

		$mage_config = Mage::getConfig();
		$customer_group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
		if ($customer_group_id==0) { // Pour les commandes depuis Adminhtml
			$customer_group_id2 = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerGroupId();
			if (isset($customer_group_id2)) $customer_group_id = $customer_group_id2;
		}
		$customer_group_code = Mage::getSingleton('customer/group')->load($customer_group_id)->getCode();
		
		$coupon_code = null;
		$session = Mage::getSingleton('checkout/session');
		if ($session && ($quote = $session->getQuote()) && $quote->hasCouponCode() && $quote->getCouponCode()) {
			$coupon_code = $quote->getCouponCode();
		} else { // Pour les commandes depuis Adminhtml
			$session = Mage::getSingleton('adminhtml/session_quote');
			if ($session && ($quote = $session->getQuote()) && $quote->hasCouponCode() && $quote->getCouponCode()) {
				$coupon_code = $quote->getCouponCode();
			}
		}

		$properties = array_merge(array(
				'info.magento.version' => Mage::getVersion(),
			),
			OwebiaShippingHelper::getDefaultProcessData(),
			array(
				'info.module.version' => (string)$mage_config->getNode('modules/Owebia_Shipping2/version'),
				'info.carrier.code' => $this->_code,
				'cart.weight.unit' => Mage::getStoreConfig('owebia/shipping/weight_unit'),
				'cart.coupon' => $coupon_code,
				'customer.group.id' => $customer_group_id,
				'customer.group.code' => $customer_group_code,
				'store.id' => $store->getId(),
				'store.code' => $store->getCode(),
				'store.name' => $store->getConfig('general/store_information/name'),
				'store.address' => $store->getConfig('general/store_information/address'),
				'store.phone' => $store->getConfig('general/store_information/phone'),
				'date.timestamp' => Mage::getModel('core/date')->timestamp(),
		));
		return $properties;
	}

	protected function __getConfigData($key) {
		return $this->getConfigData($key);
	}

	protected function __appendMethod(&$process, $row, $fees) {
		$method = Mage::getModel('shipping/rate_result_method')
			->setCarrier($this->_code)
			->setCarrierTitle($this->__getConfigData('title'))
			->setMethod($row['*code'])
			->setMethodTitle($this->_getMethodText($process,$row,'label'))
			->setMethodDescription($this->_getMethodText($process,$row,'description'))
			->setPrice($fees)
			->setCost($fees)
		;

		$process['result']->append($method);
	}

	/*
	protected function __appendError(&$process, $message) {
		if (isset($process['result'])) {
			$error = Mage::getModel('shipping/rate_result_error')
				->setCarrier($this->_code)
				->setCarrierTitle($this->__getConfigData('title'))
				->setErrorMessage($message)
			;
			$process['result']->append($error);
		}
	}
	*/
	
	protected function __formatPrice($price) {
		if (!isset($this->_core_helper)) $this->_core_helper = Mage::helper('core');
		return $this->_core_helper->currency($price);
	}

	protected function __($message) {
		$args = func_get_args();
		$message = array_shift($args);
		if ($message instanceof OS_Message) {
			$args = $message->args;
			$message = $message->message;
		}
		
		$output = Mage::helper('shipping')->__($message);
		if (count($args)==0) return $output;

		if (!isset($this->_translate_inline)) $this->_translate_inline = Mage::getSingleton('core/translate')->getTranslateInline();
		if ($this->_translate_inline) {
			$parts = explode('}}{{',$output);
			$parts[0] = vsprintf($parts[0],$args);
			return implode('}}{{',$parts);
		}
		else return vsprintf($output,$args);
	}

	protected function __getCountryName($country_code) {
		return Mage::getModel('directory/country')->load($country_code)->getName();
	}
}

?>