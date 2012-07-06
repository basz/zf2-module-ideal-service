<?php

namespace IDealService;

use Zend\ModuleManager\ModuleManager,
    Zend\EventManager\StaticEventManager;

class Module
{

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $mvcEvent)
    {
        /**
         * \Zend\EventManager\SharedEventManager
         */
        $sharedEvents = $mvcEvent->getApplication()->getEventManager()->getSharedManager();

        $sharedEvents->attach('Zend\Mvc\Controller\AbstractActionController', 'ideal-request-issuers-list', function(\Zend\EventManager\Event $e) {
                    $service = $e->getTarget()->getServiceLocator()->get('IDealService');
                    return $service->requestIssuerList($e);
                }, 100);

        $sharedEvents->attach('Zend\Mvc\Controller\AbstractActionController', 'ideal-request-transaction', function(\Zend\EventManager\Event $e) {
                    $service = $e->getTarget()->getServiceLocator()->get('IDealService');
                    return $service->requestTransaction($e);
                }, 100);

        $sharedEvents->attach('Zend\Mvc\Controller\AbstractActionController', 'ideal-request-transaction-status', function(\Zend\EventManager\Event $e) {
                    $service = $e->getTarget()->getServiceLocator()->get('IDealService');
                    return $service->requestStatus($e);
                }, 100);
    }

    public function getServiceConfiguration()
    {
        return array(
            'aliases' => array(
            ),
            'factories'     => array(
                'IDealService.cache' => function ($sm) {
                    $config = $sm->get('config');
                    return \Zend\Cache\StorageFactory::factory($config['IDealService.cacheOptions']);
                },
                'IDealService.logger' => function ($sm) {
                    $config = $sm->get('config');
                    $logger = new \Zend\Log\Logger();
                    foreach ($config['IDealService.logWriterOptions'] as $name => $options) {
                        $writer = $logger->plugin($name, $options);
                        $logger->addWriter($writer);
                    }
                    return $logger;
                },
                'IDealService' => function ($sm) {
                    $config = $sm->get('config');
                    $service = new \IDealService\Service();
                    $options = new \IDealService\ServiceOptions($config['IDealService.serviceOptions']);
                    $service->setOptions($options);
                    $service->setCache($sm->get('IDealService.cache'));
                    $service->setLogger($sm->get('IDealService.logger'));

                    return $service;
                },
            ),
        );
    }

}
