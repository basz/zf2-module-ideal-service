<?php

namespace IDealService\Model;

use \Zend\Stdlib\AbstractOptions,
    \Zend\Stdlib\Parameters;

class TransactionStatus extends AbstractOptions
{
    protected $transactionId;
    protected $status;
    protected $consumerName;
    protected $consumerAccountNumber;
    protected $consumerCity;

    protected $vendorSpecific;

    function __construct($transactionId)
    {
        $this->setTransactionId($transactionId);
    }

    protected function setTransactionId( $transactionId )
    {
        $this->transactionId = $transactionId;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param status The status to set. See the definitions
     */
    public function setStatus( $status )
    {
        $this->status = $status;
    }

    /**
     * @return Returns the status. See the definitions
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getVendorSpecific() {
        if (!$this->vendorSpecific) {
            $this->vendorSpecific = new Parameters();
        }

        return $this->vendorSpecific;
    }

    /**
     * @return Returns the consumerAccountNumber.
     */
    function getConsumerAccountNumber()
    {
        return $this->consumerAccountNumber;
    }

    /**
     * @param consumerAccountNumber The consumerAccountNumber to set.
     */
    function setConsumerAccountNumber( $consumerAccountNumber )
    {
        $this->consumerAccountNumber = $consumerAccountNumber;
    }

    /**
     * @return Returns the consumerCity.
     */
    function getConsumerCity()
    {
        return $this->consumerCity;
    }

    /**
     * @param consumerCity The consumerCity to set.
     */
    function setConsumerCity( $consumerCity )
    {
        $this->consumerCity = $consumerCity;
    }

    /**
     * @return Returns the consumerName.
     */
    function getConsumerName()
    {
        return $this->consumerName;
    }

    /**
     * @param consumerName The consumerName to set.
     */
    function setConsumerName( $consumerName )
    {
        $this->consumerName = $consumerName;
    }



}
