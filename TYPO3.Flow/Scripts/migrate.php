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

use TYPO3\Flow\Core\Migrations\AbstractMigration;
use TYPO3\Flow\Core\Migrations\Manager;

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
define('MAXIMUM_LINE_LENGTH', 84);


if(flagIsSet('packages-path')) {
	define('FLOW_PATH_PACKAGES', getFlagValue('packages-path'));
} else {
	define('FLOW_PATH_PACKAGES', FLOW_PATH_ROOT . 'Packages/');
}

if (\TYPO3\Flow\Core\Migrations\Git::isGitAvailable() === FALSE) {
	outputLine('No executable git binary found, exiting.');
	exit(255);
}

$migrationsManager = new Manager();

$packageKey = getFlagValue('package-key');
$versionNumber = flagIsSet('version') ? preg_replace('/[^0-9]/', '', getFlagValue('version')) : NULL;
// see https://jira.typo3.org/browse/FLOW-110
if (strlen($versionNumber) === 12) {
	$versionNumber .= '00';
}
$verbose = flagIsSet('verbose');

if (flagIsSet('status')) {
	outputLine('Fetching migration status...');
	try {
		$status = $migrationsManager->getStatus($packageKey, $versionNumber);
	} catch (\Exception $exception) {
		outputLine('EXCEPTION: %s', array($exception->getMessage()));
		exit(255);
	}

	outputHeadline('Migration status', 1);
	foreach ($status as $packageKey => $migrationsStatus) {
		outputHeadline('for package "%s"', 2, array($packageKey));
		foreach ($migrationsStatus as $migrationVersionNumber => $migrationStatus) {
			if ($versionNumber !== NULL && $versionNumber != $migrationVersionNumber) {
				continue;
			}
			/** @var AbstractMigration $migration */
			$migration = $migrationStatus['migration'];
			$status = $migrationStatus['state'] === Manager::STATE_MIGRATED ? 'migrated' : 'not migrated/skipped';

			$migrationTitle = sprintf('%s (%s)', formatVersion($migrationVersionNumber), $migration->getIdentifier());
			outputLine('>> %s %s', array(str_pad($migrationTitle, MAXIMUM_LINE_LENGTH - 24), $status), MAXIMUM_LINE_LENGTH - 16);
			if ($verbose) {
				$description = $migration->getDescription();
				if ($description !== NULL) {
					outputLine('     %s', array($migration->getDescription()), 5);
					outputLine();
				}
			}
		}
	}
	exit(0);
}

$migrationsManager->on(Manager::EVENT_MIGRATION_DONE, function(AbstractMigration $migration) use ($verbose) {

	if ($verbose || $migration->hasWarnings()) {
		outputMigrationHeadline($migration);
	}

	if ($verbose && $migration->hasNotes()) {
		outputHeadline('Notes', 2);
		outputBulletList($migration->getNotes());
		outputSeparator();
	}
	if ($migration->hasWarnings()) {
		outputHeadline('Warnings', 2);
		outputBulletList($migration->getWarnings());
		outputSeparator();
	}
	if ($verbose) {
		outputLine('Done with %s', array($migration->getIdentifier()));
		outputLine();
	}
});

if ($verbose) {
	$migrationsManager->on(Manager::EVENT_MIGRATION_SKIPPED, function (AbstractMigration $migration, $packageKey, $reason) {
		outputMigrationHeadline($migration);
		outputLine('  Skipping %s: %s', array($packageKey, $reason));
		outputLine();
	});
}

$migrationsManager->on(Manager::EVENT_MIGRATION_EXECUTED, function(AbstractMigration $migration, $packageKey, $migrationResult) {
	outputMigrationHeadline($migration);
	outputLine('  Migrated %s:', array($packageKey));
	outputLine();
	outputLine($migrationResult);
});

$lastMigration = NULL;
function outputMigrationHeadline(AbstractMigration $migration) {
	global $lastMigration;
	if ($migration !== $lastMigration) {
		outputHeadline('Migration %s (%s)', 1, array($migration->getIdentifier(), formatVersion($migration->getVersionNumber())));
		$description = $migration->getDescription();
		if ($description !== NULL) {
			outputLine($description);
			outputLine();
		}
		$lastMigration = $migration;
	}
}

outputLine('Migrating...');
try {
	$migrationsManager->migrate($packageKey, $versionNumber);
} catch (\Exception $exception) {
	outputLine('EXCEPTION: %s', array($exception->getMessage()));
	exit(255);
}
outputLine('Done.');

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
	if (!flagIsSet($flag)) {
		return NULL;
	}
	$index = array_search('--' . $flag, $GLOBALS['argv']);
	return $GLOBALS['argv'][$index +1];
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

/**
 * @param string $text Text to output
 * @param array $arguments Optional arguments to use for sprintf
 * @param integer $indention
 * @return void
 */
function outputLine($text = '', array $arguments = array(), $indention = 0) {
	if ($arguments !== array()) {
		$text = vsprintf($text, $arguments);
	}
	if ($indention > 0) {
		$wrappedLines = explode(PHP_EOL, wordwrap($text, MAXIMUM_LINE_LENGTH, PHP_EOL, TRUE));
		echo implode(PHP_EOL . str_repeat(' ', $indention), $wrappedLines);
	} else {
		echo wordwrap($text, MAXIMUM_LINE_LENGTH, PHP_EOL, TRUE);
	}
	echo PHP_EOL;
}

/**
 * @param string $headline
 * @param integer $level headline level (1-4)
 * @param array $arguments Optional arguments to use for sprintf
 * @return void
 */
function outputHeadline($headline, $level = 1, array $arguments = array()) {
	outputLine();
	$separatorCharacters = array('=', '-', '=', '-');
	$separatorCharacter = isset($separatorCharacters[$level - 1]) ? $separatorCharacters[$level - 1] : $separatorCharacters[0];
	if ($level === 1) {
		outputSeparator($separatorCharacter);
	}
	outputLine($headline, $arguments);
	outputSeparator($separatorCharacter);
}

/**
 * @param string $character
 * @return void
 */
function outputSeparator($character = '-') {
	echo str_repeat($character, MAXIMUM_LINE_LENGTH);
	echo PHP_EOL;
}

/**
 * @param array $items
 * @return void
 */
function outputBulletList(array $items) {
	foreach ($items as $item) {
		outputLine('  * ' . $item, array(), 4);
	}
}