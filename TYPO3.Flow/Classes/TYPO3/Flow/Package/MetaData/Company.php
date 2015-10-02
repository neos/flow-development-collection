<?php
namespace TYPO3\Flow\Package\MetaData;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Package company party meta model
 *
 */
class Company extends \TYPO3\Flow\Package\MetaData\AbstractParty
{
    /**
     * Get the party type
     *
     * @return string Party type "company"
     */
    public function getPartyType()
    {
        return \TYPO3\Flow\Package\MetaDataInterface::PARTY_TYPE_COMPANY;
    }
}
