<?php
declare(strict_types=1);

namespace Neos\Flow\Configuration\Loader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception as ConfigurationException;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Configuration\Exception\RecursionException;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

class RoutesLoader implements LoaderInterface
{
    /**
     * The maximum number of recursions when merging subroute configurations.
     *
     * @var integer
     */
    private const MAXIMUM_SUBROUTE_RECURSIONS = 99;

    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    public function __construct(YamlSource $yamlSource, ConfigurationManager $configurationManager)
    {
        $this->yamlSource = $yamlSource;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param array $packages
     * @param ApplicationContext $context
     * @return array
     * @throws ConfigurationException | InvalidConfigurationException | InvalidConfigurationTypeException | ParseErrorException | RecursionException
     */
    public function load(array $packages, ApplicationContext $context): array
    {
        // load main routes
        $routesConfiguration = [];
        foreach (array_reverse($context->getHierarchy()) as $contextName) {
            $routesConfiguration[] = $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
        }
        $routesConfiguration[] = $this->yamlSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
        $routesConfiguration = array_merge([], ...$routesConfiguration);

        $routeSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.mvc.routes') ?? [];

        $routesConfiguration = $this->includeSubRoutesFromSettings($routesConfiguration, $routeSettings);
        return $this->mergeRoutesWithSubRoutes($packages, $context, $routesConfiguration);
    }

    /**
     * Merges routes from Neos.Flow.mvc.routes settings into $routeDefinitions
     * NOTE: Routes from settings will always be appended to existing route definitions from the main Routes configuration!
     *
     * @param array $routeDefinitions
     * @param array $routeSettings
     * @return array
     */
    protected function includeSubRoutesFromSettings(array $routeDefinitions, array $routeSettings): array
    {
        $sortedRouteSettings = (new PositionalArraySorter($routeSettings))->toArray();
        foreach ($sortedRouteSettings as $packageKey => $routeFromSettings) {
            if ($routeFromSettings === false) {
                continue;
            }
            $subRoutesName = $packageKey . 'SubRoutes';
            $subRoutesConfiguration = ['package' => $packageKey];
            if (isset($routeFromSettings['variables'])) {
                $subRoutesConfiguration['variables'] = $routeFromSettings['variables'];
            }
            if (isset($routeFromSettings['suffix'])) {
                $subRoutesConfiguration['suffix'] = $routeFromSettings['suffix'];
            }
            $routeDefinitions[] = [
                'name' => $packageKey,
                'uriPattern' => '<' . $subRoutesName . '>',
                'subRoutes' => [
                    $subRoutesName => $subRoutesConfiguration
                ]
            ];
        }

        return $routeDefinitions;
    }

    /**
     * Loads specified sub routes and builds composite routes.
     *
     * @param PackageInterface[] $packages
     * @param ApplicationContext $context
     * @param array $routesConfiguration
     * @param int $subRoutesRecursionLevel Counts how many SubRoutes have been loaded. If this number exceeds MAXIMUM_SUBROUTE_RECURSIONS, an exception is thrown
     * @return array
     * @throws ParseErrorException | RecursionException| ConfigurationException
     */
    protected function mergeRoutesWithSubRoutes(array $packages, ApplicationContext $context, array $routesConfiguration, int $subRoutesRecursionLevel = 0): array
    {
        $mergedRoutesConfiguration = [];
        foreach ($routesConfiguration as $routeConfiguration) {
            if (!isset($routeConfiguration['subRoutes'])) {
                $mergedRoutesConfiguration[] = $routeConfiguration;
                continue;
            }
            $mergedSubRoutesConfiguration = [$routeConfiguration];
            foreach ($routeConfiguration['subRoutes'] as $subRouteKey => $subRouteOptions) {
                if (!isset($subRouteOptions['package'])) {
                    throw new ParseErrorException(sprintf('Missing package configuration for SubRoute in Route "%s".', ($routeConfiguration['name'] ?? 'unnamed Route')), 1318414040);
                }
                if (!isset($packages[$subRouteOptions['package']])) {
                    throw new ParseErrorException(sprintf('The SubRoute Package "%s" referenced in Route "%s" is not available.', $subRouteOptions['package'], ($routeConfiguration['name'] ?? 'unnamed Route')), 1318414040);
                }
                /** @var FlowPackageInterface $package */
                $package = $packages[$subRouteOptions['package']];
                $subRouteFilename = 'Routes';
                if (isset($subRouteOptions['suffix'])) {
                    $subRouteFilename .= '.' . $subRouteOptions['suffix'];
                }
                $subRouteConfiguration = [];
                foreach (array_reverse($context->getHierarchy()) as $contextName) {
                    $subRouteFilePathAndName = $package->getConfigurationPath() . $contextName . '/' . $subRouteFilename;
                    $subRouteConfiguration[] = $this->yamlSource->load($subRouteFilePathAndName);
                }
                $subRouteFilePathAndName = $package->getConfigurationPath() . $subRouteFilename;
                $subRouteConfiguration[] = $this->yamlSource->load($subRouteFilePathAndName);
                if ($subRoutesRecursionLevel > self::MAXIMUM_SUBROUTE_RECURSIONS) {
                    throw new RecursionException(sprintf('Recursion level of SubRoutes exceed ' . self::MAXIMUM_SUBROUTE_RECURSIONS . ', probably because of a circular reference. Last successfully loaded route configuration is "%s".', $subRouteFilePathAndName), 1361535753);
                }
                $subRouteConfiguration = array_merge([], ...$subRouteConfiguration);

                $subRouteConfiguration = $this->mergeRoutesWithSubRoutes($packages, $context, $subRouteConfiguration, $subRoutesRecursionLevel + 1);
                $mergedSubRoutesConfiguration = $this->buildSubRouteConfigurations($mergedSubRoutesConfiguration, $subRouteConfiguration, $subRouteKey, $subRouteOptions);
            }
            $mergedRoutesConfiguration = array_merge($mergedRoutesConfiguration, $mergedSubRoutesConfiguration);
        }

        return $mergedRoutesConfiguration;
    }

    /**
     * Merges all routes in $routesConfiguration with the sub routes in $subRoutesConfiguration
     *
     * @param array $routesConfiguration
     * @param array $subRoutesConfiguration
     * @param string $subRouteKey the key of the sub route: <subRouteKey>
     * @param array $subRouteOptions
     * @return array the merged route configuration
     * @throws ParseErrorException
     */
    protected function buildSubRouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, string $subRouteKey, array $subRouteOptions): array
    {
        $variables = $subRouteOptions['variables'] ?? [];
        $mergedSubRoutesConfigurations = [];
        foreach ($subRoutesConfiguration as $subRouteConfiguration) {
            foreach ($routesConfiguration as $routeConfiguration) {
                $mergedSubRouteConfiguration = $subRouteConfiguration;
                $mergedSubRouteConfiguration['name'] = sprintf('%s :: %s', $routeConfiguration['name'] ?? 'Unnamed Route', $subRouteConfiguration['name'] ?? 'Unnamed Subroute');
                $mergedSubRouteConfiguration['name'] = $this->replacePlaceholders($mergedSubRouteConfiguration['name'], $variables);
                if (!isset($mergedSubRouteConfiguration['uriPattern'])) {
                    throw new ParseErrorException('No uriPattern defined in route configuration "' . $mergedSubRouteConfiguration['name'] . '".', 1274197615);
                }
                if ($mergedSubRouteConfiguration['uriPattern'] !== '') {
                    $mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($mergedSubRouteConfiguration['uriPattern'], $variables);
                    $mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($routeConfiguration['uriPattern'], [$subRouteKey => $mergedSubRouteConfiguration['uriPattern']]);
                } else {
                    $mergedSubRouteConfiguration['uriPattern'] = rtrim($this->replacePlaceholders($routeConfiguration['uriPattern'], [$subRouteKey => '']), '/');
                }
                if (isset($mergedSubRouteConfiguration['defaults'])) {
                    $mergedSubRouteConfiguration['defaults'] = $this->replacePlaceholders($mergedSubRouteConfiguration['defaults'], $variables);
                }
                if (isset($mergedSubRouteConfiguration['routeParts'])) {
                    $mergedSubRouteConfiguration['routeParts'] = $this->replacePlaceholders($mergedSubRouteConfiguration['routeParts'], $variables);
                }
                $mergedSubRouteConfiguration = Arrays::arrayMergeRecursiveOverrule($routeConfiguration, $mergedSubRouteConfiguration);
                unset($mergedSubRouteConfiguration['subRoutes']);
                $mergedSubRoutesConfigurations[] = $mergedSubRouteConfiguration;
            }
        }

        return $mergedSubRoutesConfigurations;
    }

    /**
     * Replaces placeholders in the format <variableName> with the corresponding variable of the specified $variables collection.
     *
     * @param string|array $value
     * @param array $variables
     * @return array|string
     */
    private function replacePlaceholders($value, array $variables)
    {
        if (is_array($value)) {
            foreach ($value as $arrayKey => $arrayValue) {
                $value[$arrayKey] = $this->replacePlaceholders($arrayValue, $variables);
            }
        } elseif (is_string($value)) {
            foreach ($variables as $variableName => $variableValue) {
                $value = str_replace('<' . $variableName . '>', $variableValue, $value);
            }
        }
        return $value;
    }
}
