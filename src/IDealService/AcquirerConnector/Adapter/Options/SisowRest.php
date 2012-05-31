<?php

namespace IDealService\AcquirerConnector\Adapter\Options;

use \Zend\Stdlib\Options;

class SisowRest extends CommonOptions
{
    protected $merchantKey;
    protected $shopId;
    protected $returnUrl;

    public function __construct(array $options = null)
    {
        parent::__construct($options);

        if ($this->merchantkey === null)
            throw new \RuntimeException(sprintf("Required option '%s' ommited", 'merchantKey'));
    }

    public function setMerchantKey($merchantKey)
    {
        $this->merchantKey = $merchantKey;
    }

    public function getMerchantKey()
    {
        return $this->merchantKey;
    }

    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    }

    public function getShopId()
    {
        return $this->shopId;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }
}