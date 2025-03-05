<?php return array(
    'root' => array(
        'name' => 'unzerdev/shopware6',
        'pretty_version' => '6.3.0',
        'version' => '6.3.0.0',
        'reference' => null,
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'heidelpay/shopware-6' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'unzerdev/php-sdk' => array(
            'pretty_version' => '3.11.0',
            'version' => '3.11.0.0',
            'reference' => '274650b6120b8665c8867627210cd8adab65406b',
            'type' => 'library',
            'install_path' => __DIR__ . '/../unzerdev/php-sdk',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'unzerdev/shopware6' => array(
            'pretty_version' => '6.3.0',
            'version' => '6.3.0.0',
            'reference' => null,
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
