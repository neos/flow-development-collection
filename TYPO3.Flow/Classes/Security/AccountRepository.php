<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The repository for accounts
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AccountRepository extends \F3\FLOW3\Persistence\Repository {

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('creationDate' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING);

	/**
	 * Constructs the Account Repository
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		parent::__construct();
		$this->objectType = 'F3\FLOW3\Security\Account';
	}

	/**
	 * Returns the account for a specific authentication provider with the given identitifer
	 *
	 * @param string $accountIdentifier The account identifier
	 * @param string $authenticationProviderName The authentication provider name
	 * @return F3\FLOW3\Security\Account
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * Returns the account for a specific authentication provider with the given identitifer if it's not expired
	 *
	 * @param string $accountIdentifier The account identifier
	 * @param string $authenticationProviderName The authentication provider name
	 * @return F3\FLOW3\Security\Account
	 * @author Bastian Waidelich <bastian@typo3.org>
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

?>
