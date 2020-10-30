<?php
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

use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Backend\PhpCapableBackendInterface;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\StringFrontend;

/**
 * Testcase for the PHP source code cache frontend
 *
 */
class PhpFrontendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects(self::once())->method('isValidEntryIdentifier')->with('foo')->will(self::returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesPhpSourceCodeTagsAndLifetimeToBackend()
    {
        $originalSourceCode = 'return "hello world!";';
        $modifiedSourceCode = '<?php ' . $originalSourceCode . chr(10) . '#';

        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects(self::once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    /**
     * @test
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $cache->set('Foo-Bar', []);
    }

    /**
     * @test
     */
    public function requireOnceCallsTheBackendsRequireOnceMethod()
    {
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects(self::once())->method('requireOnce')->with('Foo-Bar')->will(self::returnValue('hello world!'));

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);

        $result = $cache->requireOnce('Foo-Bar');
        self::assertSame('hello world!', $result);
    }
}
