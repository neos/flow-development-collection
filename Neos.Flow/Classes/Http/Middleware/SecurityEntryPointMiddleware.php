<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A HTTP middleware that handles authentication exceptions that were thrown by the dispatcher (@see \Neos\Flow\Mvc\Dispatcher::dispatch()) and
 * * rethrows the exception if no token with Entry Point is authenticated
 * * or otherwise invokes the Entry Point of all authenticated tokens
 */
class SecurityEntryPointMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject(name="Neos.Flow:SecurityLogger")
     * @var LoggerInterface
     */
    protected $securityLogger;

    /**
     * @Flow\Inject(lazy=false)
     * @var ActionRequestFactory
     */
    protected $actionRequestFactory;

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // FIXME: Currently the security context needs an ActionRequest, therefore we need to build it here
        $routingMatchResults = $request->getAttribute(ServerRequestAttributes::ROUTING_RESULTS) ?? [];
        $actionRequest = $this->actionRequestFactory->createActionRequest($request, $routingMatchResults);
        $this->securityContext->setRequest($actionRequest);
        try {
            return $next->handle($request->withAttribute(ServerRequestAttributes::ACTION_REQUEST, $actionRequest));
        } catch (AuthenticationRequiredException $authenticationException) {
            /** @var TokenInterface[] $tokensWithEntryPoint */
            $tokensWithEntryPoint = array_filter($this->securityContext->getAuthenticationTokens(), static function (TokenInterface $token) {
                return $token->getAuthenticationEntryPoint() !== null;
            });

            if ($tokensWithEntryPoint === []) {
                $this->securityLogger->notice('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.');
                throw $authenticationException;
            }

            $response = $this->buildHttpResponse();
            foreach ($tokensWithEntryPoint as $token) {
                $entryPoint = $token->getAuthenticationEntryPoint();
                $this->securityLogger->info(sprintf('Starting authentication with entry point of type "%s"', \get_class($entryPoint)), LogEnvironment::fromMethodName(__METHOD__));

                // Only store the intercepted request if it is a GET request (otherwise it can't be resumed properly)
                // We also don't store the request for "sessionless authentications" because that would implicitly start a session
                // TODO: Adjust when a session-independent storing mechanism is possible (see https://github.com/neos/flow-development-collection/issues/1693)
                if (!$token instanceof SessionlessTokenInterface
                    && $request->getMethod() === 'GET'
                    && $authenticationException->hasInterceptedRequest()) {
                    $this->securityContext->setInterceptedRequest($authenticationException->getInterceptedRequest());
                }
                $response = $entryPoint->startAuthentication($request, $response);
            }
            return $response;
        }
    }

    /**
     * Build a base HTTP Response in case of a missing authentication exception
     *
     * @return ResponseInterface
     */
    protected function buildHttpResponse(): ResponseInterface
    {
        // 403 to be b/c with the previous implementation, but 401 would be more fitting
        return new Response(403);
    }
}
