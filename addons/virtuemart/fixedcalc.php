<?php

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

class fixedCalculationHelper extends calculationHelper
{
    public function __construct($vendorId = 1, $countryId = 0, $stateId = 0)
    {
        $this->_db = Factory::getDbo();
        $config = Factory::getConfig();
        $offset = $config->get('offset');

        $jnow = Factory::getDate($offset);
        $this->_now = $jnow->toSQL();
        $this->_nullDate = $this->_db->getNullDate();

        $this->productVendorId = $vendorId;

        $this->_currencyDisplay = CurrencyDisplay::getInstance();
        $this->_debug = false;

        // This breaks the price calculation even if setShopperGroupIds is called later with another shopper group ID
        //$this->setShopperGroupIds();

        $this->setCountryState($countryId, $stateId);
        $this->setVendorId($this->productVendorId);

        $this->rules['Marge'] = [];
        $this->rules['Tax'] = [];
        $this->rules['VatTax'] = [];
        $this->rules['DBTax'] = [];
        $this->rules['DATax'] = [];

        //round only with internal digits
        $this->_roundindig = VmConfig::get('roundindig', false);
        $this->optimisedCalcSql = VmConfig::get('optimisedCalcSql', true);
    }

    public function setCorrectShopperGroupIds($shoppergroups)
    {
        $this->setShopperGroupIds($shoppergroups);
    }
}
