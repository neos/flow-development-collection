<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\Error\WithReferenceCodeInterface;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Http\Factories\ResponseFactory;
use Neos\Http\Factories\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A middleware that returns a 4xx message if no other middleware was able to process the request
 */
class NotFoundMiddleware implements MiddlewareInterface
{
    public const REFERENCE_CODE = 'notFoundReferenceCode';

    public const STATUS_CODE = 'notFoundStatusCode';

    public const DESCRIPTION = 'notFoundDescription';

    public const DETAILS = 'notFoundDetails';

    /**
     * @var ResponseFactory
     * @Flow\Inject
     */
    protected $responseFactory;

    /**
     * @var StreamFactory
     * @Flow\Inject
     */
    protected $streamFactory;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="http.notFound.viewClassName")
     */
    protected $viewClassName;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="http.notFound.viewOptions")
     */
    protected $viewOptions;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="http.notFound.renderingOptions")
     */
    protected $renderingOptions;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="http.notFound.default.description")
     */
    protected $defaultDescription;

    /**
     * @var int
     * @Flow\InjectConfiguration(path="http.notFound.default.statusCode")
     */
    protected $defaultStatusCode;


    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $statusCode = $request->getAttribute(self::STATUS_CODE) ?? $this->defaultStatusCode;
        $text = $request->getAttribute(self::DESCRIPTION) ?? $this->defaultDescription;
        $details = $request->getAttribute(self::DETAILS) ?? '';
        $referenceCode = $request->getAttribute(self::REFERENCE_CODE) ?? null;

        $body = $this->render(
            $request,
            $statusCode,
            $text,
            $details,
            $referenceCode
        );

        return $this->responseFactory->createResponse($statusCode)->withBody($this->streamFactory->createStream($body));
    }

    /**
     * @param ServerRequestInterface $httpRequest
     * @param int $statusCode
     * @param string|null $text
     * @param string|null $details
     * @param array $renderingOptions
     * @param int|null $referenceCode
     * @return string
     */
    protected function render(ServerRequestInterface $httpRequest, int $statusCode, ?string $text = '', ?string $details = '', mixed $referenceCode = null): string
    {
        $statusMessage = ResponseInformationHelper::getStatusMessageByCode($statusCode);
        $viewClassName = $this->viewClassName;
        $viewOptions = array_filter($this->viewOptions, static function ($optionValue) {
            return $optionValue !== null;
        });
        /** @var ViewInterface $view */
        $view = $viewClassName::createWithOptions($viewOptions);

        $request = ActionRequest::fromHttpRequest($httpRequest);
        $request->setControllerPackageKey('Neos.Flow');
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $view->setControllerContext(new ControllerContext(
            $request,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        ));

        $view->assignMultiple([
            'renderingOptions' => $this->renderingOptions,
            'referenceCode' => $referenceCode,
            'statusCode' => $statusCode,
            'statusMessage' => $statusMessage,
            'errorDescription' => $text,
            'exception' => [
                'message' => $details,
                'referenceCode' => $referenceCode
            ]
        ]);

        return $view->render();
    }
}
