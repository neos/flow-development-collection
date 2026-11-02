<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Laminas\Code\Reflection\MethodReflection;
use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for proxy methods which return by reference
 *
 * @see https://github.com/neos/flow-development-collection/issues/3590
 */
class ProxyMethodByReferenceTest extends UnitTestCase
{
    /**
     * Creates a class with a method returning by reference and one returning by value
     * and returns the name of that class.
     */
    protected function buildOriginalClass(): string
    {
        $className = 'ProxyMethodByReferenceFixture' . md5(uniqid((string)mt_rand(), true));
        eval('
            class ' . $className . ' {
                protected array $items = [\'initial\'];
                public function &itemsReference(): array { return $this->items; }
                public function items(): array { return $this->items; }
            }
        ');
        return $className;
    }

    #[Test]
    public function bodyCodeOfMethodReturningByReferenceAssignsTheParentCallByReference(): void
    {
        $className = $this->buildOriginalClass();
        $proxyMethod = ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection($className, 'itemsReference'));
        $proxyMethod->addPreParentCallCode('// pre parent call');
        $proxyMethod->addPostParentCallCode('// post parent call');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$result = &parent::itemsReference();', $bodyCode);
        self::assertStringContainsString('return $result;', $bodyCode);
    }

    #[Test]
    public function bodyCodeOfMethodReturningByValueAssignsTheParentCallByValue(): void
    {
        $className = $this->buildOriginalClass();
        $proxyMethod = ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection($className, 'items'));
        $proxyMethod->addPreParentCallCode('// pre parent call');
        $proxyMethod->addPostParentCallCode('// post parent call');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('$result = parent::items();', $bodyCode);
        self::assertStringNotContainsString('&parent::', $bodyCode);
    }

    /**
     * If only pre parent call code exists, the parent call is returned directly – which
     * preserves the reference without any further measures.
     */
    #[Test]
    public function bodyCodeOfMethodReturningByReferenceWithOnlyPreParentCallCodeReturnsTheParentCallDirectly(): void
    {
        $className = $this->buildOriginalClass();
        $proxyMethod = ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection($className, 'itemsReference'));
        $proxyMethod->addPreParentCallCode('// pre parent call');

        $bodyCode = $proxyMethod->renderBodyCode();

        self::assertStringContainsString('return parent::itemsReference();', $bodyCode);
        self::assertStringNotContainsString('$result', $bodyCode);
    }

    #[Test]
    public function generatedProxyMethodPreservesTheReferenceReturnedByTheOriginalMethod(): void
    {
        $originalClassName = $this->buildOriginalClass();
        $proxyMethod = ProxyMethodGenerator::copyMethodSignatureAndDocblock(new MethodReflection($originalClassName, 'itemsReference'));
        $proxyMethod->addPreParentCallCode('// pre parent call');
        $proxyMethod->addPostParentCallCode('// post parent call');

        $proxyClassName = $originalClassName . '_Proxy';
        eval('class ' . $proxyClassName . ' extends ' . $originalClassName . ' {' . $proxyMethod->generate() . '}');

        $proxy = new $proxyClassName();
        $items = &$proxy->itemsReference();
        $items[] = 'added via reference';

        self::assertSame(['initial', 'added via reference'], $proxy->items());
    }
}
