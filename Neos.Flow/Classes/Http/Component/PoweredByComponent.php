<?php
namespace Neos\Flow\Http\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;

/**
 * Adds the "X-Flow-Powered" to the response.
 */
class PoweredByComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $token = static::prepareApplicationToken($this->objectManager);
        if ($token === '') {
            return;
        }

        $response = $componentContext->getHttpResponse();
        $response = $response->withAddedHeader('X-Flow-Powered', $token);
        $componentContext->replaceHttpResponse($response);
    }

    /**
     * Renders a major version out of a full version string
     *
     * @param string $version For example "2.3.7"
     * @return string For example "2"
     */
    protected static function renderMajorVersion($version)
    {
        preg_match('/^(\d+)/', $version, $versionMatches);

        return isset($versionMatches[1]) ? $versionMatches[1] : '';
    }

    /**
     * Renders a minor version out of a full version string
     *
     * @param string $version For example "2.3.7"
     * @return string For example "2.3"
     */
    protected static function renderMinorVersion($version)
    {
        preg_match('/^(\d+\.\d+)/', $version, $versionMatches);

        return isset($versionMatches[1]) ? $versionMatches[1] : '';
    }

    /**
     * Generate an application information header for the response based on settings and package versions.
     * Will statically compile in production for performance benefits.
     *
     * @param ObjectManagerInterface $objectManager
     * @return string
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @Flow\CompileStatic
     */
    public static function prepareApplicationToken(ObjectManagerInterface $objectManager): string
    {
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $tokenSetting = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.http.applicationToken');

        if (!in_array($tokenSetting, ['ApplicationName', 'MinorVersion', 'MajorVersion'])) {
            return '';
        }

        $applicationPackageKey = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.core.applicationPackageKey');
        $applicationName = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.core.applicationName');
        $applicationIsNotFlow = ($applicationPackageKey !== 'Neos.Flow');

        if ($tokenSetting === 'ApplicationName') {
            return 'Flow' . ($applicationIsNotFlow ? ' ' . $applicationName : '');
        }

        // At this point the $tokenSetting must be either "MinorVersion" or "MajorVersion" so lets use it.
        $versionRenderer = 'render' . $tokenSetting;

        $packageManager = $objectManager->get(PackageManager::class);
        $flowPackage = $packageManager->getPackage('Neos.Flow');
        $flowVersion = static::$versionRenderer($flowPackage->getInstalledVersion());

        $applicationPackage = $applicationIsNotFlow ? $packageManager->getPackage($applicationPackageKey) : null;
        $applicationVersion = ($applicationIsNotFlow && $applicationPackage !== null) ? static::$versionRenderer($applicationPackage->getInstalledVersion()) : null;

        return 'Flow/' . ($flowVersion ?: 'dev') . ($applicationIsNotFlow ? ' ' . $applicationName . '/' . ($applicationVersion ?: 'dev') : '');
    }
}
