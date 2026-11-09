<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A readonly class with state which is meant to survive a serialization roundtrip.
 *
 * The transient property makes the proxy class builder add the serialization code, so that
 * __sleep() and __wakeup() are generated for this readonly class.
 */
readonly class ReadonlyClassWithSerializedState
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var array<int, string>
     */
    public array $tags;

    /**
     * @var string
     */
    #[Flow\Transient]
    public string $temporaryValue;

    /**
     * @param array<int, string> $tags
     */
    public function __construct(string $name = 'the name', array $tags = ['first', 'second'], string $temporaryValue = 'the temporary value')
    {
        $this->name = $name;
        $this->tags = $tags;
        $this->temporaryValue = $temporaryValue;
    }
}
