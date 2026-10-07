<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension Scanner CLI',
    'description' => 'CLI command to scan TYPO3 extensions for deprecated or removed API usage.',
    'category' => 'misc',
    'author' => 'Netresearch DTT GmbH',
    'author_email' => '',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.4.99',
            'php' => '8.2.0-8.5.99',
            'install' => '12.4.0-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
