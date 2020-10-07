<?php
namespace Neos\Flow\Configuration;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Package\Package;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Proxy(FALSE)
 */
class RouteConfigurationProcessor
{
    /**
     * The maximum number of recursions when merging subroute configurations.
     *
     * @var integer
     */
    const MAXIMUM_SUBROUTE_RECURSIONS = 99;

    /**
     * Counts how many SubRoutes have been loaded. If this number exceeds MAXIMUM_SUBROUTE_RECURSIONS, an exception is thrown
     *
     * @var integer
     */
    protected $subRoutesRecursionLevel = 0;

    /**
     * @var array
     */
    protected $routeSettings;

    /**
     * @var array
     */
    protected $orderedListOfContextNames;

    /**
     * @var Package[]
     */
    protected $packages;

    /**
     * @var YamlSource
     */
    protected $configurationSource;

    /**
     * RouteConfigurationProcessor constructor.
     *
     * @param array $routeSettings
     * @param array $orderedListOfContextNames
     * @param array $packages
     * @param $configurationSource
     */
    public function __construct(array $routeSettings, array $orderedListOfContextNames, array $packages, $configurationSource)
    {
        $this->routeSettings = $routeSettings;
        $this->orderedListOfContextNames = $orderedListOfContextNames;
        $this->packages = $packages;
        $this->configurationSource = $configurationSource;
    }

    /**
     * @param $routeDefinitions
     * @return array
     * @throws Exception\ParseErrorException
     * @throws Exception\RecursionException
     */
    public function process($routeDefinitions)
    {
        $routeDefinitions = $this->includeSubRoutesFromSettings($routeDefinitions);
        $routeDefinitions = $this->mergeRoutesWithSubRoutes($routeDefinitions);
        return $routeDefinitions;
    }

    /**
     * Merges routes from Neos.Flow.mvc.routes settings into $routeDefinitions
     * NOTE: Routes from settings will always be appended to existing route definitions from the main Routes configuration!
     *
     * @param array $routeDefinitions
     * @return array
     */
    protected function includeSubRoutesFromSettings($routeDefinitions)
    {
        if ($this->routeSettings === null) {
            return;
        }
        $sortedRouteSettings = (new PositionalArraySorter($this->routeSettings))->toArray();
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
     * @param array $routesConfiguration
     * @return array
     * @throws Exception\ParseErrorException
     * @throws Exception\RecursionException
     */
    protected function mergeRoutesWithSubRoutes(array $routesConfiguration)
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
                    throw new Exception\ParseErrorException(sprintf('Missing package configuration for SubRoute in Route "%s".', (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
                }
                if (!isset($this->packages[$subRouteOptions['package']])) {
                    throw new Exception\ParseErrorException(sprintf('The SubRoute Package "%s" referenced in Route "%s" is not available.', $subRouteOptions['package'], (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
                }
                /** @var $package PackageInterface */
                $package = $this->packages[$subRouteOptions['package']];
                $subRouteFilename = 'Routes';
                if (isset($subRouteOptions['suffix'])) {
                    $subRouteFilename .= '.' . $subRouteOptions['suffix'];
                }
                $subRouteConfiguration = [];
                foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
                    $subRouteFilePathAndName = $package->getConfigurationPath() . $contextName . '/' . $subRouteFilename;
                    $subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
                }
                $subRouteFilePathAndName = $package->getConfigurationPath() . $subRouteFilename;
                $subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
                if ($this->subRoutesRecursionLevel > self::MAXIMUM_SUBROUTE_RECURSIONS) {
                    throw new Exception\RecursionException(sprintf('Recursion level of SubRoutes exceed ' . self::MAXIMUM_SUBROUTE_RECURSIONS . ', probably because of a circular reference. Last successfully loaded route configuration is "%s".', $subRouteFilePathAndName), 1361535753);
                }

                $this->subRoutesRecursionLevel++;
                $subRouteConfiguration = $this->mergeRoutesWithSubRoutes($subRouteConfiguration);
                $this->subRoutesRecursionLevel--;
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
     * @throws Exception\ParseErrorException
     */
    protected function buildSubRouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, $subRouteKey, array $subRouteOptions)
    {
        $variables = $subRouteOptions['variables'] ?? [];
        $mergedSubRoutesConfigurations = [];
        foreach ($subRoutesConfiguration as $subRouteConfiguration) {
            foreach ($routesConfiguration as $routeConfiguration) {
                $mergedSubRouteConfiguration = $subRouteConfiguration;
                $mergedSubRouteConfiguration['name'] = sprintf('%s :: %s', $routeConfiguration['name'] ?? 'Unnamed Route', $subRouteConfiguration['name'] ?? 'Unnamed Subroute');
                $mergedSubRouteConfiguration['name'] = $this->replacePlaceholders($mergedSubRouteConfiguration['name'], $variables);
                if (!isset($mergedSubRouteConfiguration['uriPattern'])) {
                    throw new Exception\ParseErrorException('No uriPattern defined in route configuration "' . $mergedSubRouteConfiguration['name'] . '".', 1274197615);
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
     * @return mixed
     */
    protected function replacePlaceholders($value, array $variables)
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
