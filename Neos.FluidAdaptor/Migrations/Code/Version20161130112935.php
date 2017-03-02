<?php

namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjusts code to package renaming from "TYPO3.Fluid" to "Neos.FluidAdaptor".
 *
 */
class Version20161130112935 extends AbstractMigration
{
    public function getIdentifier()
    {
        return 'TYPO3.FluidAdaptor-20161130112935';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Fluid', 'Neos\FluidAdaptor');
        $this->searchAndReplace('TYPO3.Fluid', 'Neos.FluidAdaptor');
    }
}
