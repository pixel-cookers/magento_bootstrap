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

class Owebia_Shipping2_Controller_Abstract extends Mage_Adminhtml_Controller_Action
{
	public function __() {
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

	protected function getModulePath($path) {
		if (file_exists(dirname(__FILE__).'/Owebia_Shipping2_'.str_replace('/','_',$path))) {
			return 'Owebia_Shipping2_'.str_replace('/','_',$path);
		} else {
			return Mage::getBaseDir('code').'/community/Owebia/Shipping2/'.$path;
		}
	}

	protected function getMimeType($extension) {
		$mime_type_array = array(
			'.gz' => 'application/x-gzip',
			'.tgz' => 'application/x-gzip',
			'.zip' => 'application/zip',
			'.pdf' => 'application/pdf',
			'.png' => 'image/png',
			'.gif' => 'image/gif',
			'.jpg' => 'image/jpeg',
			'.jpeg' => 'image/jpeg',
			'.txt' => 'text/plain',
			'.htm' => 'text/html',
			'.html' => 'text/html',
			'.mpg' => 'video/mpeg',
			'.avi' => 'video/x-msvideo',
		);
		return isset($mime_type_array[$extension]) ? $mime_type_array[$extension] : 'application/octet-stream';
	}

	protected function forceDownload($filename, $content) {
		if (headers_sent()) {
			trigger_error('forceDownload($filename) - Headers have already been sent',E_USER_ERROR);
			return false;
		}

		$extension = strrchr($filename,'.');
		$mime_type = $this->getMimeType($extension);

		header('Content-disposition: attachment; filename="'.$filename.'"');
		header('Content-Type: application/force-download');
		header('Content-Transfer-Encoding: '.$mime_type."\n"); // Surtout ne pas enlever le \n
		//header('Content-Length: '.filesize($filename));
		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		echo $content;
		return true;
	}

	protected function jsEscape($input) {
		return str_replace(array("\r\n","\r","\n","'"),array("\\n","\\n","\\n","\\'"),$input);
	}

	protected function cleanKey($key) {
		return preg_replace('/[^a-z0-9-_]/i','_-_',$key);
	}

	protected function page($id, $title, $content) {
		return "<div id=\"os2editor-".$id."-page\" class=\"os2editor-page\">"
					.$this->pageHeader($title,$this->button('Close',"os2editor.closePage(this);",'cancel'))
					."<div class=\"page-content\">".$content."</div>"
				."</div>"
		;
	}

	protected function pageHeader($title, $buttons) {
		return "<div class=\"content-header\">"
					."<table cellspacing=\"0\"><tr>"
					."<td><h3>".$this->__($title)."</h3></td>"
					."<td class=\"form-buttons\">"
						.$buttons
					."</td>"
					."</tr></table>"
				."</div>"
		;
	}

	protected function untranslated_button($label, $onclick, $class_name='') {
		$class_name = 'scalable'.($class_name!='' ? ' '.$class_name : '');
		return "<button type=\"button\" class=\"".$class_name."\" onclick=\"".$onclick."\"><span>".$label."</span></button>";
	}

	protected function button($label, $onclick, $class_name='') {
		return $this->untranslated_button($this->__($label),$onclick,$class_name);
	}
}

?>