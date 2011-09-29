#!/usr/bin/env php
<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require (__DIR__ . '/../Classes/Utility/Files.php');
require (__DIR__ . '/../Classes/Exception.php');
require (__DIR__ . '/../Classes/Utility/Exception.php');

define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

if (FLOW3_SAPITYPE !== 'CLI') exit ('This script can only be executed from the command line.');

if (!isset($argv[1]) || ($argv[1] !== '--migrate' && $argv[1] !== '--dryrun')) {
	echo "
FLOW3 1.0.0 beta 1 migration script.

This script scans PHP, YAML and XML files of all installed packages for
occurrences of old namespace references and old-style package keys and
replaces them with an updated version.

MAKE SURE TO BACKUP YOUR CODE BEFORE RUNNING THIS SCRIPT!

Call this script with the --dryrun to see what would be changed.
Call this script with the --migrate to actually do the changes.
";

	exit;
}

define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../') . '/'))));
define('FLOW3_PATH_ROOT', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../../../') . '/'))));
define('FLOW3_PATH_WEB', FLOW3_PATH_ROOT . 'Web/');
define('FLOW3_PATH_CONFIGURATION', FLOW3_PATH_ROOT . 'Configuration/');
define('FLOW3_PATH_DATA', FLOW3_PATH_ROOT . 'Data/');
define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');



$phpFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.php', TRUE);
$htmlFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.html', TRUE);
$yamlFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.yaml', TRUE);
$configurationFiles = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_CONFIGURATION, '.yaml', TRUE);

$allPathsAndFilenames = array_merge($phpFiles, $yamlFiles, $configurationFiles, $htmlFiles);
foreach ($allPathsAndFilenames as $pathAndFilename) {
	$pathInfo = pathinfo($pathAndFilename);

	if (!isset($pathInfo['filename'])) continue;
	if ($pathAndFilename === __FILE__) continue;
	if (strpos($pathAndFilename, 'Packages/Framework/') !== FALSE) continue;

	$file = file_get_contents($pathAndFilename);
	$fileBackup = $file;

	$file = str_replace('F3\\FLOW3', 'TYPO3\\FLOW3', $file);
	$file = str_replace('F3\\Fluid', 'TYPO3\\Fluid', $file);
	$file = str_replace('F3\\ExtJS', 'TYPO3\\ExtJS', $file);
	$file = str_replace('F3\\Welcome', 'TYPO3\\Welcome', $file);
	$file = str_replace('F3\\Party', 'TYPO3\\Party', $file);
	$file = str_replace('F3\\Kickstart', 'TYPO3\\Kickstart', $file);
	$file = str_replace('F3\\', 'YourCompanyName\\', $file);

	$file = preg_replace('%resource://(FLOW3|Fluid|Kickstart|ExtJS|Welcome|Party|DocumentationBrowser)/(Private|Public)/%', 'resource://TYPO3.$1/$2/', $file);

	$file = preg_replace('%package="(FLOW3|Fluid|Kickstart|ExtJS|Welcome|Party|DocumentationBrowser)"%', 'package="TYPO3.$1"', $file);
	$file = preg_replace('%package="([a-zA-Z0-9]+)"%', 'package="YourCompanyName.$1"', $file);

	$file = preg_replace('%(\'?@package\'?: *\'?)(FLOW3|Fluid|Kickstart|ExtJS|Welcome|Party|DocumentationBrowser)(\'?\s)%', '$1TYPO3.$2$3', $file);
	$file = preg_replace('%(\'?@package\'?: *\'?)([a-zA-Z0-9]+)(\'?\s)%', '$1YourCompanyName.$2$3', $file);

	$shortPathAndFilename = substr($pathAndFilename, strlen(FLOW3_PATH_ROOT));
	if ($file !== $fileBackup) {
		if ($argv[1] === '--migrate') {
			echo 'Updated           ' . $shortPathAndFilename . chr(10);
			file_put_contents($pathAndFilename, $file);
		} else {
			echo 'Would update      ' . $shortPathAndFilename . chr(10);
		}
	}
	unset($file);

}

echo "\nDone.\n";

?>
