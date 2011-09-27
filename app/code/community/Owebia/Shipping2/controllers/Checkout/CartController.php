<?php

if (file_exists(dirname(__FILE__).'/Mage_Checkout_CartController.php')) include_once 'Mage_Checkout_CartController.php';
else include_once Mage::getBaseDir('code').'/core/Mage/Checkout/controllers/CartController.php';

class Owebia_Shipping2_Checkout_CartController extends Mage_Checkout_CartController
{
    /**
     * Initialize shipping information
     */
    public function estimatePostAction()
    {
        $country    = (string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
        $city       = (string) $this->getRequest()->getParam('estimate_city');
        $regionId   = (string) $this->getRequest()->getParam('region_id');
        $region     = (string) $this->getRequest()->getParam('region');

        $this->_getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
            ->setCollectShippingRates(true);
		
		// Recalcul des totaux
		$this->_getQuote()->collectTotals();

        $this->_getQuote()->save();
        $this->_goBack();
    }
}
