<?php

namespace IDealServiceTest;

use IDealService\Service,
    IDealService\ServiceOptions;


class ServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var IDealService\Service
     */
    protected $service;

    public function setUp()
    {
        $this->service = new Service(new ServiceOptions(array('merchantId'=>'321', 'vendorMethod'=>'dummy')));
    }

    public function tearDown()
    {
       // $this->service->reset();
    }

    public function testAcquirerConnectorLoadable() {
        $ac = $this->service->getAcquirerConnector();

        $this->assertInstanceOf('IDealService\AcquirerConnector\Adapter\AdapterInterface', $ac);

        $this->assertInstanceOf('IDealService\AcquirerConnector\Adapter\Dummy', $ac);
    }

    public function testAcquirerConnectorSameInstance() {
        $ac1 = $this->service->getAcquirerConnector();
        $ac2 = $this->service->getAcquirerConnector();

        $this->assertEquals($ac1, $ac2);
    }

    public function testAcquirerConnectorDifferentInstance() {
        $ac1 = $this->service->getAcquirerConnector();

        $service2 = new Service(new ServiceOptions(array('merchantId'=>'456', 'vendorMethod'=>'dummy')));
        $ac2 = $service2->getAcquirerConnector();

        $this->assertNotEquals($ac1, $ac2, 'Failed asserting that an instance from is not equal to b.');
    }

    public function testNonExistantVendorMethodThrowsException()
    {
        $exceptionRaised = false;

        try {
            $this->service->setOptions(new ServiceOptions(array('merchantId'=>'321', 'vendorMethod'=>'non-existant-adapter')));

            $ac = $this->service->getAcquirerConnector();
        } catch (\Zend\Loader\Exception\RuntimeException $e) {
            $exceptionRaised = true;
        }

        if (!$exceptionRaised)
            $this->fail('Failed asserting that exception of type "\Zend\Loader\Exception\RuntimeException" is thrown.');
    }

}
