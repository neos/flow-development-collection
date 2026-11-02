<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Frontend;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\PhpCapableBackendInterface;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the PHP source code cache frontend
 *
 */
final class PhpFrontendTest extends BaseTestCase
{
    #[Test]
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->onlyMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->willReturn(false);
        $cache->set('foo', 'bar');
    }

    #[Test]
    public function setPassesPhpSourceCodeTagsAndLifetimeToBackend()
    {
        $originalSourceCode = 'return "hello world!";';
        $modifiedSourceCode = '<?php ' . $originalSourceCode . chr(10) . '#';

        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    #[Test]
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->set('Foo-Bar', []);
    }

    #[Test]
    public function requireOnceCallsTheBackendsRequireOnceMethod()
    {
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->willReturn(('hello world!'));

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);

        $result = $cache->requireOnce('Foo-Bar');
        self::assertSame('hello world!', $result);
    }
}
