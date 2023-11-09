<?php
/*
 * This file configures dynamic return type support for factory methods in PhpStorm
 * see https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {
    expectedArguments(\Neos\Flow\Annotations\Validate::__construct(), 1, 'AggregateBoundary', 'Alphanumeric', 'Boolean', 'Collection', 'Conjunction', 'Count', 'DateTimeRange', 'DateTime', 'Disjunction', 'EmailAddress', 'Float', 'GenericObject', 'Integer', 'Label', 'LocaleIdentifier', 'NotEmpty', 'NumberRange', 'Number', 'Raw', 'RegularExpresion', 'StringLength', 'Text', 'UniqueEntity', 'Uuid');

    expectedArguments(\Neos\Flow\Annotations\Scope::__construct(), 0, 'prototype', 'session', 'singleton');

    registerArgumentsSet(
        'flowSettingsTypes',
        \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES,
        \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS,
        \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
        \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
        \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS
    );
    expectedArguments(\Neos\Flow\Annotations\InjectConfiguration::__construct(), 2, argumentsSet('flowSettingsTypes'));
    expectedArguments(\Neos\Flow\Configuration\ConfigurationManager::getConfiguration(), 0, argumentsSet('flowSettingsTypes'));
    expectedArguments(\Neos\Flow\Configuration\ConfigurationManager::loadConfiguration(), 0, argumentsSet('flowSettingsTypes'));
}
