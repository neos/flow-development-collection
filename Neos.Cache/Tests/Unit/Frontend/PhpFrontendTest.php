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
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
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
        $mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Exception\InvalidDataException
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
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
        $mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

        $cache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($cache, 'backend', $mockBackend);

        $result = $cache->requireOnce('Foo-Bar');
        $this->assertSame('hello world!', $result);
    }
}
