<?php
namespace TYPO3\Flow\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Flow\Session\SessionManagerInterface;

/**
 * The repository for accounts
 *
 * @Flow\Scope("singleton")
 */
class AccountRepository extends Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\Flow\Security\Account';

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('creationDate' => QueryInterface::ORDER_DESCENDING);

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
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function remove($object) {
		parent::remove($object);
		/** @var Account $object */
		$tag = 'TYPO3-Flow-Security-Account-' . md5($object->getAccountIdentifier());
		$this->sessionManager->destroySessionsByTag($tag, sprintf('The account %s (%s) was deleted', $object->getAccountIdentifier(), $object->getAuthenticationProviderName()));
	}

	/**
	 * Returns the account for a specific authentication provider with the given identifier
	 *
	 * @param string $accountIdentifier The account identifier
	 * @param string $authenticationProviderName The authentication provider name
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function findByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName) {
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
	public function findActiveByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('accountIdentifier', $accountIdentifier),
				$query->equals('authenticationProviderName', $authenticationProviderName),
				$query->logicalOr(
					$query->equals('expirationDate', NULL),
					$query->greaterThan('expirationDate', new \DateTime())
				)
			)
		)->execute()->getFirst();
	}
}
