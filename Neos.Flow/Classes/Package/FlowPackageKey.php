<?php

declare(strict_types=1);

namespace Neos\Flow\Package;

use Neos\Flow\Annotations as Flow;

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
            throw new \InvalidArgumentException(sprintf('Not a valid Flow package key: "%s"', $value), 1711659821);
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

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
