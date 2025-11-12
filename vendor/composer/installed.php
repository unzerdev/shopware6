<?php return array(
    'root' => array(
        'name' => 'unzerdev/shopware6',
        'pretty_version' => '6.99.2',
        'version' => '6.99.2.0',
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
            'pretty_version' => '3.13.1',
            'version' => '3.13.1.0',
            'reference' => '0a26d70b33185d52e49b06cb500a6e01bb6f1087',
            'type' => 'library',
            'install_path' => __DIR__ . '/../unzerdev/php-sdk',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'unzerdev/shopware6' => array(
            'pretty_version' => '6.99.2',
            'version' => '6.99.2.0',
            'reference' => null,
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
