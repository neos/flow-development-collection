<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parses the request body and adds the result to the ServerRequest instance.
 */
class RequestBodyParsingMiddleware implements MiddlewareInterface
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if (!empty($request->getParsedBody())) {
            return $next->handle($request);
        }
        $parsedBody = $this->parseRequestBody($request);
        return $next->handle($request->withParsedBody($parsedBody));
    }

    /**
     * Parses the request body according to the media type.
     *
     * @param ServerRequestInterface $httpRequest
     * @return null|array|string|integer
     */
    protected function parseRequestBody(ServerRequestInterface $httpRequest)
    {
        $requestBody = $httpRequest->getBody()->getContents();
        if ($requestBody === null || $requestBody === '') {
            return $requestBody;
        }

        /** @var MediaTypeConverterInterface $mediaTypeConverter */
        $mediaTypeConverter = $this->objectManager->get(MediaTypeConverterInterface::class);
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
        $requestedContentType = $httpRequest->getHeaderLine('Content-Type');
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $requestedContentType);
        // FIXME: The MediaTypeConverter returns an empty array for "error cases", which might be unintended
        return $this->propertyMapper->convert($requestBody, 'array', $propertyMappingConfiguration);
    }
}
