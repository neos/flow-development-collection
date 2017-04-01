<?php
namespace TYPO3\Flow\Security;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The repository for accounts
 *
 * @Flow\Scope("singleton")
 */
class AccountRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = 'TYPO3\Flow\Security\Account';

    /**
     * @var array
     */
    protected $defaultOrderings = array('creationDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING);

    /**
     * Returns the account for a specific authentication provider with the given identifier
     *
     * @param string $accountIdentifier The account identifier
     * @param string $authenticationProviderName The authentication provider name
     * @return \TYPO3\Flow\Security\Account
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
     * @return \TYPO3\Flow\Security\Account
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
