<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Websocket Server Integration',
    'description' => 'Run a websocket server within the TYPO3 context with extensible server logic.',
    'category' => 'cli',
    'author' => 'Jonathan Kieling',
    'author_email' => 'jonathan.kieling@werkraum.net',
    'author_company' => 'werkraum Digitalmanufaktur GmbH',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'version' => '0.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.13-10.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];