<?php

defined('_JEXEC') or die();

class fixedCalculationHelper extends calculationHelper
{
    public function __construct($vendorId = 1, $countryId = 0, $stateId = 0)
    {
        $this->_db = JFactory::getDBO();
        $this->_app = JFactory::getApplication();
        //We store in UTC and use here of course also UTC
        $jnow = JFactory::getDate();
        $this->_now = $jnow->toSQL();
        $this->_nullDate = $this->_db->getNullDate();

        $this->productVendorId = $vendorId;

        $this->_currencyDisplay = CurrencyDisplay::getInstance();
        $this->_debug = false;

        if (!empty($this->_currencyDisplay->_vendorCurrency)) {
            $this->vendorCurrency = $this->_currencyDisplay->_vendorCurrency;
            $this->vendorCurrency_code_3 = $this->_currencyDisplay->_vendorCurrency_code_3;
            $this->vendorCurrency_numeric = $this->_currencyDisplay->_vendorCurrency_numeric;
        }

        $this->setCountryState($countryId, $stateId);
        $this->setVendorId($this->productVendorId);

        $this->rules['Marge'] = [];
        $this->rules['Tax'] = [];
        $this->rules['VatTax'] = [];
        $this->rules['DBTax'] = [];
        $this->rules['DATax'] = [];

        //round only with internal digits
        $this->_roundindig = VmConfig::get('roundindig', false);

        self::$_instance = $this;
    }

    public function setCorrectShopperGroupIds($shoppergroups)
    {
        $this->setShopperGroupIds($shoppergroups);
    }
}
