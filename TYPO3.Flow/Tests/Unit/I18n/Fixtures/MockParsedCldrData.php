<?php
return array(
	'identity' => array(
		'version' => '',
		'generation' => '',
		'language[@type="pl"]' => '',
	),
	'dates' => array(
		'calendars' => array(
			'calendar[@type="gregorian"]' => array(
				'dateFormats' => array(
					'dateFormatLength[@type="full"]' => array(
						'dateFormat' => array(
							'pattern' => 'EEEE, d MMMM y',
						),
					),
					'dateFormatLength[@type="long"]' => array(
						'dateFormat' => array(
							'pattern' => 'd MMMM y',
						),
					),
					'dateFormatLength[@type="medium"]' => array(
						'dateFormat' => array(
							'pattern' => 'dd-MM-yyyy',
							'pattern[@alt="proposed-x1001"]' => 'd MMM y',
						),
					),
					'dateFormatLength[@type="short"]' => array(
							'alias[@source="locale"][@path="../dateFormatLength[@type=\'medium\']"]' => '',
					),
				),
			),
	        'calendar[@type="buddhist"]' => array(
				'dateFormats' => array(
					'dateFormatLength[@type="full"]' => array(
						'alias[@source="locale"][@path="../../../calendar[@type=\'gregorian\']/dateFormats/dateFormatLength[@type=\'full\']"]' => '',
					),
				),
			),
		),
	),
);
