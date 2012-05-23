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

class Owebia_Shipping2_Block_Adminhtml_System_Config_Form_Field_Config extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	private static $JS_INCLUDED = false;
	
	private function label($input) {
		return str_replace(array("\r\n","\r","\n","'"),array("\\n","\\n","\\n","\\'"),$this->__($input));
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		$output = '';
		if (!self::$JS_INCLUDED) {
			$include_path = Mage::getBaseUrl('js').'owebia/shipping2';
			$output = "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js\"></script>\n"
				."<script type=\"text/javascript\" src=\"".$include_path."/os2editor.js?t=".time()."\"></script>\n"
				."<script type=\"text/javascript\">\n"
				."//<![CDATA[\n"
				."jQuery.noConflict();\n"
				."var os2editor = new OS2Editor({\n"
				."ajax_url: '".$this->getUrl('owebia-shipping2/ajax')."?isAjax=true',\n"
				."form_key: FORM_KEY,\n"
				."menu_item_dissociate_label: '".$this->label('Dissociate')."',\n"
				."menu_item_remove_label: '".$this->label('Remove')."',\n"
				."menu_item_edit_label: '".$this->label('Edit')."',\n"
				."prompt_new_value_label: '".$this->label('Enter the new value:')."',\n"
				."default_row_label: '".$this->label('[No label]')."',\n"
				."loading_label: '".$this->label('Loading...')."'\n"
				."});\n"
				."//]]>\n"
				."</script>\n"
				."<link type=\"text/css\" href=\"".$include_path."/os2editor.css?t=".time()."\" rel=\"stylesheet\" media=\"all\"/>\n"
			;
			self::$JS_INCLUDED = true;
		}

		$shipping_code = preg_replace('/^groups\[([^\]]*)\].*$/','\1',$element->getName());
		return $output
			.'<div style="margin-bottom:1px;">'
			.'<button type="button" class="scalable" onclick="os2editor.open(this,\''.$shipping_code.'\');"><span>'.$this->__("Open editor").'</span></button>'
			.'&nbsp;<button type="button" class="scalable" onclick="os2editor.help(\'summary\',this,\''.$shipping_code.'\');"><span>'.$this->__("Help").'</span></button>'
			.'</div>'
			.$element->getElementHtml().'<br/>'
		;
	}
}
