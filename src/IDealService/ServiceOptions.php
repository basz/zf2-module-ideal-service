<?php

namespace IDealService;

use \Zend\Stdlib\AbstractOptions,
    \Zend\Stdlib\Parameters;

class ServiceOptions extends AbstractOptions
{

    /**
     * Many Acquirers support sandbox modes for testing purposes
     *
     * @var boolean
     */
    protected $useSandbox;

    /**
     *
     * @var string
     */
    protected $securePath;
    protected $merchantId; /* vendor specific */

    protected $subId; /* vendor specific */
    protected $localPrivateKey; /* PRIVATEKEY */ /* vendor specific */
    protected $localPublicKey; /* PRIVATECERT */ /* vendor specific */
    protected $localPrivateKeyPass; /* PRIVATEKEYPASS */ /* vendor specific */
    protected $remotePublicKey; /* CERTIFICATE0 */ /* vendor specific */

    protected $proxyUrl;
    protected $timeout;

    protected $expirationPeriod;
    protected $returnUrl;

    protected $vendorMethod;
    protected $vendorOptions;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->vendorMethod === null)
            throw new \RuntimeException(sprintf("Required option '%s' ommited", 'vendorMethod'));
    }



    protected function setVendorMethod($vendorMethod)
    {
        if (!is_string($vendorMethod)) {
            throw new \InvalidArgumentException(sprintf("Argument '%s' for method '%s' is not of type '%s'", 'vendorMethod', 'setVendorMethod', 'string'));
        }

        $this->vendorMethod = $vendorMethod;
    }

    public function getVendorMethod()
    {
        return $this->vendorMethod;
    }

    protected function setVendorOptions($vendorOptions) {
        $this->vendorOptions = $vendorOptions;
    }

    public function getVendorOptions() {
        return $this->vendorOptions;
    }


    /**
     * Many Acquirers provide a sandbox for testing purposes.
     *
     * @var boolean $sandbox
     */
    public function setUseSandbox($useSandbox)
    {
        $this->useSandbox = filter_var($useSandbox, FILTER_VALIDATE_BOOLEAN);
    }

    public function getUseSandbox()
    {
        return (bool) $this->useSandbox;
    }

    /**
     * Sets path to a safe location were certificates could be stored
     *
     * Can be a absolute path or a relative path from module root
     *
     * @param string $path | null
     * @throws \RuntimeException When path can't be found
     */
    public function setSecurePath($path)
    {
        if (!is_null($path) && !is_string($path)) {
            throw new \InvalidArgumentException(sprintf("Argument '%s' for method '%s' is not of type '%s'", 'path', 'setSecurePath', 'string|null'));
        }

        if ($path === null) {
            $this->securePath = null;
        } else {
            $path = (string) $path;

            if (substr($path, 0, 1) != DIRECTORY_SEPARATOR) {
                $path = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . $path;
            }

            if (!is_dir($path)) {
                throw new \RuntimeException(sprintf("Path '%s' is not an existing directory!", $path));
            }

            $this->securePath = realpath($path);
        }
    }
    /**
     * Gets path to a safe location were certificates could be stored
     *
     * @return string | null
     */
    public function getSecurePath()
    {
        return $this->securePath;
    }


//    public function getLocalPrivateKey()
//    {
//        return $this->localPrivateKey;
//    }
//
//    public function setLocalPrivateKey($localPrivateKey)
//    {
//        $this->localPrivateKey = $localPrivateKey;
//    }
//
//    public function getLocalPublicKey()
//    {
//        return $this->localPublicKey;
//    }
//
//    public function setLocalPublicKey($localPublicKey)
//    {
//        $this->localPublicKey = $localPublicKey;
//    }
//
//    public function getLocalPrivateKeyPass()
//    {
//        return $this->localPrivateKeyPass;
//    }
//
//    public function setLocalPrivateKeyPass($localPrivateKeyPass)
//    {
//        $this->localPrivateKeyPass = $localPrivateKeyPass;
//    }
//
//    public function getRemotePublicKey()
//    {
//        return $this->remotePublicKey;
//    }
//
//    public function setRemotePublicKey($remotePublicKey = array())
//    {
//        if (!is_array($remotePublicKey))
//            $remotePublicKey = array($remotePublicKey);
//
//        $this->remotePublicKey = $remotePublicKey;
//    }

    public function getProxyUrl()
    {
        return $this->proxyUrl;
    }

    public function setProxyUrl($proxyUrl)
    {
        if (is_string($proxyUrl)) {
            $this->proxyUrl = $proxyUrl;
        } else {
            $this->proxyUrl = null;
        }
    }

//    public function getProxyAcquireUrl()
//    {
//        return $this->proxyAcquireUrl;
//    }
//
//    public function setProxyAcquireUrl($proxyAcquireUrl)
//    {
//        $this->proxyAcquireUrl = $proxyAcquireUrl;
//    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

 }