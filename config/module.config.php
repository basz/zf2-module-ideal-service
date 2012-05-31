<?php

return array(
    'IDealService.serviceOptions' => array(
    /* copy IDealService/config/module.idealservice.config.dist.php to config/module.idealservice.config.php */
    ),
    'IDealService.cacheOptions' => array(
        'adapter' => array(
            'name'    => 'filesystem',
            'options' => array(
                'ttl'       => 24 * 3600,
                'cacheDir'  => 'data/cache',
                'namespace' => 'ideal-issuer-list',
            ),
        ),
        'plugins'   => array(
            'serializer',
        ),
    ),
    'IDealService.logWriterOptions' => array(
        'stream' => array('stream' => 'data/logs/ideal.log'),
    )
);