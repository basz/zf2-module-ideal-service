<?php

namespace IDealService;

use
    IDealService\AcquirerConnector\Adapter\AdapterInterface as AcquirerConnectorAdapterInterface,
    IDealService\AcquirerConnector\AdapterBroker,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Error,
    Zend\Cache\Storage\Adapter\AdapterInterface as CacheAdapterInterface,
    Zend\EventManager\Event,
    Zend\ServiceManager\ServiceManager,
    Zend\ServiceManager\ServiceManagerAwareInterface,
    Zend\Log\Logger;
;

class Service implements ServiceManagerAwareInterface
{

    protected $services;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->services = $serviceManager;
    }
    /**
     * @var ServiceOptions
     */
    protected $options;

    /**
     * @var Zend\Cache\Storage\Adapter
     */
    protected $cache;

    /**
     * @var Zend\Log\Loggable
     */
    protected $logger;

    /**
     * The used EventManager if any
     *
     * @var null|EventManager
     */
    protected $events;

    /**
     *
     * @var type
     */
    protected $acquirerConnectorAdapter;


    public function setCache(CacheAdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setOptions(ServiceOptions $options)
    {
        $this->options = $options;

        // options changed?
        $this->acquirerConnectorAdapter = null;
    }

    public function getOptions()
    {
        return $this->options;
    }


    public function getAcquirerConnector()
    {
        if ($this->acquirerConnectorAdapter === null) {
            $this->acquirerConnectorAdapter = self::getAdapterBroker()->load($this->getOptions()->getVendorMethod());
            $this->acquirerConnectorAdapter->setServiceOptions($this->getOptions());

            if ($this->getLogger() instanceof Logger) {
                $this->acquirerConnectorAdapter->setLogger($this->getLogger());
            }
        }

        return $this->acquirerConnectorAdapter;
    }

    /**
     * Get the adapter broker
     *
     * @return Broker
     */
    public static function getAdapterBroker()
    {
        static $broker;
        if ($broker === null) {
            $broker = new AdapterBroker();

            $broker->setRegisterPluginsOnLoad(true);
        }
        return $broker;
    }

    /**
     * @param \Zend\EventManager\Event $e
     * @return IssuersCollection | false
     */
    public function requestIssuerList(Event $e)
    {
        if ($this->getCache()) {
            $cacheKey     = md5(var_export($this->options, true));
            if (!($response = $this->getCache()->getItem($cacheKey))) {
                $response = $this->getAcquirerConnector()->issueDirectoryRequest();

                // cache only when succes
                if ($response instanceof IssuersCollection) {
                    $this->getCache()->setItem($cacheKey, $response);
                }
            } else {
                $this->getCache()->touchItem($cacheKey);
            }
        } else {
            $response = $this->getAcquirerConnector()->issueDirectoryRequest();
        }

        $e->stopPropagation(true);

        if ($response instanceof Error) {
            $this->getLogger()->crit($response->getMessage(), array($response->getCode(), $response->getConsumerMessage()));
            return false;
        }

        return $response;
    }

    /**
     *
     * @param \Zend\EventManager\Event $e
     * @return \IDeal\TransactionResponse | false
     */
    public function requestTransaction(Event $e)
    {
        /**
         * @type \IDealService\model\Transaction
         */
        $transaction = $e->getParam('transaction');

        $e->stopPropagation(true);

        $response = $this->getAcquirerConnector()->issueTransactionRequest($transaction);

        if ($response instanceof Error) {
            $this->getLogger()->crit($response->getMessage(), array($response->getCode(), $response->getConsumerMessage()));

            return false;
        }

        return $response;
    }

    /**
     *
     * @param \Zend\EventManager\Event $e
     * @return \IDeal\StatusResponse | false
     */
    public function requestStatus(Event $e)
    {
        /**
         * @type \IDealService\model\TransactionStatus
         */
        $transactionStatus = $e->getParam(0);

        $e->stopPropagation(true);

        $response = $this->getAcquirerConnector()->issueStatusRequest($transactionStatus);

        if ($response instanceof Error) {
            $this->getLogger()->crit($response->getMessage(), array($response->getCode(), $response->getConsumerMessage()));

            return false;
        }

        return $response;
    }
}