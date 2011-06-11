<?php
namespace F3\FLOW3\Persistence\Doctrine;

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
 * DB session initialization handler for Doctrine integration
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License', version 3 or later
 */
class SessionInitializationHandler implements \Doctrine\Common\EventSubscriber {

	/**
	 * @var string
	 */
	protected $initializationSql;

	/**
	 * Configure SQL to run for each connection
	 *
	 * @param string $initializationSql
	 */
	public function __construct($initializationSql) {
		$this->initializationSql = $initializationSql;
	}

	/**
	 * @param \Doctrine\DBAL\Event\ConnectionEventArgs $arguments
	 * @return void
	 */
	public function postConnect(\Doctrine\DBAL\Event\ConnectionEventArgs $arguments) {
		$arguments->getConnection()->executeUpdate($this->initializationSql);
	}

	/**
	 * @return array
	 */
	public function getSubscribedEvents() {
		return array(\Doctrine\DBAL\Events::postConnect);
	}
}

?>