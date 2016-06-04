<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Frontend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the PHP source code cache frontend
 *
 */
class PhpFrontendTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $cache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->setMethods(array('isValidEntryIdentifier'))->getMock();
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

        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface')->disableOriginalConstructor()->getMock();
        $mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, array('tags'), 1234);

        $cache = $this->getAccessibleMock('TYPO3\Flow\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', false);
        $cache->_set('backend', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, array('tags'), 1234);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $cache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\PhpFrontend')->disableOriginalConstructor()->setMethods(array('dummy'))->getMock();
        $cache->set('Foo-Bar', array());
    }

    /**
     * @test
     */
    public function requireOnceCallsTheBackendsRequireOnceMethod()
    {
        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface')->disableOriginalConstructor()->getMock();
        $mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

        $cache = $this->getAccessibleMock('TYPO3\Flow\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', false);
        $cache->_set('backend', $mockBackend);

        $result = $cache->requireOnce('Foo-Bar');
        $this->assertSame('hello world!', $result);
    }
}
