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
 * Change TYPO3\Flow\Persistence\Doctrine\DatabaseConnectionException to
 * TYPO3\Flow\Persistence\Doctrine\Exception\DatabaseConnectionException
 */
class Version201310031523 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace(
			'TYPO3\Flow\Persistence\Doctrine\DatabaseConnectionException',
			'TYPO3\Flow\Persistence\Doctrine\Exception\DatabaseConnectionException'
		);
	}

}
