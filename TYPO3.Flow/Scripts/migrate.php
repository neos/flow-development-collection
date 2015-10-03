<?php

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\Migrations\AbstractMigration;
use TYPO3\Flow\Core\Migrations\Manager;

// if installed through composer, use it's autoloading
if (file_exists(__DIR__ . '/../../../Libraries/autoload.php')) {
    require(__DIR__ . '/../../../Libraries/autoload.php');
} else {
    require(__DIR__ . '/../Classes/TYPO3/Flow/Exception.php');
    require(__DIR__ . '/../Classes/TYPO3/Flow/Utility/Exception.php');
    require(__DIR__ . '/../Classes/TYPO3/Flow/Utility/Files.php');
    require(__DIR__ . '/../Classes/TYPO3/Flow/Configuration/ConfigurationManager.php');
    require(__DIR__ . '/../Classes/TYPO3/Flow/Configuration/Source/YamlSource.php');
}

require(__DIR__ . '/Migrations/AbstractMigration.php');
require(__DIR__ . '/Migrations/Manager.php');
require(__DIR__ . '/Migrations/Tools.php');
require(__DIR__ . '/Migrations/Git.php');

define('FLOW_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

if (FLOW_SAPITYPE !== 'CLI') {
    exit('The migrate tool can only be run from the command line (with a CLI PHP binary).');
}

define('FLOW_PATH_FLOW', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../') . '/'))));
define('FLOW_PATH_ROOT', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../../../') . '/'))));
define('FLOW_PATH_WEB', FLOW_PATH_ROOT . 'Web/');
define('FLOW_PATH_CONFIGURATION', FLOW_PATH_ROOT . 'Configuration/');
define('FLOW_PATH_DATA', FLOW_PATH_ROOT . 'Data/');
define('MAXIMUM_LINE_LENGTH', 84);
define('STYLE_DEFAULT', 0);
define('STYLE_ERROR', 31);
define('STYLE_WARNING', 33);
define('STYLE_SUCCESS', 32);


if (flagIsSet('packages-path')) {
    define('FLOW_PATH_PACKAGES', getFlagValue('packages-path'));
} else {
    define('FLOW_PATH_PACKAGES', FLOW_PATH_ROOT . 'Packages/');
}

if (\TYPO3\Flow\Core\Migrations\Git::isGitAvailable() === false) {
    outputLine('No executable git binary found, exiting.');
    exit(255);
}

$migrationsManager = new Manager();

$packageKey = getFlagValue('package-key');
$versionNumber = null;
if (flagIsSet('version')) {
    if (preg_match('/[0-9]{12,14}/', getFlagValue('version'), $matches) !== 1) {
        outputLine('EXCEPTION: invalid version "%s" specified, please provide the 12 or 14 digit timestamp of the version you want to target.', array(getFlagValue('version')), 0, STYLE_ERROR);
        exit(255);
    }
    $versionNumber = $matches[0];
    // see https://jira.neos.io/browse/FLOW-110
    if (strlen($versionNumber) === 12) {
        $versionNumber .= '00';
    }
}
$verbose = flagIsSet('verbose');

if (flagIsSet('status')) {
    outputLine('Fetching migration status...');
    try {
        $status = $migrationsManager->getStatus($packageKey, $versionNumber);
    } catch (\Exception $exception) {
        outputLine('EXCEPTION: %s', array($exception->getMessage()), 0, STYLE_ERROR);
        exit(255);
    }

    outputHeadline('Migration status', 1);
    foreach ($status as $packageKey => $migrationsStatus) {
        outputHeadline('for package "%s"', 2, array($packageKey));
        foreach ($migrationsStatus as $migrationVersionNumber => $migrationStatus) {
            if ($versionNumber !== null && $versionNumber != $migrationVersionNumber) {
                continue;
            }
            /** @var AbstractMigration $migration */
            $migration = $migrationStatus['migration'];
            $status = $migrationStatus['state'] === Manager::STATE_MIGRATED ? 'migrated' : 'not migrated/skipped';

            $migrationTitle = sprintf('%s (%s)', formatVersion($migrationVersionNumber), $migration->getIdentifier());
            outputLine('>> %s %s', array(str_pad($migrationTitle, MAXIMUM_LINE_LENGTH - 24), $status), MAXIMUM_LINE_LENGTH - 16);
            if ($verbose) {
                $description = $migration->getDescription();
                if ($description !== null) {
                    outputLine('     %s', array($migration->getDescription()), 5);
                    outputLine();
                }
            }
        }
    }
    exit(0);
}

$migrationsManager->on(Manager::EVENT_MIGRATION_DONE, function (AbstractMigration $migration) use ($verbose) {

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
        outputBulletList($migration->getWarnings(), STYLE_WARNING);
        outputSeparator();
    }
    if ($verbose) {
        outputLine('Done with %s', array($migration->getIdentifier()));
        outputLine();
    }
});

$migrationsManager->on(Manager::EVENT_MIGRATION_SKIPPED, function (AbstractMigration $migration, $reason) use ($migrationsManager) {
    outputMigrationHeadline($migration);
    outputLine('  Skipping %s: %s', array($migrationsManager->getCurrentPackageKey(), $reason), 0, STYLE_WARNING);
    outputLine();
});

if ($verbose) {
    $migrationsManager->on(Manager::EVENT_MIGRATION_ALREADY_APPLIED, function (AbstractMigration $migration, $reason) use ($migrationsManager) {
        outputMigrationHeadline($migration);
        outputLine('  Skipping %s: %s', array($migrationsManager->getCurrentPackageKey(), $reason));
        outputLine();
    });
}

$migrationsManager->on(Manager::EVENT_MIGRATION_EXECUTED, function (AbstractMigration $migration) use ($migrationsManager) {
    outputMigrationHeadline($migration);
    outputLine('  Migrated %s', array($migrationsManager->getCurrentPackageKey()), 0, STYLE_SUCCESS);
});

if ($verbose) {
    $migrationsManager->on(Manager::EVENT_MIGRATION_COMMIT_SKIPPED, function (AbstractMigration $migration, $reason) {
        outputLine('  Skipping commit: %s', array($reason), 0, STYLE_WARNING);
    });
}

$migrationsManager->on(Manager::EVENT_MIGRATION_COMMITTED, function (AbstractMigration $migration, $commitResult) {
    outputMigrationHeadline($migration);
    outputLine();
    outputLine($commitResult);
});

$migrationsManager->on(Manager::EVENT_MIGRATION_LOG_IMPORTED, function (AbstractMigration $migration, $importResult) {
    outputMigrationHeadline($migration);
    outputLine('  Import migration log from Git history', array(), 0, STYLE_SUCCESS);
    outputLine('  Commit result:');
    outputLine($importResult);
});

$lastMigration = null;
function outputMigrationHeadline(AbstractMigration $migration)
{
    global $lastMigration;
    if ($migration !== $lastMigration) {
        outputHeadline('Migration %s (%s)', 1, array($migration->getIdentifier(), formatVersion($migration->getVersionNumber())));
        $description = $migration->getDescription();
        if ($description !== null) {
            outputLine($description);
            outputLine();
        }
        $lastMigration = $migration;
    }
}

outputLine('Migrating...');
try {
    $migrationsManager->migrate($packageKey, $versionNumber, flagIsSet('force'));
} catch (\Exception $exception) {
    outputLine('EXCEPTION: %s', array($exception->getMessage()), 0, STYLE_ERROR);
    exit(255);
}
outputLine('Done.');

/**
 * Check if the given flag is in $GLOBALS['argv'].
 *
 * @param string $flag
 * @return boolean
 */
function flagIsSet($flag)
{
    return in_array('--' . $flag, $GLOBALS['argv']);
}

/**
 * Get the value of the given flag from $GLOBALS['argv'].
 *
 * @param $flag
 * @return mixed
 */
function getFlagValue($flag)
{
    if (!flagIsSet($flag)) {
        return null;
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
function formatVersion($timestamp)
{
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
 * @param integer $style one of the STYLE_* constants
 * @return void
 */
function outputLine($text = '', array $arguments = array(), $indention = 0, $style = STYLE_DEFAULT)
{
    if ($arguments !== array()) {
        $text = vsprintf($text, $arguments);
    }
    if ($style !== STYLE_DEFAULT && hasColorSupport()) {
        $text = "\x1B[" . $style . "m" . $text . "\x1B[0m";
    }
    if ($indention > 0) {
        $wrappedLines = explode(PHP_EOL, wordwrap($text, MAXIMUM_LINE_LENGTH, PHP_EOL, true));
        echo implode(PHP_EOL . str_repeat(' ', $indention), $wrappedLines);
    } else {
        echo wordwrap($text, MAXIMUM_LINE_LENGTH, PHP_EOL, true);
    }
    echo PHP_EOL;
}

/**
 * @return boolean TRUE if the terminal support ANSI colors, otherwise FALSE
 */
function hasColorSupport()
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
    }

    return function_exists('posix_isatty') && @posix_isatty(STDOUT);
}

/**
 * @param string $headline
 * @param integer $level headline level (1-4)
 * @param array $arguments Optional arguments to use for sprintf
 * @return void
 */
function outputHeadline($headline, $level = 1, array $arguments = array())
{
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
function outputSeparator($character = '-')
{
    echo str_repeat($character, MAXIMUM_LINE_LENGTH);
    echo PHP_EOL;
}

/**
 * @param array $items
 * @param integer $style one of the STYLE_* constants
 * @return void
 */
function outputBulletList(array $items, $style = STYLE_DEFAULT)
{
    foreach ($items as $item) {
        outputLine('  * ' . $item, array(), 4, $style);
    }
}
