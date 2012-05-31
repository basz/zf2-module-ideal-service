<?php

namespace IDealService\AcquirerConnector;

use Zend\Loader\PluginClassLoader;

/**
 * Plugin class Loader implementation for acquirers adapters.
 */
class AdapterLoader extends PluginClassLoader
{

    /**
     * @var array Pre-aliased adapters
     */
    protected $plugins = array(
        'ing.advanced' => 'IDealService\AcquirerConnector\Adapter\IngAdvanced',
        'targetpay'    => 'IDealService\AcquirerConnector\Adapter\TargetPay',
        'sisow.rest'   => 'IDealService\AcquirerConnector\Adapter\SisowRest',
        'dummy'        => 'IDealService\AcquirerConnector\Adapter\Dummy',
    );

}
