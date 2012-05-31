<?php

namespace IDealService\AcquirerConnector\Adapter\Options;

use \Zend\Stdlib\Options;

class CommonOptions extends Options
{
    protected $merchantId;

    public function __construct(array $options = null)
    {
        parent::__construct($options);

        if ($this->merchantId === null)
            throw new \RuntimeException(sprintf("Required option '%s' ommited", 'merchantId'));
    }

    protected function setMerchantId($merchantId)
    {
        if (!is_string($merchantId)) {
            throw new \InvalidArgumentException(sprintf("Argument '%s' for method '%s' is not of type '%s'", 'merchantId', 'setMerchantId', 'string'));
        }

        $this->merchantId = $merchantId;
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }
}
