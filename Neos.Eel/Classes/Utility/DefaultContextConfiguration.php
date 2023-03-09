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
class DefaultContextConfiguration
{
    private function __construct(
        private array $configuration
    ) {
    }

    public static function fromConfiguration(array $configuration)
    {
        unset($configuration["__internalLegacyConfig"]);
        return new self($configuration);
    }

    /**
     * @return \Iterator<EelHelperDefaultContextEntry|EelFunctionDefaultContextEntry>
     */
    public function toDefaultContextEntries(): \Iterator
    {
        $existingKeysWithEntry = [];
        foreach (self::createDefaultContextEntries([], $this->configuration) as $defaultContextEntry) {
            $dotPath = join(".", $defaultContextEntry->paths);
            foreach ($existingKeysWithEntry as $existingKey) {
                if ($existingKey === $dotPath) {
                    // we allow overriding another path with another syntax
                    continue;
                }
                if (str_starts_with($dotPath, $existingKey)) {
                    throw new \DomainException("Cannot use namespace '$existingKey' as helper with nested helpers.");
                }
            }
            $existingKeysWithEntry[] = $dotPath;
            yield $defaultContextEntry;
        };
    }

    /**
     * @return \Iterator<EelHelperDefaultContextEntry|EelFunctionDefaultContextEntry>
     */
    private static function createDefaultContextEntries(array $paths, array|string $configuration): \Iterator
    {
        switch (true) {
            case $paths === []:
                // on root level, we allow dots inside the Namespace
                assert(is_array($configuration));
                foreach ($configuration as $subPath => $value) {
                    if (str_contains($subPath, ".")) {
                        $subPath = explode('.', $subPath);
                        yield from self::createDefaultContextEntries($subPath, $value);
                        continue;
                    }
                    yield from self::createDefaultContextEntries([$subPath], $value);
                }
                break;

            case is_array($configuration) && isset($configuration["className"]):
                yield EelHelperDefaultContextEntry::fromConfiguration(
                    $paths, $configuration
                );
                break;

            case is_array($configuration):
                foreach ($configuration as $subPath => $value) {
                    yield from self::createDefaultContextEntries([...$paths, $subPath], $value);
                }
                break;

            case str_contains($configuration, '::'):
                yield new EelFunctionDefaultContextEntry($paths, $configuration);
                break;

            default:
                yield new EelHelperDefaultContextEntry(
                    $paths, $configuration, []
                );
        }
    }
}
