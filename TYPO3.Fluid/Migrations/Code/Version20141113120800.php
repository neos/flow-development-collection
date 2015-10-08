<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Rename the "resource" argument of the security.ifAccess ViewHelper to "privilegeTarget"
 */
class Version20141113120800 extends AbstractMigration
{
    public function up()
    {
        $this->searchAndReplaceRegex('/\<f\:security\.ifAccess\s+(resource=)/', '<f:security.ifAccess privilegeTarget=', array('html'));
        $this->searchAndReplaceRegex('/\{f\:security\.ifAccess\s*\(\s*(resource:)/', '{f:security.ifAccess(privilegeTarget:', array('html'));
    }
}
