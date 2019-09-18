<?php
declare(strict_types=1);
namespace Neos\Flow\Http\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Psr\Log\LoggerInterface;

/**
 * A HTTP component that handles authentication exceptions that were thrown by the dispatcher (@see \Neos\Flow\Mvc\Dispatcher::dispatch()) and
 * * rethrows the exception if not token with Entry Point is authenticated
 * * or otherwise invokes the Entry Point of all authenticated tokens
 */
class SecurityEntryPointComponent implements ComponentInterface
{
    public const AUTHENTICATION_EXCEPTION = 'authenticationException';

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $securityLogger;

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext): void
    {
        $authenticationException = $componentContext->getParameter(static::class, static::AUTHENTICATION_EXCEPTION);
        if ($authenticationException === null) {
            return;
        }

        /** @var TokenInterface[] $tokensWithEntryPoint */
        $tokensWithEntryPoint = array_filter($this->securityContext->getAuthenticationTokens(), static function (TokenInterface $token) {
            return $token->getAuthenticationEntryPoint() !== null;
        });

        if ($tokensWithEntryPoint === []) {
            $this->securityLogger->notice('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.');
            throw $authenticationException;
        }

        $response = $componentContext->getHttpResponse();
        foreach ($tokensWithEntryPoint as $token) {
            $entryPoint = $token->getAuthenticationEntryPoint();
            $this->securityLogger->info(sprintf('Starting authentication with entry point of type "%s"', \get_class($entryPoint)), LogEnvironment::fromMethodName(__METHOD__));

            // Only store the intercepted request if it is a GET request (otherwise it can't be resumed properly)
            // We also don't store the request for "sessionless authentications" because that would implicitly start a session
            // TODO: Adjust when a session-independent storing mechanism is possible (see https://github.com/neos/flow-development-collection/issues/1693)
            if (!$token instanceof SessionlessTokenInterface && $componentContext->getHttpRequest()->getMethod() === 'GET') {
                $actionRequest = $componentContext->getParameter(DispatchComponent::class, 'actionRequest');
                $this->securityContext->setInterceptedRequest($actionRequest->getMainRequest());
            }
            $response = $entryPoint->startAuthentication($componentContext->getHttpRequest(), $response);
        }
        $componentContext->replaceHttpResponse($response);
    }
}
