<?php
use TYPO3\Flow\Utility\Files;

/**
 * Entry Point (Router) for PHP's embedded HTTP server. Use ./flow server:run to execute.
 */

if (strpos($_SERVER['REQUEST_URI'], '_Resources/') !== FALSE) {
	// published resources shall be served directly
	return FALSE;
}

require(__DIR__. '/../Classes/TYPO3/Flow/Core/Bootstrap.php');

define('FLOW_PATH_ROOT', Files::getUnixStylePath(realpath(__DIR__ . '/../../../../')) . '/');

// Script filename and script name must "emulate" index.php, to not break routing
$_SERVER['SCRIPT_FILENAME'] = FLOW_PATH_ROOT . 'Web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// we want to have nice URLs
putenv('FLOW_REWRITEURLS=1');



$context = \TYPO3\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new \TYPO3\Flow\Core\Bootstrap($context);
$bootstrap->run();