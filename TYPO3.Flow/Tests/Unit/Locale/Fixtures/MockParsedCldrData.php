<?php
return array(
	'identity' => array(
		'version' => array(
			'number="foo"' => '',
		),
		'generation' => array(
			'date="foo"' => '',
		),
		'language' => array(
			'type="pl"' => '',
		),
	),
	'dates' => array(
		'calendars' => array(
			'calendar' => array(
				'type="gregorian"' => array(
					'dateFormats' => array(
						'dateFormatLength' => array(
							'type="full"' => array(
								'dateFormat' => array(
									'pattern' => 'EEEE, d MMMM y',
								),
							),
							'type="long"' => array(
								'dateFormat' => array(
									'pattern' => 'd MMMM y',
								),
							),
							'type="medium"' => array(
								'dateFormat' => array(
									'pattern' => array(
										'#noattributes' => 'dd-MM-yyyy',
										'alt="proposed-x1001" draft="unconfirmed"' => 'd MMM y',
									),
								),
							),
							'type="short"' => array(
								'alias' => array(
									'source="locale" path="../dateFormatLength[@type=\'medium\']"' => '',
								),
							),
						),
					),
				),
				'type="buddhist"' => array(
					'dateFormats' => array(
						'dateFormatLength' => array(
							'type="full"' => array(
								'alias' => array(
									'source="locale" path="../../../calendar[@type=\'gregorian\']/dateFormats/dateFormatLength[@type=\'full\']"' => '',
								),
							),
						),
					),
				),
			),
		),
	),
);

?>
