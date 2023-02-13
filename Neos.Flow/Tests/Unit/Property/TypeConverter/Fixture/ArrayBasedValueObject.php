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

final class ArrayBasedValueObject implements \JsonSerializable
{
    /**
     * @var array
     */
    private $value;

    /**
     * @param array $value
     */
    private function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
