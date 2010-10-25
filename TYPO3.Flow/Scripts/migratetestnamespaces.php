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

if (isset($_SERVER['FLOW3_ROOTPATH'])) {
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
FLOW3 1.0.0 alpha 13 test case namespaces migration script.

This script scans the Tests/Unit/ directory of each package and
adjusts the namespace declaration of each test case to match the
new conventions in FLOW3 1.0.0 alpha 13.
";


$files = \F3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, 'Test.php', TRUE);

foreach ($files as $pathAndFilename) {
	$pathInfo = pathinfo($pathAndFilename);
	if (!isset($pathInfo['filename'])) continue;

	if (strpos($pathAndFilename, 'Packages/Framework/') !== FALSE) continue;
	if ($pathAndFilename === __FILE__) continue;

	$file = file_get_contents($pathAndFilename);
	$fileBackup = $file;

	$lines = explode(chr(10), $file);
	if (count($lines) > 3 && $lines[0] === "<?php" && substr($lines[2], 0, 13) === 'namespace F3\\') {
		$pathSegments = explode('/', substr($pathAndFilename, strlen(FLOW3_PATH_PACKAGES)));
		$newNamespace = 'F3\\' . implode('\\', array_slice($pathSegments, 1, count($pathSegments) - 2));
		$lines[2] = "namespace $newNamespace;";
		$file = implode(chr(10), $lines);
	}

	if ($file !== $fileBackup) {
		echo 'Updated           ' . $pathAndFilename . chr(10);
		file_put_contents($pathAndFilename, $file);
	} else {
		echo 'No need to update ' . $pathAndFilename . chr(10);
	}

	unset($file);
}

echo "\nDone.\n";

?>