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

final class BooleanBasedValueObject implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $value;

    /**
     * @param bool $value
     */
    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * @param bool $bool
     * @return self
     */
    public static function fromBool(bool $bool): self
    {
        return new self($bool);
    }

    /**
     * @return bool
     */
    public function getValue(): bool
    {
        return $this->value;
    }

    /**
     * @return boolean
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
