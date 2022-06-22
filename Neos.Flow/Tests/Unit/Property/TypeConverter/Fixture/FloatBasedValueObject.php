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

final class FloatBasedValueObject implements \JsonSerializable
{
    /**
     * @var float
     */
    private $value;

    /**
     * @param float $value
     */
    private function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * @param float $float
     * @return self
     */
    public static function fromFloat(float $float): self
    {
        return new self($float);
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
