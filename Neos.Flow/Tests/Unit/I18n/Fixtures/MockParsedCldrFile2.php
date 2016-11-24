<?php
return [
    'localeDisplayNames' => [
        'measurementSystemNames' => [
            'measurementSystemName[@type="metric"]' => 'SI-enheter',
            'measurementSystemName[@type="US"]' => 'engelska enheter',
            'measurementSystemName[@type="US"][@alt="proposed-x1001"]' => 'US-enheter',
        ],
        'languages' => [
            'language[@type="pl"]' => 'polska',
        ],
        'variants' => [
            'variant[@type="1996"]' => '1996 års reformerad tysk stavning',
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
            ],
        ],
    ],
];
