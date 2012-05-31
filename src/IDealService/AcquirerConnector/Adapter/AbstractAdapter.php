<?php

namespace IDealService\AcquirerConnector\Adapter;

use IDealService\ServiceOptions,
    Zend\Log\Logger,
    Zend\Log\LoggerAware;

abstract class AbstractAdapter implements AdapterInterface
{

    const LOG_VENDOR_SPECIFIC_OPTION_OMITTED = "Vendor specific options omitted '%s' for this vendor method (%s).";
    const LOG_MESSAGE_VERIFICATION_FAILED = "Message could not be verified to be valid.";

    /**
     * options
     *
     * @var \IDealService\ServiceOptions
     */
    private $serviceOptions;

    /**
     * options for the connector
     *
     * @var IDealService\AcquirerConnector\Adapter\Options\CommonOptions
     */
    private $vendorOptions;

    /**
     * @var \Zend\Log\Logger
     */
    public $logger;

    public function setServiceOptions(ServiceOptions $serviceOptions)
    {
        $this->serviceOptions = $serviceOptions;

        // invalidates vendorOptions
        $this->vendorOptions = null;
    }

    public function getServiceOptions()
    {
        return $this->serviceOptions;
    }

    public function setVendorOptions(Options\CommonOptions $vendorOptions)
    {
        $this->vendorOptions = $vendorOptions;
    }

    public function getVendorOptions()
    {
        if ($this->vendorOptions === null) {
            $this->setVendorOptions(self::getOptionsBroker()->load(
                            $this->getServiceOptions()->getVendorMethod(), $this->getServiceOptions()->getVendorOptions())
            );
        }

        return $this->vendorOptions;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the options broker
     *
     * @return OptionsBroker
     */
    protected static function getOptionsBroker()
    {
        static $broker;

        if ($broker === null) {
            $broker = new OptionsBroker();
            $broker->setRegisterPluginsOnLoad(true);
        }

        return $broker;
    }

    /**
     * Logs a message
     *
     * @param  int $priority
     * @param  mixed $message
     * @param  array|Traversable $extra
     */
    protected function log($priority = Logger::DEBUG, $message, $extra = array())
    {
        if (!($this->logger instanceof Logger))
            return;

        $this->logger->log($priority, $message);
    }

    /**
     *
     * @param type $data (array or string)
     * @param type $url
     * @return string
     * @throws \Exception
     */
    protected function postData($data, $url)
    {
        $this->log(Logger::DEBUG, sprintf("Posting data to: %s\n\n%s\n\n", $url, is_array($data) ? var_export($data, true) : $data));

        try {
            $ch = curl_init($url);

        if ($this->getServiceOptions()->getProxyUrl()) {
            curl_setopt($ch, CURLOPT_PROXY, $this->getServiceOptions()->getProxyUrl());
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getServiceOptions()->getTimeout());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        $response        = curl_exec($ch);
        $response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if ($response === false || $response_status == '0') {
            $errno  = curl_errno($ch);
            $errstr = curl_error($ch);

            throw new \Exception("cURL error: [$errno] $errstr");
        }

        $this->log(Logger::DEBUG, sprintf("Response: \n%s\n\n", $response));

        } catch (\Exception $e) {
            $this->log(Logger::ERR, sprintf("Error: %s", $e->getMessage()));
            return false;
        }
        return $response;
    }

}
