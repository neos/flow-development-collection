<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjust DB migrations to Doctrine Migrations 3.0
 *
 * - use Doctrine\Migrations\AbstractMigration instead of Doctrine\DBAL\Migrations\AbstractMigration
 * - adjust method signatures
 */
class Version20201109224100 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20201109224100';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Doctrine\DBAL\Migrations\AbstractMigration', 'Doctrine\Migrations\AbstractMigration', ['php']);
        $this->searchAndReplaceRegex('/public function getDescription\(\)(\s*\{)?$/m', 'public function getDescription(): string $1', ['php']);
        $this->searchAndReplaceRegex('/public function (up|down|preUp|postUp|preDown|postDown)\(Schema \$schema\)(\s*\{)?$/m', 'public function $1(Schema \$schema): void $2', ['php']);
    }
}
