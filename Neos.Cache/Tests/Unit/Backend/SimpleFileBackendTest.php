<?php
namespace TYPO3\Flow\Cache\Tests\Unit\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Cache\Backend\SimpleFileBackend;
use TYPO3\Flow\Cache\EnvironmentConfiguration;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;
use TYPO3\Flow\Cache\Frontend\PhpFrontend;
use TYPO3\Flow\Cache\Tests\BaseTestCase;

/**
 * Test case for the SimpleFileBackend
 */
class SimpleFileBackendTest extends BaseTestCase
{
    /**
     * @var FrontendInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCacheFrontend;

    /**
     * @var EnvironmentConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEnvironmentConfiguration;

    /**
     * @return void
     */
    public function setUp()
    {
        vfsStream::setup('Temporary/Directory/');

        $this->mockEnvironmentConfiguration = $this->getMock(\TYPO3\Flow\Cache\EnvironmentConfiguration::class, null, [
            __DIR__,
            'Testing',
            'vfs://Temporary/Directory/',
            1024
        ], '');

        $this->mockCacheFrontend = $this->getMockBuilder(\TYPO3\Flow\Cache\Frontend\FrontendInterface::class)->getMock();
    }

    /**
     * Convenience function to retrieve an instance of SimpleFileBackend with required dependencies
     *
     * @param array $options
     * @param FrontendInterface $mockCacheFrontend
     * @return SimpleFileBackend
     */
    protected function getSimpleFileBackend(array $options = array(), FrontendInterface $mockCacheFrontend = null)
    {
        $simpleFileBackend = new SimpleFileBackend($this->mockEnvironmentConfiguration, $options);

        if ($mockCacheFrontend === null) {
            $simpleFileBackend->setCache($this->mockCacheFrontend);
        } else {
            $simpleFileBackend->setCache($mockCacheFrontend);
        }
        return $simpleFileBackend;
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception
     */
    public function setCacheThrowsExceptionOnNonWritableDirectory()
    {

        $mockEnvironmentConfiguration = $this->getMock(\TYPO3\Flow\Cache\EnvironmentConfiguration::class, null, [
            __DIR__,
            'Testing',
            'vfs://Some/NonExisting/Directory/',
            1024
        ], '');
        $simpleFileBackend = new SimpleFileBackend($mockEnvironmentConfiguration, []);

        $simpleFileBackend->setCache($this->mockCacheFrontend);
        $this->getSimpleFileBackend();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception
     * @expectedExceptionCode 1248710426
     */
    public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength()
    {
        $mockEnvironmentConfiguration = new EnvironmentConfiguration(__DIR__,
            'Testing',
            'vfs://Temporary/Directory/',
            5);

        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\SimpleFileBackend::class, array('setTag', 'writeCacheFile'), array(), '', false);
        $backend->expects($this->once())->method('writeCacheFile')->willReturn(false);
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        $backend->set($entryIdentifier, 'cache data');
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');
        mkdir('vfs://Temporary/Directory/OtherDirectory');

        $simpleFileBackend = $this->getSimpleFileBackend(['cacheDirectory' => 'vfs://Temporary/Directory/OtherDirectory']);
        $this->assertEquals('vfs://Temporary/Directory/OtherDirectory/', $simpleFileBackend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        $simpleFileBackend = $this->getSimpleFileBackend();
        $this->assertEquals('vfs://Temporary/Directory/Cache/Data/SomeCache/', $simpleFileBackend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches()
    {
        /** @var PhpFrontend|\PHPUnit_Framework_MockObject_MockObject $mockPhpCacheFrontend */
        $mockPhpCacheFrontend = $this->getMockBuilder(\TYPO3\Flow\Cache\Frontend\PhpFrontend::class)->disableOriginalConstructor()->getMock();
        $mockPhpCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomePhpCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        $simpleFileBackend = $this->getSimpleFileBackend(array(), $mockPhpCacheFrontend);
        $this->assertEquals('vfs://Temporary/Directory/Cache/Code/SomePhpCache/', $simpleFileBackend->getCacheDirectory());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set('SomeIdentifier', array('not a string'));
    }

    /**
     * @test
     */
    public function setReallySavesToTheSpecifiedDirectory()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = uniqid('some data');
        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data);

        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename);
        $this->assertEquals($data, $retrievedData);
    }

    /**
     * @test
     */
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data1 = uniqid('some data');
        $data2 = uniqid('some other data');
        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data1);
        $simpleFileBackend->set($entryIdentifier, $data2);

        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename);
        $this->assertEquals($data2, $retrievedData);
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheFile()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data1 = uniqid('some data');
        $data2 = uniqid('some other data');
        $entryIdentifier = 'SimpleFileBackendTest';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data1);
        $simpleFileBackend->set($entryIdentifier, $data2);

        $this->assertSame($data2, $simpleFileBackend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function getReturnsFalseForDeletedFiles()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        unlink($pathAndFilename);

        $this->assertFalse($simpleFileBackend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function hasReturnsTrueIfAnEntryExists()
    {
        $entryIdentifier = 'SimpleFileBackendTest';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        $this->assertTrue($simpleFileBackend->has($entryIdentifier));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfAnEntryDoesNotExist()
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set('SomeEntryIdentifier', 'some data');

        $this->assertFalse($simpleFileBackend->has('SomeNonExistingEntryIdentifier'));
    }

    /**
     * @test
     */
    public function removeReallyRemovesACacheEntry()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        $this->assertFileExists($pathAndFilename);
        $this->assertTrue($simpleFileBackend->has($entryIdentifier));

        $simpleFileBackend->remove($entryIdentifier);

        $this->assertFileNotExists($pathAndFilename);
        $this->assertFalse($simpleFileBackend->has($entryIdentifier));
    }

    /**
     * @return array
     */
    public function invalidEntryIdentifiers()
    {
        return array(
            'trailing slash' => array('/myIdentifer'),
            'trailing dot and slash' => array('./myIdentifer'),
            'trailing two dots and slash' => array('../myIdentifier'),
            'trailing with multiple dots and slashes' => array('.././../myIdentifier'),
            'slash in middle part' => array('my/Identifier'),
            'dot and slash in middle part' => array('my./Identifier'),
            'two dots and slash in middle part' => array('my../Identifier'),
            'multiple dots and slashes in middle part' => array('my.././../Identifier'),
            'pending slash' => array('myIdentifier/'),
            'pending dot and slash' => array('myIdentifier./'),
            'pending dots and slash' => array('myIdentifier../'),
            'pending multiple dots and slashes' => array('myIdentifier.././../'),
        );
    }

    /**
     * @test
     * @param string $identifier
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function setThrowsExceptionForInvalidIdentifier($identifier)
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($identifier, 'some data');
    }

    /**
     * @test
     * @param string $identifier
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionForInvalidIdentifier($identifier)
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->get($identifier);
    }

    /**
     * @test
     * @param string $identifier
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function hasThrowsExceptionForInvalidIdentifier($identifier)
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->has($identifier);
    }

    /**
     * @test
     * @param string $identifier
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function removeThrowsExceptionForInvalidIdentifier($identifier)
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->remove($identifier);
    }

    /**
     * @test
     * @param string $identifier
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier)
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->requireOnce($identifier);
    }

    /**
     * @test
     */
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile()
    {
        $entryIdentifier = 'SomeValidPhpEntry';

        $simpleFileBackend = $this->getSimpleFileBackend();

        $data = '<?php return "foo";';
        $simpleFileBackend->set($entryIdentifier, $data);

        $loadedData = $simpleFileBackend->requireOnce($entryIdentifier);
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile()
    {
        $entryIdentifier = 'SomePhpEntryWithException';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function requireOnceDoesNotSwallowPhpWarningsOfTheIncludedFile()
    {
        $entryIdentifier = 'SomePhpEntryWithPhpWarning';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php trigger_error("Warning!", E_WARNING); ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function requireOnceDoesNotSwallowPhpNoticesOfTheIncludedFile()
    {
        $entryIdentifier = 'SomePhpEntryWithPhpNotice';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php $undefined ++; ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $entryIdentifier1 = 'SimpleFileBackendTest1';
        $pathAndFilename1 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier1;
        $entryIdentifier2 = 'SimpleFileBackendTest2';
        $pathAndFilename2 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier2;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier1, 'some data');
        $simpleFileBackend->set($entryIdentifier2, 'some more data');

        $this->assertFileExists($pathAndFilename1);
        $this->assertFileExists($pathAndFilename2);
        $this->assertTrue($simpleFileBackend->has($entryIdentifier1));
        $this->assertTrue($simpleFileBackend->has($entryIdentifier2));

        $simpleFileBackend->flush();

        $this->assertFileNotExists($pathAndFilename1);
        $this->assertFalse($simpleFileBackend->has($entryIdentifier1));
        $this->assertFileNotExists($pathAndFilename2);
        $this->assertFalse($simpleFileBackend->has($entryIdentifier2));
    }

    /**
     * @test
     */
    public function backendAllowsForIteratingOverEntries()
    {
        $simpleFileBackend = $this->getSimpleFileBackend();

        for ($i = 0; $i < 100; $i++) {
            $entryIdentifier = sprintf('entry-%s', $i);
            $data = 'some data ' . $i;
            $simpleFileBackend->set($entryIdentifier, $data);
        }

        $entries = array();
        foreach ($simpleFileBackend as $entryIdentifier => $data) {
            $entries[$entryIdentifier] = $data;
        }
        natsort($entries);
        $i = 0;
        foreach ($entries as $entryIdentifier => $data) {
            $this->assertEquals(sprintf('entry-%s', $i), $entryIdentifier);
            $this->assertEquals('some data ' . $i, $data);
            $i++;
        }
        $this->assertEquals(100, $i);
    }
}
