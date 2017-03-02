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
 * Rename form.textbox to form.textfield
 */
class Version20120503130300 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Fluid-201205031303';
    }

    public function up()
    {
        $this->searchAndReplace('form.textbox', 'form.textfield', array('html'));

        $this->showNote('Widget configuration has changed, you might want to add "widgetId" attributes to your widget inclusions in Fluid templates. Adjust Routes.yaml as needed!');
    }
}
