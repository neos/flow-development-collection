<?php

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Bootstrap for the command line
 */

if (PHP_SAPI !== 'cli') {
    echo(sprintf("The Neos Flow command line script or sub process was executed with a '%s' PHP binary. Make sure that you specified a CLI capable PHP binary in your PATH or Flow's Settings.yaml.", PHP_SAPI) . PHP_EOL);
    exit(1);
}

if (isset($argv[1]) && ($argv[1] === 'neos.flow:core:setfilepermissions' || $argv[1] === 'flow:core:setfilepermissions' || $argv[1] === 'core:setfilepermissions')) {
    if (DIRECTORY_SEPARATOR !== '/') {
        exit('The core:setfilepermissions command is only available on UNIX platforms.' . PHP_EOL);
    }

    $filePermissions = decoct(fileperms(__DIR__ . '/setfilepermissions.sh') & 0777);
    if ($filePermissions !== '700') {
        chmod(__DIR__ . '/setfilepermissions.sh', 0700);
    }

    array_shift($argv);
    array_shift($argv);
    $returnValue = 0;
    exec(__DIR__ . '/setfilepermissions.sh ' . implode($argv, ' '), $output, $returnValue);
    exit($returnValue);
} elseif (isset($argv[1]) && ($argv[1] === 'neos.flow:core:migrate' || $argv[1] === 'flow:core:migrate' || $argv[1] === 'core:migrate')) {
    array_shift($argv);
    array_shift($argv);
    require(__DIR__ . '/migrate.php');
} else {
    require(__DIR__ . '/../Classes/Core/Bootstrap.php');

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

    $context = trim(\Neos\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT'), '"\' ') ?: 'Development';

    $bootstrap = new \Neos\Flow\Core\Bootstrap($context);
    $bootstrap->run();
}
