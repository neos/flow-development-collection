<?php

declare(strict_types=1);

namespace Neos\Eel\Utility;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

interface DefaultContextEntry
{
    /**
     * An array of path segments of the context value
     * @return non-empty-list<string>
     */
    public function getPath(): array;

    /**
     * A helper class instance or a closure to be assigned to the path
     */
    public function toContextValue(): object;

    /**
     * An array of allowed method paths
     *
     * EXAMPLE:
     *
     *      []
     *      [["Array", "join"]
     *      [["*"]]
     *
     * @return list<non-empty-list<string>>
     */
    public function getAllowedMethods(): array;
}
