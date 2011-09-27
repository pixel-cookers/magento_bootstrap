<?php

/**
 * Magento Owebia Shipping2 Module
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
 * @copyright  Copyright (c) 2008-10 Owebia (http://www.owebia.com/)
 * @author     Antoine Lemoine
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Owebia_Shipping2_AjaxController extends Owebia_Shipping2_Controller_Abstract
{
	private function getPropertyHelper($row_id, $property_key, $property) {
		$cleaned_property = $this->cleanKey($property_key);
		$prefix = "r-".$row_id."-p-".$cleaned_property;
		$value = isset($property['original_value']) ? $property['original_value'] : (isset($property['value']) ? $property['value'] : '');
		
		switch ($property_key) {
			case 'enabled':
				$enabled = $value!==false;
				return "<p><select id=\"".$prefix."\" class=\"field\">"
						."<option value=\"0\"".($enabled ? '' : ' selected="selected"').">".$this->__('Disabled')."</option>"
						."<option value=\"1\"".($enabled ? ' selected="selected"' : '').">".$this->__('Enabled')."</option>"
					."</select><p>";
				break;
			case 'label':
			case 'description':
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Insert')."</legend>"
					."<p>"
						.$this->button('Destination country',"os2editor.insertAtCaret(this,'{destination.country.name}');")
						.$this->button('Cart weight',"os2editor.insertAtCaret(this,'{cart.weight}');")
						.$this->button('Cart quantity',"os2editor.insertAtCaret(this,'{cart.quantity}');")
						.$this->button('Price including tax',"os2editor.insertAtCaret(this,'{cart.price_including_tax}');")
						.$this->button('Price excluding tax',"os2editor.insertAtCaret(this,'{cart.price_excluding_tax}');")
					."</p>"
					."</fieldset>";
				break;
			case 'fees':
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Insert')."</legend>"
					."<p><span class=\"buttons-set-label\">".$this->__('Cart')."</span>"
						.$this->button('Weight',"os2editor.insertAtCaret(this,'{cart.weight}');")
						.$this->button('Products quantity',"os2editor.insertAtCaret(this,'{cart.quantity}');")
						.$this->button('Price including tax',"os2editor.insertAtCaret(this,'{cart.price_including_tax}');")
						.$this->button('Price excluding tax',"os2editor.insertAtCaret(this,'{cart.price_excluding_tax}');")
					."</p>"
					."<p><span class=\"buttons-set-label\">".$this->__('Selection')."</span>"
						.$this->button('Weight',"os2editor.insertAtCaret(this,'{selection.weight}');")
						.$this->button('Products quantity',"os2editor.insertAtCaret(this,'{selection.quantity}');")
					."</p>"
					."<p><span class=\"buttons-set-label\">".$this->__('Product')."</span>"
						.$this->button('Weight',"os2editor.insertAtCaret(this,'{product.weight}');")
						.$this->button('Quantity',"os2editor.insertAtCaret(this,'{product.quantity}');")
					."</p>"
					."</fieldset>";
				break;
			case 'conditions':
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Insert')."</legend>"
					."<p><span class=\"buttons-set-label\">".$this->__('Cart')."</span>"
						.$this->button('Weight',"os2editor.insertAtCaret(this,'{cart.weight}');")
						.$this->button('Products quantity',"os2editor.insertAtCaret(this,'{cart.quantity}');")
						.$this->button('Price including tax',"os2editor.insertAtCaret(this,'{cart.price_including_tax}');")
						.$this->button('Price excluding tax',"os2editor.insertAtCaret(this,'{cart.price_excluding_tax}');")
					."</p>"
					."</fieldset>";
				break;
			case 'customer_groups':
				$groups = CustomerGroup::getCustomerGroups();
				$output = '';
				foreach ($groups as $group) {
					$output .= $this->untranslated_button(htmlentities($group->getCode()),"os2editor.insertAtCaret(this,'".$this->jsEscape($group->getId())."');");
				}
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Display')."</legend>"
					."<p>"
						.$this->button('Display original input',"os2editor.updatePropertyValue('original-value',this,false);")
						.$this->button('Display names',"os2editor.updatePropertyValue('full-value',this,false);")
						.$this->button('Display identifiers',"os2editor.updatePropertyValue('compact-value',this,true);")
					."</p>"
					."</fieldset>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Insert')."</legend>"
					."<p>"
						//.$this->button('Not logged in',"os2editor.insertAtCaret(this,'NOT LOGGED IN');")
						.$output
					."</p>"
					."</fieldset>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Preview')."</legend>"
						."<div class=\"preview-items-list customer-group-list\">".$this->getCustomerGroupsPreview($value)."</div>"
					."</fieldset>"
				;
				break;
			case 'tracking_url':
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Insert')."</legend>"
					."<p>"
						.$this->button('Tracking number',"os2editor.insertAtCaret(this,'{tracking_number}');")
					."</p>"
					."</fieldset>";
				break;
			case 'destination':
			case 'origin':
				$parsed_value = $this->parseAddressFilter($value);
				$excluding = $parsed_value['excluding'];
				return "<div class=\"address-filters-property\"><p>"
					."<input type=\"radio\" class=\"excluding\" id=\"".$prefix."-exluding-0\" name=\"".$prefix."-exluding\""
						." value=\"0\"".(!$excluding ? " checked=\"checked\"" : '')." onclick=\"os2editor.updateAddressFilterPreview(this);\"/>"
						."<label for=\"".$prefix."-exluding-0\"> ".$this->__('Limit to')."</label> &nbsp; "
					."<input type=\"radio\" class=\"excluding\" id=\"".$prefix."-exluding-1\" name=\"".$prefix."-exluding\""
						." value=\"1\"".($excluding ? " checked=\"checked\"" : '')." onclick=\"os2editor.updateAddressFilterPreview(this);\"/>"
						."<label for=\"".$prefix."-exluding-1\"> ".$this->__('Exclude')."</label></p>"
					."<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Display')."</legend>"
					."<p>"
						.$this->button('Display original input',"os2editor.updatePropertyValue('original-value',this,false);")
						.$this->button('Display corrected names',"os2editor.updatePropertyValue('full-value',this,false);")
						.$this->button('Display codes',"os2editor.updatePropertyValue('compact-value',this,true);")
					."</p>"
					."</fieldset>"
					."<fieldset class=\"buttons-set\"><legend>".$this->__('Preview')."</legend>"
						."<div class=\"preview-items-list address-filter-list\">".$this->getAddressFiltersPreview($parsed_value)."</div>"
					."</fieldset>"
					."</div>"
				;
				break;
			case '*comment' :
				$lines = explode("\n",trim($value));
				for ($i=0; $i<count($lines); $i++) {
					$lines[$i] = preg_replace('/^# ?/','',$lines[$i]);
				}
				$value = implode("\n",$lines);
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>";
			default :
				return "<textarea id=\"".$prefix."\" class=\"field\">".$value."</textarea>";
		}
	}

	private function getCustomerGroupsPreview($input) {
		if (trim($input)=='') return '';
		$elems = explode(',',$input);
		$customer_groups = array();
		foreach ($elems as $elem) {
			$customer_groups[] = new CustomerGroup($elem);
		}
		return implode('',$customer_groups);
	}

	private function getAddressFiltersPreview($data) {
		$address_filters = array();
		foreach ($data['countries'] as $country) {
			$address_filters[] = new AddressFilter($country);
		}
		return implode('',$address_filters);
	}

	private function parseAddressFilter($address_filter) {
		$output = array(
			'excluding' => false,
			'countries' => array(),
			'original' => $address_filter,
		);

		$address_filter = str_replace(
			array('\(', '\)', '\,'),
			array('__opening_parenthesis__', '__closing_parenthesis__', '__comma__'),
			$address_filter
		);
		$address_filter = str_replace(array("\r\n","\r","\n"),array(',',',',','),$address_filter);

		if (preg_match('# *\* *- *\((.*)\) *#s',$address_filter,$result)) {
			$address_filter = $result[1];
			$output['excluding'] = true;
		}

		$tmp_address_filter_array = explode(',',trim($address_filter));
		
		$concat = false;
		$concatened = '';
		$address_filter_array = array();
		$i = 0;
		
		foreach ($tmp_address_filter_array as $address_filter) {
			if ($concat) $concatened .= ','.$address_filter;
			else {
				if ($i<count($tmp_address_filter_array)-1 && preg_match('#\(#',$address_filter)) {
					$concat = true;
					$concatened .= $address_filter;
				} else $address_filter_array[] = $address_filter;
			}
			if (preg_match('#\)#',$address_filter)) {
				$address_filter_array[] = $concatened;
				$concatened = '';
				$concat = false;
			}
			$i++;
		}
		
		foreach ($address_filter_array as $address_filter) {
			$address_filter = trim($address_filter);
			$original_address_filter = str_replace(
				array('__opening_parenthesis__', '__closing_parenthesis__', '__comma__'),
				array('\(', '\)', '\,'),
				$address_filter
			);
			if (trim($address_filter)!='') {
				if (preg_match('# *([^,(]+) *(-)? *(?:\( *(-)? *(.*)\))? *#s', $address_filter,$result)) {
					$country_code = $result[1];

					$region_codes = isset($result[4]) ? explode(',',$result[4]) : array();
					$in_array = false;
					for ($i=count($region_codes); --$i>=0;) {
						$code = trim(str_replace(
							array('__opening_parenthesis__', '__closing_parenthesis__', '__comma__'),
							array('\(', '\)', '\,'),
							$region_codes[$i]
						));
						$region_codes[$i] = $code;
					}
					/*$in_array = in_array($address['region_code'],$region_codes,true) || in_array($address['postcode'],$region_codes,true);*/
					$excluding_region = isset($result[2]) && $result[2]=='-' || isset($result[3]) && $result[3]=='-';
					$output['countries'][] = array(
						'excluding' => $excluding_region,
						'country_code' => $country_code,
						'region_codes' => implode(',',$region_codes),
						'original' => $original_address_filter,
					);
				} else {
					$output['countries'][] = array(
						'excluding' => null,
						'country_code' => $original_address_filter,
						'region_codes' => null,
						'original' => $original_address_filter,
					);
				}
			}
		}
		return $output;
	}

	private function getRowUI(&$row, $selected) {
		$row['_ID_']['value'] = isset($row['_ID_']['value']) ? $row['_ID_']['value'] : uniqid('c');
		$row_id = $row['_ID_']['value'];

		if (isset($row['lines'])) {
			$output = "<div id=\"r-".$row_id."-container\" class=\"row-container has-error ignored-lines".($selected ? ' selected' : '')."\">"
				."<div class=\"row-header\" onclick=\"os2editor.selectRow('".$row_id."');\">"
					."<div class=\"row-actions\">"
						.$this->button('Apply changes',"os2editor.applyChanges();")
						.$this->button('Delete',"os2editor.removeRow(this);",'delete')
					."</div>"
					."<div class=\"row-title\">".$this->__('Ignored lines')."</div></div>"
				."<div class=\"properties-container\"><textarea class=\"field\">".$row['lines']."</textarea></div></div>";
			return $output;
		}

		if (!isset($row['label'])) {
			$row['label']['value'] = $this->__('New shipping method');
		}

		$properties = array(
			'enabled' => 'Enabled',
			'code' => 'Code',
			'label' => 'Label',
			'description' => 'Description',
			'destination' => 'Destination',
			'origin' => 'Origin',
			'conditions' => 'Conditions',
			'fees' => 'Fees',
			'customer_groups' => 'Customer groups',
			'tracking_url' => 'Tracking url',
			'*comment' => 'Comment',
		);

		$label = $row['label']['value'];
		$output = "<div id=\"r-".$row_id."-container\" class=\"row-container".($selected ? ' selected' : '')."\">"
			."<div class=\"row-header\" onclick=\"os2editor.selectRow('".$row_id."');\">"
				."<div class=\"row-actions\">".$this->button('Delete',"os2editor.removeRow(this);",'delete')."</div><div class=\"row-title\">".$label."</div></div>"
			."<div class=\"properties-container\">";
		$list = "<ul class=\"properties-list\">";
		$j = 0;
		foreach ($properties as $property_key => $label) {
			$cleaned_property = $this->cleanKey($property_key);
			$value = isset($row[$property_key]) ? trim($row[$property_key]['value']) : '';
			$list .= "<li id=\"r-".$row_id."-p-".$cleaned_property."-item\" class=\"property-item".($j==0 ? ' selected' : '')
				.($value==='' ? ' empty' : '')
				."\" onclick=\"os2editor.selectProperty('".$row_id."','".$cleaned_property."');\">".$this->__($label)."</li>";
			$output .= "<div id=\"r-".$row_id."-p-".$cleaned_property."-container\" class=\"property-container"
				.($j==0 ? ' selected' : '')."\" property-name=\"".$property_key."\">"
				."<div class=\"buttons-set\" style=\"text-align:right;\">".$this->button('Help',"os2editor.help('property.".$property_key."');",'help')."</div>"
				.$this->getPropertyHelper($row_id,$property_key,isset($row[$property_key]) ? $row[$property_key] : array())."</div>";
			$j++;
		}
		foreach ($row as $property_key => $property) {
			if (!isset($properties[$property_key]) && substr($property_key,0,1)!='*') {
				$label = $property_key;
				$cleaned_property = $this->cleanKey($property_key);
				$value = isset($row[$property_key]) ? trim($row[$property_key]['value']) : '';
				$list .= "<li id=\"r-".$row_id."-p-".$cleaned_property."-item\" class=\"property-item".($j==0 ? ' selected' : '')
					.(empty($value) ? ' empty' : '').($cleaned_property=='_ID_' ? ' hide' : '')
					."\" onclick=\"os2editor.selectProperty('".$row_id."','".$cleaned_property."');\">".$this->__($label)."</li>";
				$output .= "<div id=\"r-".$row_id."-p-".$cleaned_property."-container\" class=\"property-container"
					.($j==0 ? ' selected' : '')."\" property-name=\"".$property_key."\">"
					."<div class=\"buttons-set\" style=\"text-align:right;\">".$this->button('Help',"os2editor.help('property.".$property_key."');",'help')."</div>"
					.$this->getPropertyHelper($row_id,$property_key,$property)."</div>";
				$j++;
			}
		}
		$output .= $list."</div></div>";
		return $output;
	}

	private function getConfigErrors($config) {
		$script = "os2editor.resetErrors();";
		foreach ($config as $row_code => $row) {
			if (isset($row['*messages'])) {
				$error = '';
				foreach ($row['*messages'] as $message) {
					$error .= "<p>".$this->__($message)."</p>";
				}
				if ($error!='') $script .= "os2editor.setError('".$row['_ID_']['value']."','','".$this->jsEscape($error)."');";
			}
			foreach ($row as $property_key => $property) {
				if (isset($property['messages']) && is_array($property['messages'])) {
					$error = '';
					foreach ($property['messages'] as $message) {
						$error .= "<p>".$this->__($message)."</p>";
					}
					if ($error!='') {
						$script .= "os2editor.setError('".$row['_ID_']['value']."','".$property_key."','"
							.$this->jsEscape($error
								.($property['value']!=$property['original_value'] ? 
									"<p>"
									.$this->button('Correct',"os2editor.correct('".$row['_ID_']['value']."','".$property_key."','".$this->jsEscape($property['value'])."');")
									."</p>" : '')
							)."');";
					}
				}
			}
		}
		//$script .= "alert('".str_replace(array("\r\n","\n","\'","'"),array(" "," ","\\\'","\'"),$script)."');";
		return $script;
	}

	private function loadConfig($input) {
		include_once $this->getModulePath('includes/OwebiaShippingHelper.php');

		$helper = new OwebiaShippingHelper($input);
		$helper->checkConfig();
		$config = $helper->getConfig();
		//print_r($config);
		
		$output = "<div class=\"buttons-set\">"
				.$this->button('Add a shipping method',"os2editor.addRow();",'add')
			."</div><div class=\"config-container\">";
		$i = 0;
		foreach ($config as &$row) {
			$output .= $this->getRowUI($row,$i==0);
			$i++;
		}
		$output .= "</div><script type=\"text/javascript\">".$this->getConfigErrors($config)."</script>";
		return $output;
	}

	public function indexAction() {
		header('Content-Type: text/html; charset=UTF-8');

		include_once $this->getModulePath('includes/OS2_AddressFilter.php');
		include_once $this->getModulePath('includes/OS2_CustomerGroup.php');

		switch ($_POST['what']) {
			case 'open':
				$output = ""
					// Donate page
					.$this->page('donate',"Support the development of Owebia Shipping 2 extension",$this->__('{os2editor.donate-page.content}'))
					// Help page
					.$this->page('help',"Owebia Shipping 2 extension help",'')
					// Main page
					.$this->pageHeader("Owebia Shipping 2 configuration editor",
						$this->button('Save',"os2editor.save();",'save')
						.$this->button('Export',"os2editor.saveToFile();",'')
						.$this->button('Load',"os2editor.showConfigLoader();",'')
						.$this->button('Close',"os2editor.close();",'cancel')
					)
					."<div id=\"os2-editor-config-loader\">"
						."<textarea></textarea>"
						."<div class=\"buttons-set\">"
							.$this->button('Load',"os2editor.loadConfig();",'')
							.$this->button('Cancel',"os2editor.hideConfigLoader();",'cancel')
						."</div>"
					."</div>"
					."<div id=\"os2-editor-config-container\">".$this->loadConfig($_POST['input'])."</div>"
					."<div class=\"donate-container\">"
						."<table cellspacing=\"0\"><tr>"
						."<td>".$this->__('You appreciate this extension and would like to help?')."</td>"
						."<td class=\"form-buttons\">"
							.$this->button('Donate',"os2editor.openPage('donate');",'donate')
						."</td>"
						."</tr></table>"
					."</div>"
				;
				echo $output;
				exit;
			case 'help':
				$output = $this->__('{os2editor.help.'.$_POST['input'].'}');
				if ($_POST['input']=='changelog') {
					$changelog = @file_get_contents($this->getModulePath('changelog'));
					$output = str_replace('{changelog}',htmlspecialchars(mb_convert_encoding($changelog,'UTF-8','ISO-8859-1'), ENT_QUOTES, 'UTF-8'),$output);
				}
				echo $output;
				exit;
			case 'add-row':
				$row = array(); // Passage par référence
				echo $this->getRowUI($row,true);
				exit;
			case 'load-config':
				echo $this->loadConfig($_POST['config']);
				exit;
			case 'check-config':
				include_once $this->getModulePath('includes/OwebiaShippingHelper.php');

				$helper = new OwebiaShippingHelper($_POST['config']);
				$helper->checkConfig();
				//print_r($helper->getConfig(),$out);
				//$script = "alert('".$this->jsEscape($_POST['config'])."');";
				$script = $this->getConfigErrors($helper->getConfig());
				//$script = "alert('".$this->jsEscape($this->getConfigErrors($helper->getConfig()))."');";
				break;
			case 'save-config':
				include_once $this->getModulePath('includes/OwebiaShippingHelper.php');

				$helper = new OwebiaShippingHelper($_POST['config']);
				$compress = (bool)Mage::getStoreConfig('carriers/'.$_POST['shipping_code'].'/compression');
				$output = $helper->formatConfig($compress,$keys_to_remove=array('_ID_'));
				//Mage::getConfig()->saveConfig('carriers/'.$_POST['shipping_code'].'/config',$output);
				echo $output;
				exit;
			case 'save-to-file':
				include_once $this->getModulePath('includes/OwebiaShippingHelper.php');

				$helper = new OwebiaShippingHelper(urldecode($_POST['config']));
				$formatted_config = $helper->formatConfig(false,$keys_to_remove=array('_ID_'));
				$this->forceDownload('owebia-shipping-config.txt',$formatted_config);
				exit;
			case 'get-address-filters':
				$result = $this->parseAddressFilter($_POST['input']);
				echo $this->getAddressFiltersPreview($result);
				exit;
			case 'get-customer-groups':
				echo $this->getCustomerGroupsPreview($_POST['input']);
				exit;
		}

		echo "<script type=\"text/javascript\">".$script."</script>";
		exit;
	}
}
