<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
