<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Loader\PluginClassLoader;

/**
 * Plugin class Loader implementation for acquirers adapters.
 */
class OptionsLoader extends PluginClassLoader
{

    /**
     * @var array Pre-aliased adapters
     */
    protected $plugins = array(
        'ing.advanced' => 'IDealService\AcquirerConnector\Adapter\Options\IngAdvanced',
        'targetpay'    => 'IDealService\AcquirerConnector\Adapter\Options\TargetPay',
        'sisow.rest'   => 'IDealService\AcquirerConnector\Adapter\Options\SisowRest',
        'dummy'        => 'IDealService\AcquirerConnector\Adapter\Options\CommonOptions',
    );

}
