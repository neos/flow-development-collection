<?php
return [
    'identity' => [
        'version' => '',
        'generation' => '',
        'language[@type="pl"]' => '',
    ],
    'dates' => [
        'calendars' => [
            'calendar[@type="gregorian"]' => [
                'dateFormats' => [
                    'dateFormatLength[@type="full"]' => [
                        'dateFormat' => [
                            'pattern' => 'EEEE, d MMMM y',
                        ],
                    ],
                    'dateFormatLength[@type="long"]' => [
                        'dateFormat' => [
                            'pattern' => 'd MMMM y',
                        ],
                    ],
                    'dateFormatLength[@type="medium"]' => [
                        'dateFormat' => [
                            'pattern' => 'dd-MM-yyyy',
                            'pattern[@alt="proposed-x1001"]' => 'd MMM y',
                        ],
                    ],
                    'dateFormatLength[@type="short"]' => [
                            'alias[@source="locale"][@path="../dateFormatLength[@type=\'medium\']"]' => '',
                    ],
                ],
            ],
            'calendar[@type="buddhist"]' => [
                'dateFormats' => [
                    'dateFormatLength[@type="full"]' => [
                        'alias[@source="locale"][@path="../../../calendar[@type=\'gregorian\']/dateFormats/dateFormatLength[@type=\'full\']"]' => '',
                    ],
                ],
            ],
        ],
    ],
];
