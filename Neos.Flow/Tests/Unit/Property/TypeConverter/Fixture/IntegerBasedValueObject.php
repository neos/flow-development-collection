<?php
namespace Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class IntegerBasedValueObject implements \JsonSerializable
{
    /**
     * @var int
     */
    private $value;

    /**
     * @param int $value
     */
    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @param int $int
     * @return self
     */
    public static function fromInt(int $int): self
    {
        return new self($int);
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
