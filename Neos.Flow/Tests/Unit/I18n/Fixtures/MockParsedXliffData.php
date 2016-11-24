<?php
return [
    'sourceLocale' => new \Neos\Flow\I18n\Locale('en_US'),
    'translationUnits' => [
        'key1' => [
            0 => [
                'source' => 'Source string',
                'target' => 'Übersetzte Zeichenkette',
            ],
        ],
        'key2' => [
            0 => [
                'source' => 'Source singular',
                'target' => 'Übersetzte Einzahl',
            ],
            1 => [
                'source' => 'Source plural',
                'target' => 'Übersetzte Mehrzahl 1',
            ],
            2 => [
                'source' => 'Source plural',
                'target' => 'Übersetzte Mehrzahl 2',
            ],
        ],
        'key3' => [
            0 => [
                'source' => 'No target',
                'target' => '',
            ],
        ],
    ]
];
