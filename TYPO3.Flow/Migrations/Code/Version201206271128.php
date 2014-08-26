<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Replace DataNotSerializeableException with DataNotSerializableException.
 */
class Version201206271128 extends AbstractMigration {

	/**
	 * Returns the identifier of this migration.
	 *
	 * Hardcoded to be stable after the rename to TYPO3 Flow.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.FLOW3-201206271128';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('Session\Exception\DataNotSerializeableException', 'Session\Exception\DataNotSerializableException');
	}

}
