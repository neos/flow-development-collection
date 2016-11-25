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
 * Rename the "resource" argument of the security.ifAccess ViewHelper to "privilegeTarget"
 */
class Version20141113120800 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'TYPO3.Fluid-20141113120800';
    }

    public function up()
    {
        $this->searchAndReplaceRegex('/\<f\:security\.ifAccess\s+(resource=)/', '<f:security.ifAccess privilegeTarget=', array('html'));
        $this->searchAndReplaceRegex('/\{f\:security\.ifAccess\s*\(\s*(resource:)/', '{f:security.ifAccess(privilegeTarget:', array('html'));
    }
}
