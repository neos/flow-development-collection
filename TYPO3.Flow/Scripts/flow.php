<?php

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Bootstrap for the command line
 */

if (PHP_SAPI !== 'cli') {
	echo(sprintf("The TYPO3 Flow command line script or sub process was executed with a '%s' PHP binary. Make sure that you specified a CLI capable PHP binary in your PATH or Flow's Settings.yaml.", PHP_SAPI) . PHP_EOL);
	exit(1);
}

if (isset($argv[1]) && ($argv[1] === 'typo3.flow:core:setfilepermissions' || $argv[1] === 'flow:core:setfilepermissions' || $argv[1] === 'core:setfilepermissions')) {
	if (DIRECTORY_SEPARATOR !== '/') {
		exit('The core:setfilepermissions command is only available on UNIX platforms.' . PHP_EOL);
	}
	array_shift($argv);
	array_shift($argv);
	$returnValue = 0;
	system(__DIR__ . '/setfilepermissions.sh ' . implode($argv, ' '), $returnValue);
	exit($returnValue);
} elseif (isset($argv[1]) && ($argv[1] === 'typo3.flow:core:migrate' || $argv[1] === 'flow:core:migrate' || $argv[1] === 'core:migrate')) {
	array_shift($argv);
	array_shift($argv);
	require(__DIR__ . '/migrate.php');
} else {
	require(__DIR__ . '/../Classes/TYPO3/Flow/Core/Bootstrap.php');

	if (DIRECTORY_SEPARATOR !== '/' && trim(getenv('FLOW_ROOTPATH'), '"\' ') === '') {
		$absoluteRootpath = dirname(realpath(__DIR__ . '/../../../'));
		if (realpath(getcwd()) === $absoluteRootpath) {
			$_SERVER['FLOW_ROOTPATH'] = '.';
		} elseif (strlen(getcwd()) > strlen($absoluteRootpath)) {
			$amountOfPathsToSkipBack = substr_count(getcwd(), DIRECTORY_SEPARATOR) - substr_count($absoluteRootpath, DIRECTORY_SEPARATOR);
			$_SERVER['FLOW_ROOTPATH'] = implode('/', array_fill(0, $amountOfPathsToSkipBack, '..'));
		} else {
			$_SERVER['FLOW_ROOTPATH'] = substr($absoluteRootpath, strlen(getcwd()) + 1);
		}
	} else {
		$_SERVER['FLOW_ROOTPATH'] = trim(getenv('FLOW_ROOTPATH'), '"\' ') ?: dirname($_SERVER['PHP_SELF']);
	}

	$context = trim(getenv('FLOW_CONTEXT'), '"\' ') ?: 'Development';

	$bootstrap = new \TYPO3\Flow\Core\Bootstrap($context);
	$bootstrap->run();
}

?>