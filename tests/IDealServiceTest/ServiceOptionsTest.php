<?php

namespace IDealServiceTest;

use IDealService\Service,
    IDealService\ServiceOptions;

class ServiceOptionsTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var IDealService\Service
     */
    protected $service;

    public function setUp()
    {
        $this->options = new ServiceOptions(array('merchantId'=>'123', 'vendorMethod'=>'someMethod'));
    }

    /**
     *
     */
    public function testOmittedRequiredConstructorOptionsThrowsRuntimeException()
    {
        $exceptionRaised = false;

        try {
            $so = new ServiceOptions(array('vendorMethod'=>'someMethod'));
        } catch (\RuntimeException $e) {
            $exceptionRaised = true;
        }

        try {
            $so = new ServiceOptions(array('merchantId'=>'somecode'));
        } catch (\RuntimeException $e) {
            $exceptionRaised = true;
        }

        if (!$exceptionRaised)
            $this->fail('Failed asserting that exception of type "RuntimeException" is thrown.');
    }

    public function testVendorMethodIsProtected()
    {
        $methods = array('setMerchantId', 'setVendorMethod');

        foreach ($methods as $method) {
        if (!method_exists($this->options, $method)) {
                $this->fail(sprintf('Method ServiceOptions::%s does does not exists!', $method));
            } else if (is_callable(array($this->options, $method))) {
                $this->fail(sprintf('Method ServiceOptions::%s is supposed to be private!', $method));
            }
        }
    }

    public function testSecurePath() {
        // defaults to null
        $this->assertNull($this->options->getSecurePath());

        $this->options->setSecurePath(null);

        // still null
        $this->assertNull($this->options->getSecurePath());
    }

    public function testNonExistingAbsolutePathToDirectorySecurePathThrowException()
    {
        $this->setExpectedException('\RuntimeException');

        $this->options->setSecurePath('/non/existing/abolute/path');
    }

    public function testNonExistingRelativePathToDirectorySecurePathThrowException()
    {
        $this->setExpectedException('\RuntimeException');

        $this->options->setSecurePath('non/existing/relative/path');
    }

    public function testExistingAbsolutePathToDirectorySecurePathThrowException()
    {
        $this->options->setSecurePath(__DIR__);

        $this->assertEquals(__DIR__, $this->options->getSecurePath());
    }

    public function testExistingRelativePathToDirectorySecurePathThrowException()
    {
        // relative from Module root.
        $this->options->setSecurePath('.');

        $moduleRoot = realpath(__DIR__ . '/../../');
        $this->assertEquals($moduleRoot, $this->options->getSecurePath());
    }


//    public function testOptionDiscoveryPhingPath()
//    {
//        $so = new ServiceOptions();
//        $this->assertNotNull($so->getPhingPath());
//        $this->assertTrue(is_dir($so->getPhingPath()));
//    }
//
//    public function testPhingRuns()
//    {
//        $so = new ServiceOptions();
//        $po = new PhingOptions();
//        $service = new Service($so, $po);
//        $result = $service->build();
//        $this->assertTrue($result['returnStatus'] == 255);
//    }

}
