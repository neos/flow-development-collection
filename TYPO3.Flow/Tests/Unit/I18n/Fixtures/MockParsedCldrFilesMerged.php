<?php
return array(
	'localeDisplayNames' => array(
		'localeDisplayPattern' => array(
			'localePattern' => '{0} ({1})',
		),
		'measurementSystemNames' => array(
			'measurementSystemName[@type="metric"]' => 'SI-enheter',
			'measurementSystemName[@type="US"]' => 'imperiska enheter',
			'measurementSystemName[@type="US"][@alt="proposed-x1001"]' => 'US-enheter',
		),
		'languages' => array(
			'language[@type="pl"]' => 'polska',
		),
		'variants' => array(
			'variant[@type="1996"]' => '1996 års stavning',
			'variant[@type="1996"][@alt="proposed-x1001"]' => '1996 års stavning',
		),
	),
	'dates' => array(
		'calendars' => array(
			'calendar[@type="gregorian"]' => array(
				'months' => array(
					'monthContext[@type="format"]' => array(
						'monthWidth[@type="abbreviated"]' => array(
							'month[@type="1"]' => 'jan',
							'month[@type="2"]' => 'feb',
						),
						'monthWidth[@type="wide"]' => array(
							'month[@type="1"]' => 'januari',
							'month[@type="2"]' => 'februari',
						),
					),
				),
				'fields' => array(
					'field[@type="dayperiod"]' => array(
						'displayName' => 'dagsperiod',
					),
				),
			),
			'calendar[@type="chinese"]' => array(
				'months' => array(
					'monthContext[@type="format"]' => array(
						'monthWidth[@type="narrow"]' => array(
							'month[@type="3"]' => '3',
							'month[@type="4"]' => '4',
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
