<?php
/*
 * This file configures dynamic return type support for factory methods in PhpStorm
 */

namespace PHPSTORM_META {
	$STATIC_METHOD_TYPES = [
		\Neos\Flow\ObjectManagement\ObjectManagerInterface::get('') => [
			'' == '@',
		],
		\Neos\Flow\Core\Bootstrap::getEarlyInstance('') => [
			'' == '@',
		]
	];
}
