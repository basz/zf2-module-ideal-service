<?php

namespace IDealService\AcquirerConnector\Adapter\Options;

use \Zend\Stdlib\Options;

class IngAdvanced extends CommonOptions
{
    protected $subId;

    protected $localPrivateKey;
    protected $localPublicKey;
    protected $remotePublicKey;
    protected $localPrivateKey;

    // ing.advanded
//    'vendorOptions' => array(
//        'merchantId'       => '005089460',
//        'subId'            => '0',
//        'localPrivateKey'  => 'lumasol-private-key.pem',
//        'localPublicKey'   => 'lumasol-public-key.cer',
//        // ing.advance key pass RJWOFv9KEthMNhfhQaCXg528567dhFrydk296rhdFFYy65',
//        'remotePublicKey'  => 'iDEAL.cer', // array or string
//    ),

    public function __construct(array $options = null)
    {
        parent::__construct($options);

        if ($this->subId === null)
            throw new \RuntimeException(sprintf("Required option '%s' ommited", 'subId'));
    }

    public function setSubId($subId)
    {
        $this->subId = $subId;
    }

    public function getSubId()
    {
        return $this->subId;
    }

}