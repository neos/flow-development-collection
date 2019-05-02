<?php
namespace Neos\Flow\Http\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Psr\Log\LoggerInterface;

/**
 *
 */
class SecurityEntryPointComponent implements ComponentInterface
{
    const AUTHENTICATION_EXCEPTION = 'authenticationException';

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
    public function handle(ComponentContext $componentContext)
    {
        $authenticationException = $componentContext->getParameter(SecurityEntryPointComponent::class, SecurityEntryPointComponent::AUTHENTICATION_EXCEPTION);
        if ($authenticationException === null) {
            return;
        }

        $actionRequest = $componentContext->getParameter(DispatchComponent::class, 'actionRequest');
        $entryPoint = array_reduce($this->securityContext->getAuthenticationTokens(), function ($foundEntryPoint, TokenInterface $currentToken) {
            return $foundEntryPoint ?? $currentToken->getAuthenticationEntryPoint();
        }, null);

        if ($entryPoint === null) {
            $this->securityLogger->notice('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.');
            throw $authenticationException;
        }

        $this->securityLogger->info(sprintf('Starting authentication with entry point of type "%s"', get_class($entryPoint)), LogEnvironment::fromMethodName(__METHOD__));
        $this->securityContext->setInterceptedRequest($actionRequest->getMainRequest());
        /** @var HttpResponse $response */
        $response = $entryPoint->startAuthentication($componentContext->getHttpRequest(), $componentContext->getHttpResponse());
        $componentContext->replaceHttpResponse($response);
    }
}
