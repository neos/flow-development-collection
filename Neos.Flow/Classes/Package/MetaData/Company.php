<?php
namespace Neos\Flow\Package\MetaData;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\MetaDataInterface;

/**
 * Package company party meta model
 *
 */
class Company extends AbstractParty
{
    /**
     * Get the party type
     *
     * @return string Party type "company"
     */
    public function getPartyType()
    {
        return MetaDataInterface::PARTY_TYPE_COMPANY;
    }
}
