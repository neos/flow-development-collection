<?php
declare(strict_types=1);

namespace Neos\Flow\Security\Authentication\Token;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;

/**
 * rfc6750 Bearer token (https://tools.ietf.org/html/rfc6750)
 *
 * "A security token with the property that any party in possession of the token (a "bearer")
 * can use the token in any way that any other party in possession of it can. Using a bearer
 * token does not require a bearer to prove possession of cryptographic key material (proof-of-possession)."
 */
class BearerToken extends AbstractToken implements SessionlessTokenInterface
{

    /**
     * The password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = ['bearer' => ''];

    /**
     * @param ActionRequest $actionRequest
     * @throws AuthenticationRequiredException
     * @throws InvalidAuthenticationStatusException
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        $httpRequest = $actionRequest->getHttpRequest();

        if (!$httpRequest->hasHeader('Authorization')) {
            return;
        }

        $this->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);

        foreach ($httpRequest->getHeader('Authorization') as $authorizationHeader) {
            if (strpos($authorizationHeader, 'Bearer ') === 0) {
                $this->credentials['bearer'] = substr($authorizationHeader, strlen('Bearer '));
                $this->setAuthenticationStatus(TokenInterface::AUTHENTICATION_NEEDED);
                return;
            }
        }
    }

    /**
     * @return string
     */
    public function getBearer(): string
    {
        return $this->credentials['bearer'];
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Bearer token';
    }
}
