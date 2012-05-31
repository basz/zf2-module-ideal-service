<?php

namespace IDealService\Model;

use \Zend\Stdlib\Options,
    \Zend\Stdlib\Parameters;

class Transaction extends Options
{

    protected $reference;
    protected $amount;
    protected $description      = '';
    protected $currency         = 'EUR'; /* only supported value for now */
    protected $language         = 'nl'; /* only supported value for now */
    protected $issuer;
    protected $purchaseId;
    protected $returnUrl;
    protected $expirationPeriod = 600;
    protected $issuerUrl        = null;
    protected $transactionId    = null;
    protected $vendorSpecific;

    function __construct($options = null, $vendorOptions = null)
    {
        if ($options !== null) {
            parent::__construct($options);
        }
    }

    public function getVendorSpecific()
    {
        if (!$this->vendorSpecific) {
            $this->vendorSpecific = new Parameters();
        }

        return $this->vendorSpecific;
    }

    /**
     * Store reference of the transaction.
     *
     * @param type $reference
     */
    protected function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getReference($reference = true)
    {
        return $this->reference;
    }

    protected function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    public function getIssuer($formatted = true)
    {
        return $this->issuer;
    }

    /**
     * The amount in whole cents
     *
     * @param type $amount
     */
    protected function setAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException(sprintf('%s::%s accepts numeric argument', __CLASS__, __METHOD__));
        }

        $amount = min(max(intval($amount), 1), PHP_INT_MAX);

        $this->amount = $amount;
    }

    public function getAmount($formatted = true)
    {
        return $this->amount;
    }

    /**
     * The currency
     *
     * @param string $currency
     */
    protected function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function getCurrency($formatted = true)
    {
        return $this->currency;
    }

    /**
     * Description of the product
     *
     * @param type $description
     */
    protected function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription($formatted = true)
    {
        return $this->description;
    }

    protected function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage($formatted = true)
    {
        return $this->language;
    }

    /**
     * URL on the acceptor’s system to which the client is redirected after making payment in the Internet
     * banking environment.
     *
     * @param type $returnUrl
     */
    protected function setReturnUrl($returnUrl)
    {
        $uri = new \Zend\Uri\Http($returnUrl);
        if (!$uri->isValid())
            throw new \InvalidArgumentException(sprintf("Not a valid url '%s'.", $returnUrl));

        $this->returnUrl = $uri->toString();
    }

    public function getReturnUrl($formatted = true)
    {
        return $this->returnUrl;
    }

    /**
     * Period within which the iDEAL transaction can take place. Maximum value: 1 hour.
     * Minimum value: 1 minute. Suggested value: 10 minutes.
     *
     * @param int $expirationPeriod seconds
     */
    protected function setExpirationPeriod($expirationPeriod)
    {
        if (!is_numeric($expirationPeriod)) {
            throw new \InvalidArgumentException(sprintf('%s::%s accepts numeric argument', __CLASS__, __METHOD__));
        }

        $expirationPeriod = min(max(intval($expirationPeriod), 60), 3600);

        $this->expirationPeriod = $expirationPeriod;
    }

    public function getExpirationPeriod($formatted = true)
    {
        return $formatted ? sprintf("PT%sS", $this->expirationPeriod) : $this->expirationPeriod;
    }

    /**
     * @param type $issuerUrl
     */
    public function setIssuerUrl($issuerUrl)
    {
        $uri = new \Zend\Uri\Http($issuerUrl);
        if (!$uri->isValid())
            throw new \InvalidArgumentException(sprintf("Not a valid url '%s'.", $issuerUrl));

        $this->issuerUrl = $uri->toString();
    }

    /**
     *
     * @return $issuerUrl
     */
    public function getIssuerUrl()
    {
        return $this->issuerUrl;
    }

    /**
     * @param type $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return type
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

}
