<?php
/**
 * Entry Point (Router) for PHP's embedded HTTP server. Use ./flow server:run to execute.
 */

if (strpos($_SERVER['REQUEST_URI'], '_Resources/') !== false) {
    // published resources shall be served directly
    return false;
}

require(__DIR__. '/../Classes/Core/Bootstrap.php');

if (DIRECTORY_SEPARATOR !== '/' && trim(getenv('FLOW_ROOTPATH'), '"\' ') === '') {
    $absoluteRootpath = dirname(realpath(__DIR__ . '/../../../'));
    if (realpath(getcwd()) === $absoluteRootpath) {
        $_SERVER['FLOW_ROOTPATH'] = '.';
    } elseif (strlen(getcwd()) > strlen($absoluteRootpath)) {
        $amountOfPathsToSkipBack = substr_count(getcwd(), DIRECTORY_SEPARATOR) - substr_count($absoluteRootpath, DIRECTORY_SEPARATOR);
        $_SERVER['FLOW_ROOTPATH'] = implode('/', array_fill(0, $amountOfPathsToSkipBack, '..'));
    } else {
        $_SERVER['FLOW_ROOTPATH'] = substr($absoluteRootpath, strlen(getcwd()) + 1);
    }
} else {
    $_SERVER['FLOW_ROOTPATH'] = trim(getenv('FLOW_ROOTPATH'), '"\' ') ?: dirname($_SERVER['PHP_SELF']);
}

// Script filename and script name must "emulate" index.php, to not break routing
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['FLOW_ROOTPATH'] . 'Web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// we want to have nice URLs
putenv('FLOW_REWRITEURLS=1');

$context = \Neos\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new \Neos\Flow\Core\Bootstrap($context);
$bootstrap->run();
