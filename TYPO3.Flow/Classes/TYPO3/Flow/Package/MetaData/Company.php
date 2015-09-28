<?php
namespace TYPO3\Flow\Package\MetaData;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


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
