<?php
namespace Neos\Flow\Security;

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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Session\SessionManagerInterface;

/**
 * The repository for accounts
 *
 * @Flow\Scope("singleton")
 */
class AccountRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = Account::class;

    /**
     * @var array
     */
    protected $defaultOrderings = ['creationDate' => QueryInterface::ORDER_DESCENDING];

    /**
     * @Flow\Inject
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Removes an account
     *
     * @param object $object The account to remove
     * @return void
     * @throws IllegalObjectTypeException
     */
    public function remove($object)
    {
        parent::remove($object);
        /** @var Account $object */
        $tag = 'Neos-Flow-Security-Account-' . md5($object->getAccountIdentifier());
        $this->sessionManager->destroySessionsByTag($tag, sprintf('The account %s (%s) was deleted', $object->getAccountIdentifier(), $object->getAuthenticationProviderName()));
    }

    /**
     * Returns the account for a specific authentication provider with the given identifier
     *
     * @param string $accountIdentifier The account identifier
     * @param string $authenticationProviderName The authentication provider name
     * @return Account
     */
    public function findByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName)
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->logicalAnd(
                $query->equals('accountIdentifier', $accountIdentifier),
                $query->equals('authenticationProviderName', $authenticationProviderName)
            )
        )->execute()->getFirst();
    }

    /**
     * Returns the account for a specific authentication provider with the given identifier if it's not expired
     *
     * @param string $accountIdentifier The account identifier
     * @param string $authenticationProviderName The authentication provider name
     * @return Account
     */
    public function findActiveByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName)
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->logicalAnd(
                $query->equals('accountIdentifier', $accountIdentifier),
                $query->equals('authenticationProviderName', $authenticationProviderName),
                $query->logicalOr(
                    $query->equals('expirationDate', null),
                    $query->greaterThan('expirationDate', new \DateTime())
                )
            )
        )->execute()->getFirst();
    }
}
