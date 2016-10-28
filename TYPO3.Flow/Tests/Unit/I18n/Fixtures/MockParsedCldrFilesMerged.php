<?php
return [
    'localeDisplayNames' => [
        'localeDisplayPattern' => [
            'localePattern' => '{0} ({1})',
        ],
        'measurementSystemNames' => [
            'measurementSystemName[@type="metric"]' => 'SI-enheter',
            'measurementSystemName[@type="US"]' => 'imperiska enheter',
            'measurementSystemName[@type="US"][@alt="proposed-x1001"]' => 'US-enheter',
        ],
        'languages' => [
            'language[@type="pl"]' => 'polska',
        ],
        'variants' => [
            'variant[@type="1996"]' => '1996 års stavning',
            'variant[@type="1996"][@alt="proposed-x1001"]' => '1996 års stavning',
        ],
    ],
    'dates' => [
        'calendars' => [
            'calendar[@type="gregorian"]' => [
                'months' => [
                    'monthContext[@type="format"]' => [
                        'monthWidth[@type="abbreviated"]' => [
                            'month[@type="1"]' => 'jan',
                            'month[@type="2"]' => 'feb',
                        ],
                        'monthWidth[@type="wide"]' => [
                            'month[@type="1"]' => 'januari',
                            'month[@type="2"]' => 'februari',
                        ],
                    ],
                ],
                'fields' => [
                    'field[@type="dayperiod"]' => [
                        'displayName' => 'dagsperiod',
                    ],
                ],
            ],
            'calendar[@type="chinese"]' => [
                'months' => [
                    'monthContext[@type="format"]' => [
                        'monthWidth[@type="narrow"]' => [
                            'month[@type="3"]' => '3',
                            'month[@type="4"]' => '4',
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
