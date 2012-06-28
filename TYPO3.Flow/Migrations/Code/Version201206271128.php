<?php
namespace TYPO3\FLOW3\Core\Migrations;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Replace DataNotSerializeableException with DataNotSerializableException.
 */
class Version201206271128 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('Session\Exception\DataNotSerializeableException', 'Session\Exception\DataNotSerializableException');
	}

}

?>