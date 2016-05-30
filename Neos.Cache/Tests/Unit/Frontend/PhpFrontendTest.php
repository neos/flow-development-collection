<?php
namespace TYPO3\Flow\Cache\Tests\Unit\Frontend;

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
use TYPO3\Flow\Cache\Tests\BaseTestCase;

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
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, ['isValidEntryIdentifier'], [], '', false);
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

        $mockBackend = $this->getMock(\TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface::class, [], [], '', false);
        $mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\PhpFrontend::class, ['dummy'], [], '', false);
        $this->inject($cache, 'backend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\PhpFrontend::class, ['dummy'], [], '', false);
        $cache->set('Foo-Bar', []);
    }

    /**
     * @test
     */
    public function requireOnceCallsTheBackendsRequireOnceMethod()
    {
        $mockBackend = $this->getMock(\TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface::class, [], [], '', false);
        $mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\PhpFrontend::class, ['dummy'], [], '', false);
        $this->inject($cache, 'backend', $mockBackend);

        $result = $cache->requireOnce('Foo-Bar');
        $this->assertSame('hello world!', $result);
    }
}
