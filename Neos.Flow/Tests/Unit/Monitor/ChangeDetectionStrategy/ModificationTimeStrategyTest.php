<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 */
final class ModificationTimeStrategyTest extends UnitTestCase
{
    /**
     * @var \Neos\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy
     */
    protected $strategy;

    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('testDirectory');

        $this->strategy = new ModificationTimeStrategy();
        $this->strategy->injectCache($this->createStub(StringFrontend::class));
    }

    #[Test]
    public function getFileStatusReturnsStatusUnchangedIfFileDoesNotExistAndDidNotExistEarlier()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';

        $status = $this->strategy->getFileStatus($fileUrl);
        self::assertSame(ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    #[Test]
    public function getFileStatusReturnsStatusUnchangedIfFileExistedAndTheModificationTimeDidNotChange()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
    }

    #[Test]
    public function getFileStatusDetectsANewlyCreatedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $status = $this->strategy->getFileStatus($fileUrl);
        self::assertSame(ChangeDetectionStrategyInterface::STATUS_CREATED, $status);
    }

    #[Test]
    public function getFileStatusDetectsADeletedFile()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        unlink($fileUrl);
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(ChangeDetectionStrategyInterface::STATUS_DELETED, $status);
    }

    #[Test]
    public function getFileStatusReturnsStatusChangedIfTheFileExistedEarlierButTheModificationTimeHasChangedSinceThen()
    {
        $fileUrl = vfsStream::url('testDirectory') . '/test.txt';
        file_put_contents($fileUrl, 'test data');

        $this->strategy->getFileStatus($fileUrl);
        vfsStreamWrapper::getRoot()->getChild('test.txt')->lastModified(time() + 5);
        clearstatcache();
        $status = $this->strategy->getFileStatus($fileUrl);

        self::assertSame(ChangeDetectionStrategyInterface::STATUS_CHANGED, $status);
    }
}
