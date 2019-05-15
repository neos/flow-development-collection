<?php
namespace Neos\Flow\Http\Component;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Defines the baseUri attribute for the current request.
 *
 * This is done after the TrustedProxiesComponent to make sure
 * the proxy headers are evaluated correctly.
 */
class BaseUriComponent implements ComponentInterface
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
        $request = $componentContext->getHttpRequest();
        $baseUri = RequestInformationHelper::generateBaseUri($request);
        $baseUriSetting = self::getConfiguredBaseUri($this->objectManager);
        if (!empty($baseUriSetting)) {
            $baseUri = new Uri($baseUriSetting);
        }

        $request = $request->withAttribute(ServerRequestAttributes::ATTRIBUTE_BASE_URI, $baseUri);
        $componentContext->replaceHttpRequest($request);
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return string
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @Flow\CompileStatic
     */
    public static function getConfiguredBaseUri(ObjectManagerInterface $objectManager): string
    {
        return (string)$objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.http.baseUri');
    }
}
