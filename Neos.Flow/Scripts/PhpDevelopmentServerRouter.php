<?php
use Neos\Utility\Files;

/**
 * Entry Point (Router) for PHP's embedded HTTP server. Use ./flow server:run to execute.
 */

if (strpos($_SERVER['REQUEST_URI'], '_Resources/') !== false) {
    // published resources shall be served directly
    return false;
}

require(__DIR__. '/../Classes/Core/Bootstrap.php');

define('FLOW_PATH_ROOT', Files::getUnixStylePath(realpath(__DIR__ . '/../../../../')) . '/');

// Script filename and script name must "emulate" index.php, to not break routing
$_SERVER['SCRIPT_FILENAME'] = FLOW_PATH_ROOT . 'Web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// we want to have nice URLs
putenv('FLOW_REWRITEURLS=1');



$context = \Neos\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new \Neos\Flow\Core\Bootstrap($context);
$bootstrap->run();
