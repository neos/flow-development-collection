<?php
return [
    'localeDisplayNames' => [
        'localeDisplayPattern' => [
            'localePattern' => '{0} ({1})',
        ],
        'measurementSystemNames' => [
            'measurementSystemName[@type="metric"]' => 'Metric',
            'measurementSystemName[@type="US"]' => 'US',
        ],
    ],
    'dates' => [
        'calendars' => [
            'calendar[@type="gregorian"]' => [
                'months' => [
                    'monthContext[@type="format"]' => [
                        'monthWidth[@type="abbreviated"]' => [
                            'alias[@source="locale"][@path="../monthWidth[@type=\'wide\']"]' => '',
                        ],
                        'monthWidth[@type="wide"]' => [
                            'month[@type="1"]' => '1',
                            'month[@type="2"]' => '2',
                        ],
                    ],
                ],
            ],
            'calendar[@type="chinese"]' => [
                'months' => [
                    'monthContext[@type="format"]' => [
                        'monthWidth[@type="narrow"]' => [
                            'alias[@source="locale"][@path="../../monthContext[@type=\'stand-alone\']/monthWidth[@type=\'narrow\']"]' => '',
                        ],
                    ],
                    'monthContext[@type="stand-alone"]' => [
                        'monthWidth[@type="narrow"]' => [
                            'month[@type="3"]' => '3',
                            'month[@type="4"]' => '4',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
