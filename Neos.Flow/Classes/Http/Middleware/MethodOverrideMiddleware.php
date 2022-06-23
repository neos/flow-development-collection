<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Allows to override the request HTTP Method via different overrides in this order:
 * - a "__method" argument passes in via request body
 * - X-Http-Method-Override header
 * - X-Http-Method header
 */
class MethodOverrideMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and overwrite the request method of a POST based on a __method argument or either
     * the X-Http-Method-Override or X-Http-Method header.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $arguments = $request->getParsedBody();
            if (isset($arguments['__method'])) {
                $request = $request->withMethod($arguments['__method']);
            } elseif ($request->hasHeader('X-Http-Method-Override')) {
                $request = $request->withMethod($request->getHeaderLine('X-Http-Method-Override'));
            } elseif ($request->hasHeader('X-Http-Method')) {
                $request = $request->withMethod($request->getHeaderLine('X-Http-Method'));
            }
        }
        return $next->handle($request);
    }

}
