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
 * Rename FLOW3 to TYPO3 Flow
 */
class Version20120925142600 extends AbstractMigration {

	/**
	 * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
	 * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
	 * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.Flow-201209251426';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3.FLOW3', 'TYPO3.Flow');
		$this->searchAndReplace('TYPO3\FLOW3', 'TYPO3\Flow');

		$this->searchAndReplace('FLOW3_PATH_FLOW3', 'FLOW_PATH_FLOW');
		$this->searchAndReplace('FLOW3_PATH', 'FLOW_PATH');
		$this->searchAndReplace('FLOW3_ROOTPATH', 'FLOW_ROOTPATH');
		$this->searchAndReplace('FLOW3_CONTEXT', 'FLOW_CONTEXT');
		$this->searchAndReplace('FLOW3_SAPITYPE', 'FLOW_SAPITYPE');
		$this->searchAndReplace('FLOW3_WEBPATH', 'FLOW_WEBPATH');

		$this->searchAndReplace('as FLOW3;', 'as Flow;');
		$this->searchAndReplace('@FLOW3\\', '@Flow\\');

		$this->searchAndReplace('typo3/flow3', 'typo3/flow', array('json'));

		$this->showNote('You should check the changes this migration applied. Feel free to beautify the file docblock headers and make sure to check for leftover "FLOW3" and "flow3" use.');
		$this->showWarning('In schema migrations that existed prior to this, do not replace "flow3" by "flow"!');
	}

}
