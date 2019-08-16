<?php
namespace Neos\Flow\Http\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Psr\Http\Message\ResponseInterface;
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
        $firstTokenWithEntryPoint = $this->getFirstTokenWithEntryPoint();
        if ($firstTokenWithEntryPoint === null) {
            $this->securityLogger->notice('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.');
            throw $authenticationException;
        }

        $entryPoint = $firstTokenWithEntryPoint->getAuthenticationEntryPoint();
        $this->securityLogger->info(sprintf('Starting authentication with entry point of type "%s"', get_class($entryPoint)), LogEnvironment::fromMethodName(__METHOD__));

        // TODO: We should only prevent storage of intercepted request in the session here, but we don't have a different storage mechanism yet.
        if (!$firstTokenWithEntryPoint instanceof SessionlessTokenInterface) {
            $this->securityContext->setInterceptedRequest($actionRequest->getMainRequest());
        }

        /** @var ResponseInterface $response */
        $response = $entryPoint->startAuthentication($componentContext->getHttpRequest(), $componentContext->getHttpResponse());
        $componentContext->replaceHttpResponse($response);
    }

    /**
     * Returns the first authenticated token that has an Authentication Entry Point configured, or NULL if none exists
     *
     * @return TokenInterface|null
     */
    private function getFirstTokenWithEntryPoint(): ?TokenInterface
    {
        foreach ($this->securityContext->getAuthenticationTokens() as $token) {
            if ($token->getAuthenticationEntryPoint() !== null) {
                return $token;
            }
        }
    }
}
