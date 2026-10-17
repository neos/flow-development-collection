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
use Neos\Utility\Arrays;

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

    /**
     * @param array{string: class-string|string|array{"className": class-string, "allowedMethods"?: string}} $configuration
     */
    public static function fromConfiguration(array $configuration)
    {
        unset($configuration["__internalLegacyConfig"]);
        return new self(self::normalizeFirstLevelDotPathsIntoNestedConfig($configuration));
    }

    public function union(DefaultContextConfiguration $other): self
    {
        return new self(
            Arrays::arrayMergeRecursiveOverrule(
                $this->configuration,
                $other->configuration
            )
        );
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return \Iterator<DefaultContextEntry>|DefaultContextEntry[]
     */
    public function toDefaultContextEntries(): \Iterator
    {
        return self::createDefaultContextEntries([], $this->configuration);
    }

    /**s
     * @param string[] $path
     * @return \Iterator<DefaultContextEntry>|DefaultContextEntry[]
     */
    private static function createDefaultContextEntries(array $path, array|string $configuration): \Iterator
    {
        switch (true) {
            case is_array($configuration) && isset($configuration["className"]):
                $configuration["allowedMethods"] ??= [];
                if (\count($configuration) !== 2) {
                    throw new \DomainException(
                        sprintf("Cannot use namespace '%s' as helper with nested helpers.", join(".", $path))
                    );
                }
                yield EelHelperDefaultContextEntry::fromConfiguration(
                    $path,
                    $configuration
                );
                break;

            case is_array($configuration):
                foreach ($configuration as $subPath => $value) {
                    yield from self::createDefaultContextEntries([...$path, $subPath], $value);
                }
                break;

            case str_contains($configuration, '::'):
                yield new EelFunctionDefaultContextEntry($path, $configuration);
                break;

            default:
                yield new EelHelperDefaultContextEntry($path, $configuration, []);
        }
    }

    /**
     * ["Foo.Bar" => Helper::class] becomes ["Foo" => ["Bar" => Helper::class]]
     */
    public static function normalizeFirstLevelDotPathsIntoNestedConfig(array $configuration): array
    {
        foreach ($configuration as $path => $pathConfiguration) {
            if (str_contains($path, ".") === false) {
                continue;
            }
            $currentPathBase = &$configuration;
            $pathSegments = explode('.', $path);
            unset($configuration[$path]);
            foreach ($pathSegments as $pathSegment) {
                if (!isset($currentPathBase[$pathSegment])) {
                    $currentPathBase[$pathSegment] = [];
                }
                $currentPathBase = &$currentPathBase[$pathSegment];
            }
            $currentPathBase = $pathConfiguration;
        }
        return $configuration;
    }
}
