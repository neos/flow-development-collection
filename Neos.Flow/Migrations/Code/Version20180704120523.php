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
 * Add scalar type hint to allowsCallOfMethod implementations
 */
class Version20180704120523 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'Neos.Flow-20180704120523';
    }

    /**
     * @return void
     */
    public function up(): void
    {
        $this->searchAndReplace('public function allowsCallOfMethod($methodName)', 'public function allowsCallOfMethod(string $methodName): bool', ['php']);
    }
}
