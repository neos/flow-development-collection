<?php
namespace Neos\Flow\Http\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses the request body and adds the result to the ServerRequest instance.
 */
class RequestBodyParsingComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

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
        $httpRequest = $componentContext->getHttpRequest();
        if (!empty($httpRequest->getParsedBody())) {
            return;
        }
        $parsedBody = $this->parseRequestBody($httpRequest);
        $httpRequest = $httpRequest->withParsedBody($parsedBody);
        $componentContext->replaceHttpRequest($httpRequest);
    }

    /**
     * Parses the request body according to the media type.
     *
     * @param ServerRequestInterface $httpRequest
     * @return array
     */
    protected function parseRequestBody(ServerRequestInterface $httpRequest): array
    {
        $requestBody = $httpRequest->getBody()->getContents();
        if ($requestBody === null || $requestBody === '') {
            return [];
        }

        $mediaTypeConverter = $this->objectManager->get(MediaTypeConverterInterface::class);
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
        $requestedContentType = RequestInformationHelper::getFirstRequestHeaderValue($httpRequest, 'Content-Type');
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $requestedContentType);
        $arguments = $this->propertyMapper->convert($requestBody, 'array', $propertyMappingConfiguration);

        return $arguments;
    }
}
