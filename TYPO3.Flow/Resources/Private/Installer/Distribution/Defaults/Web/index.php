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

$rootPath = isset($_SERVER['FLOW_ROOTPATH']) ? $_SERVER['FLOW_ROOTPATH'] : false;
if ($rootPath === false && isset($_SERVER['REDIRECT_FLOW_ROOTPATH'])) {
    $rootPath = $_SERVER['REDIRECT_FLOW_ROOTPATH'];
}
if ($rootPath === false) {
    $rootPath = dirname(__FILE__) . '/../';
} elseif (substr($rootPath, -1) !== '/') {
    $rootPath .= '/';
}

require($rootPath . 'Packages/Framework/TYPO3.Flow/Classes/TYPO3/Flow/Core/Bootstrap.php');

$context = \TYPO3\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new \TYPO3\Flow\Core\Bootstrap($context);
$bootstrap->run();
