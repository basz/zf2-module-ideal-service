<?php

/**
 * IdealService Options
 *
 * If you have a ./config/autoload/ directory set up for your project, you can
 * drop this config file in it. (remove the .dist extention to enable it)
 */
/**
 * Configuration service
 */
$serviceOptions = array(
    'useSandbox'    => true,
    'vendorMethod'  => 'dummy',
    'vendorOptions' => array(
        'merchantId'       => '123',
    ),
    'securePath'       => realpath(__DIR__ . '/../../data/ideal-ssl'),
    'proxyUrl'         => null, /* or url string, not yet implemented */
    'timeout'          => 10,
);

/**
 * You do not need to edit below this line
 */
return array(
    'IDealService.serviceOptions' => $serviceOptions
);
