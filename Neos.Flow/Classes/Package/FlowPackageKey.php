<?php

declare(strict_types=1);

namespace Neos\Flow\Package;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Composer\ComposerUtility;

/**
 * (Legacy) Flow representation of a package key.
 *
 * With the rise of composer each package _has_ a key like "vendor/foo-bar".
 * But before the adaption Flow already established the package keys like "Vendor.Foo.Bar",
 * which is represented and validated by this value object.
 *
 * The Flow package keys are currently inferred from the composer manifest {@see FlowPackageKey::getPackageKeyFromManifest()},
 * and can also be tried to be reverse calculated: {@see FlowPackageKey::guessComposerPackageName()}
 *
 * The idea around the Flow package key is obsolete since composer and will eventually be replaced.
 * Still major parts of Flow depend on the concept.
 *
 * @internal Only meant to be used inside the Flow core until replaced by composer keys.
 */
final readonly class FlowPackageKey implements \JsonSerializable
{
    public const PATTERN = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';

    /**
     * @Flow\Autowiring(false)
     */
    private function __construct(
        public string $value
    ) {
        if (!self::isPackageKeyValid($value)) {
            throw new Exception\InvalidPackageKeyException('The package key "' . $value . '" is invalid', 1220722210);
        }
    }

    public static function fromString(string $value)
    {
        return new self($value);
    }

    /**
     * Check the conformance of the given package key
     *
     * @param string $string The package key to validate
     * @return boolean If the package key is valid, returns true otherwise false
     */
    public static function isPackageKeyValid(string $string): bool
    {
        return preg_match(self::PATTERN, $string) === 1;
    }

    /**
     * Resolves package key from Composer manifest
     *
     * If it is a Flow package the name of the containing directory will be used.
     *
     * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
     * case version of the composer name / namespace will be used, with backslashes replaced by dots.
     *
     * Else the composer name will be used with the slash replaced by a dot
     */
    public static function getPackageKeyFromManifest(array $manifest, string $packagePath): self
    {
        $definedFlowPackageKey = $manifest['extra']['neos']['package-key'] ?? null;

        if ($definedFlowPackageKey && self::isPackageKeyValid($definedFlowPackageKey)) {
            return new self($definedFlowPackageKey);
        }

        $composerName = $manifest['name'];
        $autoloadNamespace = null;
        $type = null;
        if (isset($manifest['autoload']['psr-0']) && is_array($manifest['autoload']['psr-0'])) {
            $namespaces = array_keys($manifest['autoload']['psr-0']);
            $autoloadNamespace = reset($namespaces);
        }

        if (isset($manifest['type'])) {
            $type = $manifest['type'];
        }

        return self::derivePackageKey($composerName, $type, $packagePath, $autoloadNamespace);
    }

    /**
     * Derive a flow package key from the given information.
     * The order of importance is:
     *
     * - package install path
     * - first found autoload namespace
     * - composer name
     */
    private static function derivePackageKey(string $composerName, ?string $packageType, string $packagePath, ?string $autoloadNamespace): self
    {
        $packageKey = '';

        if ($packageType !== null && ComposerUtility::isFlowPackageType($packageType)) {
            $lastSegmentOfPackagePath = substr(trim($packagePath, '/'), strrpos(trim($packagePath, '/'), '/') + 1);
            if (str_contains($lastSegmentOfPackagePath, '.')) {
                $packageKey = $lastSegmentOfPackagePath;
            }
        }

        if ($autoloadNamespace !== null && (self::isPackageKeyValid($packageKey) === false)) {
            $packageKey = str_replace('\\', '.', $autoloadNamespace);
        }

        if (self::isPackageKeyValid($packageKey) === false) {
            $packageKey = str_replace('/', '.', $composerName);
        }

        $packageKey = trim($packageKey, '.');
        $packageKey = preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);

        return new self($packageKey);
    }

    /**
     * Determines the composer package name ("vendor/foo-bar") from the Flow package key ("Vendor.Foo.Bar")
     */
    public function guessComposerPackageName(): string
    {
        $nameParts = explode('.', $this->value);
        $vendor = array_shift($nameParts);
        return strtolower($vendor . '/' . implode('-', $nameParts));
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
