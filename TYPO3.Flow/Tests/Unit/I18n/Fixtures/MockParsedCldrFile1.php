<?php
return array(
	'localeDisplayNames' => array(
		'localeDisplayPattern' => array(
			'localePattern' => '{0} ({1})',
		),
		'measurementSystemNames' => array(
			'measurementSystemName[@type="metric"]' => 'Metric',
			'measurementSystemName[@type="US"]' => 'US',
		),
	),
	'dates' => array(
		'calendars' => array(
			'calendar[@type="gregorian"]' => array(
				'months' => array(
					'monthContext[@type="format"]' => array(
						'monthWidth[@type="abbreviated"]' => array(
							'alias[@source="locale"][@path="../monthWidth[@type=\'wide\']"]' => '',
						),
						'monthWidth[@type="wide"]' => array(
							'month[@type="1"]' => '1',
							'month[@type="2"]' => '2',
						),
					),
				),
			),
			'calendar[@type="chinese"]' => array(
				'months' => array(
					'monthContext[@type="format"]' => array(
						'monthWidth[@type="narrow"]' => array(
							'alias[@source="locale"][@path="../../monthContext[@type=\'stand-alone\']/monthWidth[@type=\'narrow\']"]' => '',
						),
					),
					'monthContext[@type="stand-alone"]' => array(
						'monthWidth[@type="narrow"]' => array(
							'month[@type="3"]' => '3',
							'month[@type="4"]' => '4',
						),
					),
				),
			),
		),
	),
);
