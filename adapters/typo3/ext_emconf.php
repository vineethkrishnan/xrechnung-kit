<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'xrechnung-kit',
    'description' => 'EN 16931 / XRechnung 3.0 generation and validation for TYPO3. Wraps vineethkrishnan/xrechnung-kit-core with TYPO3 DI and scheduler integration.',
    'category' => 'plugin',
    'state' => 'alpha',
    'author' => 'Vineeth N K',
    'author_email' => 'me@vineethnk.in',
    'author_company' => '',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.4.99',
            'typo3' => '11.5.0-13.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Vineethkrishnan\\XrechnungKitTypo3\\' => 'Classes/',
        ],
    ],
];
