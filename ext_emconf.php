<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'NR Extension Scanner CLI',
    'description' => 'CLI command to scan TYPO3 extensions for deprecated/removed API usage - by Netresearch',
    'category' => 'misc',
    'author' => 'Netresearch DTT GmbH',
    'author_email' => 'info@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.2.0-8.5.99',
            'install' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
