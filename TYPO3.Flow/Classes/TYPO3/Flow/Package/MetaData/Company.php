<?php
namespace TYPO3\FLOW3\Package\MetaData;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Package company party meta model
 *
 */
class Company extends \TYPO3\FLOW3\Package\MetaData\AbstractParty {

	/**
	 * Get the party type
	 *
	 * @return string Party type "company"
	 */
	public function getPartyType() {
		return \TYPO3\FLOW3\Package\MetaDataInterface::PARTY_TYPE_COMPANY;
	}
}
?>