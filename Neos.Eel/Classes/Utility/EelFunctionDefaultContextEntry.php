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

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 * @Flow\Proxy(false)
 */
class EelFunctionDefaultContextEntry implements DefaultContextEntry
{
    public function __construct(
        private array $path,
        private string $classNameAndStaticFactory
    ) {
        assert(is_callable($classNameAndStaticFactory));
        // Allow functions on the uppermost context level to allow calling them without
        // implementing ProtectedContextAwareInterface which is impossible for functions
        if (\count($path) !== 1) {
            throw new \DomainException(
                sprintf('Function helpers are only allowed on root level, "%s" was given', join(".", $path)),
                1557911015
            );
        }
        foreach ($path as $path) {
            // currently check is not in use but in case we remove the count above wed need it
            if (str_contains($path, ".")) {
                throw new \DomainException("Path should not contain dots", 1678365547490);
            }
        }
    }

    public function toContextValue(): \Closure
    {
        return \Closure::fromCallable($this->classNameAndStaticFactory);
    }

    public function getAllowedMethods(): array
    {
        return [$this->path];
    }

    public function getPath(): array
    {
        return $this->path;
    }
}
