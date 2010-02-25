<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require (__DIR__ . '/../Classes/Utility/Files.php');

define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

if (FLOW3_SAPITYPE !== 'CLI') exit ('This script can only be executed from the command line.');

define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../') . '/'))));

$rootPath = isset($_SERVER['FLOW3_ROOTPATH']) ? $_SERVER['FLOW3_ROOTPATH'] : FALSE;
if ($rootPath === FALSE && isset($_SERVER['REDIRECT_FLOW3_ROOTPATH'])) {
	$rootPath = $_SERVER['REDIRECT_FLOW3_ROOTPATH'];
}
if ($rootPath !== FALSE) {
	$rootPath = str_replace('//', '/', str_replace('\\', '/', (realpath($_SERVER['FLOW3_ROOTPATH'])))) . '/';
	$testPath = str_replace('//', '/', str_replace('\\', '/', (realpath($rootPath . 'Packages/Framework/FLOW3')))) . '/';
	if ($testPath !== FLOW3_PATH_FLOW3) {
		exit('FLOW3: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $rootPath . 'Packages/Framework/FLOW3' .'" does not lead to' . PHP_EOL . '"' . FLOW3_PATH_FLOW3 .'"' . PHP_EOL);
	}
	define('FLOW3_PATH_ROOT', $rootPath);
	unset($rootPath);
	unset($testPath);
}

if (!defined('FLOW3_PATH_ROOT')) {
	exit('FLOW3: No root path defined in environment variable FLOW3_ROOTPATH (Error #1248964376)' . PHP_EOL);
}
if (!isset($_SERVER['FLOW3_WEBPATH']) || !is_dir($_SERVER['FLOW3_WEBPATH'])) {
	exit('FLOW3: No web path defined in environment variable FLOW3_WEBPATH or directory does not exist (Error #1249046843)' . PHP_EOL);
}

define('FLOW3_PATH_WEB', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($_SERVER['FLOW3_WEBPATH'])) . '/');
define('FLOW3_PATH_CONFIGURATION', FLOW3_PATH_ROOT . 'Configuration/');
define('FLOW3_PATH_DATA', FLOW3_PATH_ROOT . 'Data/');
define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');

echo "
FLOW3 1.0.0 alpha 8 migration script.

This script replaces calls and other references to the old object manager and
object factory to the new API introduced in 1.0.0 alpha 8.

";

$phpFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.php', TRUE);
$yamlFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.yaml', TRUE);
$xmlFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.xml', TRUE);
$configurationFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_CONFIGURATION, '.yaml', TRUE);

$allPathsAndFilenames = array_merge($phpFiles, $yamlFiles, $xmlFiles, $configurationFiles);

foreach ($allPathsAndFilenames as $pathAndFilename) {
	$pathInfo = pathinfo($pathAndFilename);
	if (!isset($pathInfo['filename'])) continue;

#	if (strpos($pathAndFilename, 'Packages/Framework/') !== FALSE) continue;
	if ($pathAndFilename === __FILE__) continue;
	if (strpos($pathAndFilename, 'FLOW3/Classes/Object/') !== FALSE) continue;

	$file = file_get_contents($pathAndFilename);
	$fileBackup = $file;

	$file = preg_replace('/([^a-zA-Z])\\\\F3\\\\FLOW3\\\\Object\\\\ObjectFactoryInterface([^a-zA-Z])/', '$1\\F3\\FLOW3\\Object\\ObjectManagerInterface$2', $file);
	$file = preg_replace('/([oO]bjectManager)->get/', '$1->get', $file);
	$file = preg_replace('/([oO]bject)Factory/', '$1Manager', $file);

	if ($file !== $fileBackup) {
		file_put_contents('/tmp/flow3/' . str_replace('/', '_', $pathAndFilename), $file);
		echo 'Updated           ' . $pathAndFilename . chr(10);
	} else {
		echo 'No need to update ' . $pathAndFilename . chr(10);
	}

	unset($file);
}

echo "\nDone.\n";

?>