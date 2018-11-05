<?php
namespace Neos\Flow\Security;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\RequestInterface;

/**
 * @Flow\Scope("session")
 * @internal
 */
class SessionDataContainer
{
    /**
     * @var array
     */
    protected $securityTokens = [];

    /**
     * @var array
     */
    protected $csrfProtectionTokens = [];

    /**
     * @var RequestInterface|null
     */
    protected $interceptedRequest;

    /**
     * @return array
     */
    public function getSecurityTokens(): array
    {
        return $this->securityTokens;
    }

    /**
     * @param array $securityTokens
     */
    public function setSecurityTokens(array $securityTokens)
    {
        $this->securityTokens = $securityTokens;
    }

    /**
     * @return array
     */
    public function getCsrfProtectionTokens(): array
    {
        return $this->csrfProtectionTokens;
    }

    /**
     * @param array $csrfProtectionTokens
     */
    public function setCsrfProtectionTokens(array $csrfProtectionTokens)
    {
        $this->csrfProtectionTokens = $csrfProtectionTokens;
    }

    /**
     * @return RequestInterface
     */
    public function getInterceptedRequest():? RequestInterface
    {
        return $this->interceptedRequest;
    }

    /**
     * @param RequestInterface $interceptedRequest
     */
    public function setInterceptedRequest(RequestInterface $interceptedRequest = null)
    {
        $this->interceptedRequest = $interceptedRequest;
    }
}
