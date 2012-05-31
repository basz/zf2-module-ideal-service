<?php

namespace IDealService\AcquirerConnector;

use Zend\Loader\PluginBroker;

/**
 * Broker for ideal connector adapter instances
 */
class AdapterBroker extends PluginBroker
{
    /**
     * @var string Default plugin loading strategy
     */
    protected $defaultClassLoader = 'IDealService\AcquirerConnector\AdapterLoader';

    /**
     * Determine if we have a valid adapter
     *
     * @param  mixed $plugin
     * @return true
     * @throws \RuntimeException
     */
    protected function validatePlugin($plugin)
    {
        if (!$plugin instanceof Adapter\AdapterInterface) {
            throw new \RuntimeException('iDeal acquirer adapters must implement IDealService\AcquirerConnector\Adapter\AdapterInterface');
        }
        return true;
    }
}
