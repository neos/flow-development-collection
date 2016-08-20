<?php
/*
 * This file configures dynamic return type support for factory methods in PhpStorm
 */

namespace PHPSTORM_META {
	$STATIC_METHOD_TYPES = [
		\TYPO3\Flow\Object\ObjectManagerInterface::get('') => [
			'' == '@',
		],
		\TYPO3\Flow\Core\Bootstrap::getEarlyInstance('') => [
			'' == '@',
		]
	];
}