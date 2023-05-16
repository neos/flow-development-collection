<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassToBeSerialized;

/**
 * A class with PHP 8 type hints with union types
 * @Flow\Scope("prototype")
 */
class ClassWithUnionTypes
{
    protected ?string $propertyA;

    /* This should be fully equal to $propertyA */
    protected string|null $propertyB;

    /* PHP8 allows `false` type to denote a boolean that can only be false */
    protected string|false $propertyC;

    protected ClassToBeSerialized|string|null $propertyD;

    protected int|float|string|null $propertyE;

    public function getPropertyA(): ?string
    {
        return $this->propertyA;
    }

    public function setPropertyA(?string $propertyA): void
    {
        $this->propertyA = $propertyA;
    }

    public function getPropertyB(): string|null
    {
        return $this->propertyB;
    }

    public function setPropertyB(string|null $propertyB): void
    {
        $this->propertyB = $propertyB;
    }

    public function getPropertyC(): string|false
    {
        return $this->propertyC;
    }

    public function setPropertyC(string|false $propertyC): void
    {
        $this->propertyC = $propertyC;
    }

    public function getPropertyD(): string|ClassToBeSerialized|null
    {
        return $this->propertyD;
    }

    public function setPropertyD(string|ClassToBeSerialized|null $propertyD): void
    {
        $this->propertyD = $propertyD;
    }

    public function getPropertyE(): float|int|string|null
    {
        return $this->propertyE;
    }

    public function setPropertyE(float|int|string|null $propertyE): void
    {
        $this->propertyE = $propertyE;
    }
}
