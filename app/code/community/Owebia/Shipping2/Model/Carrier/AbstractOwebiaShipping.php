<?php

/**
 * Magento Owebia Shipping 2 Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Owebia
 * @package    Owebia_Shipping2
 * @copyright  Copyright (c) 2008-11 Owebia (http://www.owebia.com/)
 * @author     Antoine Lemoine
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

// Pour gérer les cas où il y a eu compilation
if (file_exists(dirname(__FILE__).'/Owebia_Shipping2_includes_OwebiaShippingHelper.php')) include_once 'Owebia_Shipping2_includes_OwebiaShippingHelper.php';
else include_once Mage::getBaseDir('code').'/community/Owebia/Shipping2/includes/OwebiaShippingHelper.php';



class Magento_Product implements OS_Product {
	private $parent_cart_item;
	private $cart_item;
	private $cart_product;
	private $loaded_product;
	private $quantity;
	private $options;
	
	public function Magento_Product($cart_item, $parent_cart_item) {
		$this->cart_item = $cart_item;
		$this->cart_product = $cart_item->getProduct();
		$this->parent_cart_item = $parent_cart_item;
		$this->quantity = isset($parent_cart_item) ? $parent_cart_item->getQty() : $cart_item->getQty();
	}

	private function getProductOptions() {
		if (isset($this->options)) return $this->options;
		$item = $this->cart_item;
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
		/*
		foreach ($product->getOptions() as $option) {
			if ($option->getTitle()==$option_name) {
				$custom_option = $product->getCustomOption('option_'.$option->getId());
				if ($custom_option) {
					$value = $custom_option->getValue();
					if ($option->getType()=='drop_down' && !$get_by_id) {
						$option_value = $option->getValueById($value);
						if ($option_value) $value = $option_value->getTitle();
					}
				}
				break;
			}
		}
		*/
	}
	
	public function getAttribute($attribute_name, $get_by_id=false) {
		$value = null;
		$product = $this->_getLoadedProduct();

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

	private function _getLoadedProduct() {
		if (!isset($this->loaded_product)) $this->loaded_product = Mage::getModel('catalog/product')->load($this->cart_product->getId());
		return $this->loaded_product;
	}

	public function getQuantity() {
		return $this->quantity;
	}

	public function getName() {
		return $this->cart_product->getName();
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

abstract class Owebia_Shipping2_Model_Carrier_AbstractOwebiaShipping
	extends Mage_Shipping_Model_Carrier_Abstract
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
			'destination.region.code' => $request->_data['dest_region_code'],
			'destination.postcode' => $request->_data['dest_postcode'],
			'origin.country.code' => $request->_data['country_id'],
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
			if ($item->getProduct()->getTypeId()!='configurable') {
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
		$tracking_url = $this->__getConfigData('tracking_view_url');
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
					'status'=>'<a target="_blank" href="'.str_replace('{tracking_number}',$tracking_number,$tracking_url).'">'.__('track the package').'</a>'
				)
			)
		;
		$tracking_result = Mage::getModel('shipping/tracking_result')
			->append($tracking_status)
		;

		if ($trackings = $tracking_result->getAllTrackings()) return $trackings[0];
		return false;
	}

	/***************************************************************************************************************************/

	protected function _process(&$process) {
		$process['data'] = array_merge($process['data'],array(
			'destination.country.name' => $this->__getCountryName($process['data']['destination.country.code']),
			'origin.country.name' => $this->__getCountryName($process['data']['origin.country.code']),
		));

		$debug = (bool)(isset($_GET['debug']) ? $_GET['debug'] : $this->__getConfigData('debug'));
		if ($debug) $this->_helper->initDebug($this->_code,$process['data']);

		$value_found = false;
		foreach ($process['config'] as $row) {
			$result = $this->_helper->processRow($process,$row);
			$this->_addMessages($this->_helper->getMessages());
			if ($result->success) {
				$value_found = true;
				$this->__appendMethod($process,$row,$result->result);
				if ($process['stop_to_first_match']) break;
			}
		}
		
		$http_request = Mage::app()->getFrontController()->getRequest();
		if ($debug && $this->_checkRequest($http_request,'checkout/cart/index')) {
			Mage::getSingleton('core/session')->addNotice('DEBUG'.$this->_helper->getDebug());
		}

		//$this->_appendErrors($process,$this->_messages);
		//if (!$value_found) $this->__appendError($process,$this->__('No match found'));
	}

	protected function _checkRequest($http_request, $path) {
		list($router,$controller,$action) = explode('/',$path);
		return $http_request->getRouteName()==$router && $http_request->getControllerName()==$controller && $http_request->getActionName()==$action;
	}

	protected function _getConfig() {
		if (!isset($this->_config)) {
			$this->_helper = new OwebiaShippingHelper($this->__getConfigData('config'));
			$this->_config = $this->_helper->getConfig();
			$this->_addMessages($this->_helper->getMessages());
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

	protected function _addMessages($messages) {
		if (!is_array($messages)) $messages = array($messages);
		if (!is_array($this->_messages)) $this->_messages = $messages;
		else $this->_messages = array_merge($this->_messages,$messages);
	}

	protected function _appendErrors(&$process, $messages) {
		if (is_array($messages)) {
			foreach ($messages as $message) {
				$this->__appendError($process,$this->__($message));
			}
		}
	}
	
	/***************************************************************************************************************************/

	protected function __getDefaultProcess() {
		$process = array(
			'cart.products' => array(),
			'config' => $this->_getConfig(),
			'data' => null,
			'result' => Mage::getModel('shipping/rate_result'),
			'stop_to_first_match' => $this->__getConfigData('stop_to_first_match'),
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
		$timestamp = Mage::getModel('core/date')->timestamp();

		$properties = array_merge(OwebiaShippingHelper::getDefaultProcessData(),array(
			'info.magento.version' => Mage::getVersion(),
			'info.module.version' => (string)$mage_config->getNode('modules/Owebia_Shipping2/version'),
			'info.carrier.code' => $this->_code,
			'cart.weight.unit' => Mage::getStoreConfig('owebia/shipping/weight_unit'),
			'cart.coupon' => Mage::getSingleton('checkout/session')->getQuote()->getCouponCode(),
			'customer.group.id' => $customer_group_id,
			'customer.group.code' => $customer_group_code,
			'store.id' => $store->getId(),
			'store.code' => $store->getCode(),
			'store.name' => $store->getConfig('general/store_information/name'),
			'store.address' => $store->getConfig('general/store_information/address'),
			'store.phone' => $store->getConfig('general/store_information/phone'),
			'date.timestamp' => $timestamp,
			'date.year' => (int)date('Y',$timestamp),
			'date.month' => (int)date('m',$timestamp),
			'date.day' => (int)date('d',$timestamp),
			'date.hour' => (int)date('H',$timestamp),
			'date.minute' => (int)date('i',$timestamp),
			'date.second' => (int)date('s',$timestamp),
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