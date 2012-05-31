<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Loader\PluginBroker;

/**
 * Broker for ideal connector adapter instances
 */
class OptionsBroker extends PluginBroker
{
    /**
     * @var string Default plugin loading strategy
     */
    protected $defaultClassLoader = 'IDealService\AcquirerConnector\Adapter\OptionsLoader';

    /**
     * Determine if we have a valid adapter
     *
     * @param  mixed $plugin
     * @return true
     * @throws \RuntimeException
     */
    protected function validatePlugin($plugin)
    {
        if (!$plugin instanceof Options\CommonOptions) {
            throw new \RuntimeException('iDeal vendor options must implement IDealService\AcquirerConnector\Adapter\Options\CommonOptions');
        }
        return true;
    }
}
