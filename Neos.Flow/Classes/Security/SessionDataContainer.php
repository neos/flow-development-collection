<?php
namespace Neos\Flow\Security;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;

/**
 * @Flow\Scope("session")
 * @internal
 */
class SessionDataContainer
{
    /**
     * The current list of security tokens.
     *
     * @var array
     */
    protected $securityTokens = [];

    /**
     * The current list of CSRF tokens
     *
     * @var array
     */
    protected $csrfProtectionTokens = [];

    /**
     * A possible request that was intercepted on a security exception
     *
     * @var ActionRequest|null
     */
    protected $interceptedRequest;

    /**
     * Get the current list of security tokens.
     *
     * @return array
     */
    public function getSecurityTokens(): array
    {
        return $this->securityTokens;
    }

    /**
     * Set the current list of security tokens with their data.
     *
     * @param array $securityTokens
     */
    public function setSecurityTokens(array $securityTokens)
    {
        foreach ($securityTokens as $token) {
            if ($token instanceof SessionlessTokenInterface) {
                throw new \InvalidArgumentException(sprintf('Tokens implementing the SessionlessTokenInterface must not be stored in the session. Got: %s', get_class($token)), 1562670555);
            }
        }
        $this->securityTokens = $securityTokens;
    }

    /**
     * Get the current list of active CSRF tokens.
     *
     * @return array
     */
    public function getCsrfProtectionTokens(): array
    {
        return $this->csrfProtectionTokens;
    }

    /**
     * set the list of currently active CSRF tokens.
     *
     * @param array $csrfProtectionTokens
     */
    public function setCsrfProtectionTokens(array $csrfProtectionTokens)
    {
        $this->csrfProtectionTokens = $csrfProtectionTokens;
    }

    /**
     * Get a possible saved request after a security exceptoin happeened.
     *
     * @return ActionRequest
     */
    public function getInterceptedRequest(): ?ActionRequest
    {
        return $this->interceptedRequest;
    }

    /**
     * Save a request that triggered a security exception.
     *
     * @param ActionRequest $interceptedRequest
     */
    public function setInterceptedRequest(ActionRequest $interceptedRequest = null): void
    {
        $this->interceptedRequest = $interceptedRequest;
    }

    /**
     * Reset data in this session container.
     */
    public function reset(): void
    {
        $this->setSecurityTokens([]);
        $this->setCsrfProtectionTokens([]);
        $this->setInterceptedRequest(null);
    }
}
