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

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @internal
 * @Flow\Proxy(false)
 */
class EelHelperDefaultContextEntry
{
    private object $instance;

    /**
     * @param array $paths like ["Vendor", "Array"] or just ["Array"]
     * @param class-string $className the EEL helper className
     * @param array $allowedMethods configuration which methods of the Helper are allowed, if ProtectedContextAwareInterface is not used like "*" or "join"
     */
    public function __construct(
        /** @psalm-readonly */
        public array $paths,
        /** @psalm-readonly */
        string $className,
        /** @psalm-readonly */
        private array $allowedMethods
    ) {
        $this->instance = new $className;
        $isHelperContextAware = $this->instance instanceof ProtectedContextAwareInterface;
        if ($isHelperContextAware && $allowedMethods !== []) {
            throw new \DomainException(
                "EEL Helper '$className' should not implement ProtectedContextAwareInterface and have allowedMethods configured.",
                1678353296292
            );
        }
        if (!$isHelperContextAware && $allowedMethods === []) {
            throw new \DomainException(
                "Plain Helper '$className' should have allowedMethods or ProtectedContextAwareInterface configured.",
                1678353436756
            );
        }
        foreach ($paths as $path) {
            if (str_contains($path, ".")) {
                throw new \DomainException("Path should not contain dots", 1678365574434);
            }
        }
        foreach ($allowedMethods as $allowedMethod) {
            if (!ctype_alnum($allowedMethod) && $allowedMethod !== "*") {
                throw new \DomainException(
                    sprintf(
                        "Allowed methods may only contain '*' or a simple method name got: %s",
                        json_encode($allowedMethod)
                    ), 1678396197768
                );
            }
        }
    }

    public static function fromConfiguration(array $paths, array $configuration): self
    {
        assert($configuration["className"]);
        $configuration["allowedMethods"] ??= [];
        if (\count($configuration) !== 2) {
            throw new \DomainException(sprintf("Cannot use namespace '%s' as helper with nested helpers.", join(".", $paths)));
        }

        $allowedMethods = $configuration["allowedMethods"];
        if (is_string($allowedMethods)) {
            $allowedMethods = [$allowedMethods];
        }
        return new self(
            $paths,
            $configuration["className"],
            $allowedMethods
        );
    }

    public function toContextValue(): object
    {
        return $this->instance;
    }

    public function getAllowedMethods(): array
    {
        return array_map(
            fn(string $allowedMethod) => join(".", [...$this->paths, $allowedMethod]),
            $this->allowedMethods
        );
    }
}
