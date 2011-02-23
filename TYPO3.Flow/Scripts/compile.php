<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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

/**
 * Bootstrap for the FLOW3 code compiler
 *
 * @author Robert Lemke <robert@typo3.org>
 */

require(__DIR__ . '/../Classes/Core/Bootstrap.php');

if (PHP_SAPI !== 'cli') {
	exit(1);
};

if ($argc !== 4) {
	exit(2);
}

define('FLOW3_PATH_ROOT', $argv[2]);
define('FLOW3_PATH_WEB', $argv[3]);
\F3\FLOW3\Core\Bootstrap::defineConstants();

if ($argv[1] === 'Testing') {
	require_once('PHPUnit/Autoload.php');
	require_once(FLOW3_PATH_ROOT . 'Packages/Framework/FLOW3/Tests/BaseTestCase.php');
	require_once(FLOW3_PATH_ROOT . 'Packages/Framework/FLOW3/Tests/FunctionalTestCase.php');
}

$flow3 = new \F3\FLOW3\Core\Bootstrap($argv[1]);
$flow3->compile();

?>