<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

$rootPath = isset($_SERVER['FLOW3_ROOTPATH']) ? $_SERVER['FLOW3_ROOTPATH'] : FALSE;
if ($rootPath === FALSE && isset($_SERVER['REDIRECT_FLOW3_ROOTPATH'])) {
	$rootPath = $_SERVER['REDIRECT_FLOW3_ROOTPATH'];
}
if ($rootPath === FALSE) {
	$rootPath = dirname(__FILE__) . '/../';
} elseif (substr($rootPath, -1) !== '/') {
	$rootPath .= '/';
}

require($rootPath . 'Packages/Framework/TYPO3.FLOW3/Classes/TYPO3/FLOW3/Core/Bootstrap.php');

$context = getenv('FLOW3_CONTEXT') ?: (getenv('REDIRECT_FLOW3_CONTEXT') ?: 'Development');
$bootstrap = new \TYPO3\FLOW3\Core\Bootstrap($context);
$bootstrap->run();

?>