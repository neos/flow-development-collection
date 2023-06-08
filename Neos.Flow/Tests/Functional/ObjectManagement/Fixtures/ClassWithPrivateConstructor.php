<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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

class ClassWithPrivateConstructor extends AbstractClassWithFactoryMethod
{
    #[Flow\Inject(lazy: false)]
    public SingletonClassA $dependency;

    private function __construct(public string $constructorArgument, readonly public PrototypeClassA $anotherDependency)
    {
    }

    public static function createInParentClass(string $constructorArgument, PrototypeClassA $anotherDependency): static
    {
        return new static($constructorArgument, $anotherDependency);
    }

    public static function createUsingSelf(string $constructorArgument, PrototypeClassA $anotherDependency): self
    {
        return new self($constructorArgument, $anotherDependency);
    }

    public function getStringContainingALotOfSelves(): string
    {
        return <<<PHP
            new self();
            self::class;
            self::create();
            function foo(self \$self): self {
                return \$self;
            }
        PHP;
    }
}
