<?php
namespace TYPO3\Flow\Tests\Unit\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 */
class ModificationTimeStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTime
     */
    protected $strategy;

    /**
     * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
     */
    protected $cache;

    /**
     */
    public function setUp()
    {
        vfsStream::setup('testDirectory');

        $this->cache = $this->getMockBuilder(\TYPO3\Flow\Cache\Frontend\StringFrontend::class)->disableOriginalConstructor()->getMock();

        $this->strategy = new \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
        $this->strategy->injectCache($this->cache);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusUnchangedIfFileDoesNotExistAndDidNotExistEarlier()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';

        $status = $this->strategy->getFileStatus($fileUrl);
        $this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusUnchangedIfFileExistedAndTheModificationTimeDidNotChange()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        $this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusDetectsANewlyCreatedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $status = $this->strategy->getFileStatus($fileUrl);
        $this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusDetectsADeletedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        unlink($fileUrl);
        $status = $this->strategy->getFileStatus($fileUrl);

        $this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED, $status);
    }

    /**
     * @test
     */
    public function getFileStatusReturnsStatusChangedIfTheFileExistedEarlierButTheModificationTimeHasChangedSinceThen()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        vfsStreamWrapper::getRoot()->getChild('test.txt')->lastModified(time() + 5);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        $this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CHANGED, $status);
    }
}
