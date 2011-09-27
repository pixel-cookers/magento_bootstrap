<?php

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
