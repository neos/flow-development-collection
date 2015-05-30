<?php

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

// if installed through composer, use it's autoloading
if (file_exists(__DIR__ . '/../../../Libraries/autoload.php')) {
	require (__DIR__ . '/../../../Libraries/autoload.php');
} else {
	require (__DIR__ . '/../Classes/TYPO3/Flow/Exception.php');
	require (__DIR__ . '/../Classes/TYPO3/Flow/Utility/Exception.php');
	require (__DIR__ . '/../Classes/TYPO3/Flow/Utility/Files.php');
	require (__DIR__ . '/../Classes/TYPO3/Flow/Configuration/ConfigurationManager.php');
	require (__DIR__ . '/../Classes/TYPO3/Flow/Configuration/Source/YamlSource.php');
}

require(__DIR__ . '/Migrations/AbstractMigration.php');
require(__DIR__ . '/Migrations/Manager.php');
require(__DIR__ . '/Migrations/Tools.php');
require(__DIR__ . '/Migrations/Git.php');

define('FLOW_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

if (FLOW_SAPITYPE !== 'CLI') exit ('The migrate tool can only be run from the command line (with a CLI PHP binary).');

define('FLOW_PATH_FLOW', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../') . '/'))));
define('FLOW_PATH_ROOT', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../../../') . '/'))));
define('FLOW_PATH_WEB', FLOW_PATH_ROOT . 'Web/');
define('FLOW_PATH_CONFIGURATION', FLOW_PATH_ROOT . 'Configuration/');
define('FLOW_PATH_DATA', FLOW_PATH_ROOT . 'Data/');

if(flagIsSet('packages-path')) {
	define('FLOW_PATH_PACKAGES', getFlagValue('packages-path'));
} else {
	define('FLOW_PATH_PACKAGES', FLOW_PATH_ROOT . 'Packages/');
}

if (\TYPO3\Flow\Core\Migrations\Git::isGitAvailable() === FALSE) {
	echo 'No executable git binary found, exiting.';
	exit(255);
}

$migrationsManager = new \TYPO3\Flow\Core\Migrations\Manager();

if (flagIsSet('status')) {
	$status = $migrationsManager->getStatus();

	$output = PHP_EOL . ' == Migration status' . PHP_EOL;

	foreach ($status as $packageKey => $migrations) {
		$output .= PHP_EOL . ' ==  for ' . $packageKey . PHP_EOL;
		foreach ($migrations as $versionNumber => $migration) {
			$status = $migration['state'] === \TYPO3\Flow\Core\Migrations\Manager::STATE_MIGRATED ? 'migrated' : 'not migrated';
			$output .= '    >> ' . formatVersion($versionNumber) . ' (' . $migration['source'] . ')' . str_repeat(' ', 30 - strlen($status)) . $status . PHP_EOL;
		}
	}
	echo $output;
	exit(0);
}

$migrationsManager->migrate(getFlagValue('package-key'));

/**
 * Check if the given flag is in $GLOBALS['argv'].
 *
 * @param string $flag
 * @return boolean
 */
function flagIsSet($flag) {
	return in_array('--' . $flag, $GLOBALS['argv']);
}

/**
 * Get the value of the given flag from $GLOBALS['argv'].
 *
 * @param $flag
 * @return mixed
 */
function getFlagValue($flag) {
	if (flagIsSet($flag)) {
		$index = array_search('--' . $flag, $GLOBALS['argv']);
		return $GLOBALS['argv'][$index +1];
	} else {
		return NULL;
	}
}

/**
 * Returns a "timestamp" as a formatted date
 *
 * @param string $timestamp (e.g. 201205021529)
 * @return string The formatted timestamp
 */
function formatVersion($timestamp) {
	return sprintf('%s-%s-%s %s:%s',
		substr($timestamp, 0, 4),
		substr($timestamp, 4, 2),
		substr($timestamp, 6, 2),
		substr($timestamp, 8, 2),
		substr($timestamp, 10, 2)
	);
}
