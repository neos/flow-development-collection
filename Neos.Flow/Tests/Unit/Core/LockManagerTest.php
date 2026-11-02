<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Core\LockManager;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the LockManager
 */
final class LockManagerTest extends UnitTestCase
{
    /**
     * @var LockManager|MockObject
     */
    protected $lockManager;

    /**
     * @var vfsStreamFile
     */
    protected $mockLockFile;

    /**
     * @var vfsStreamFile
     */
    protected $mockLockFlagFile;


    protected function setUp(): void
    {
        $mockLockDirectory = vfsStream::setup('LockPath');
        $this->mockLockFile = vfsStream::newFile(md5(FLOW_PATH_ROOT) . '_Flow.lock')->at($mockLockDirectory);
        $this->mockLockFlagFile = vfsStream::newFile(md5(FLOW_PATH_ROOT) . '_FlowIsLocked')->at($mockLockDirectory);

        $this->lockManager = $this->getMockBuilder(LockManager::class)->onlyMethods(['getLockPath', 'doExit'])->disableOriginalConstructor()->getMock();
        $this->lockManager->expects($this->atLeastOnce())->method('getLockPath')->willReturn(($mockLockDirectory->url() . '/'));
        $this->lockManager->__construct();
    }

    #[Test]
    public function constructorDoesNotRemoveLockFilesIfTheyAreNotExpired()
    {
        self::assertFileExists($this->mockLockFile->url());
        self::assertFileExists($this->mockLockFlagFile->url());
    }

    #[Test]
    public function constructorRemovesExpiredLockFiles()
    {
        $this->mockLockFlagFile->lastModified(time() - (LockManager::LOCKFILE_MAXIMUM_AGE + 1));
        self::assertFileExists($this->mockLockFile->url());
        self::assertFileExists($this->mockLockFlagFile->url());

        $this->lockManager->__construct();

        self::assertFileDoesNotExist($this->mockLockFile->url());
        self::assertFileDoesNotExist($this->mockLockFlagFile->url());
    }

    #[Test]
    public function isSiteLockedReturnsTrueIfTheFlagFileExists()
    {
        self::assertTrue($this->lockManager->isSiteLocked());
    }

    #[Test]
    public function isSiteLockedReturnsFalseIfTheFlagFileDoesNotExist()
    {
        unlink($this->mockLockFlagFile->url());
        self::assertFalse($this->lockManager->isSiteLocked());
    }

    #[Test]
    public function exitIfSiteLockedExitsIfSiteIsLocked()
    {
        $this->lockManager->expects($this->once())->method('doExit');
        $this->lockManager->exitIfSiteLocked();
    }

    #[Test]
    public function exitIfSiteLockedDoesNotExitIfSiteIsNotLocked()
    {
        $this->lockManager->unlockSite();
        $this->lockManager->expects($this->never())->method('doExit');
        $this->lockManager->exitIfSiteLocked();
    }

    /**
     * test
     */
    public function lockSiteOrExitCreatesLockFlagFileIfItDoesNotExist()
    {
        $mockLockFlagFilePathAndName = $this->mockLockFlagFile->url();
        unlink($mockLockFlagFilePathAndName);
        $this->lockManager->lockSiteOrExit();
        self::assertFileExists($mockLockFlagFilePathAndName);
    }

    #[Test]
    public function lockSiteOrExitUpdatesLockFlagFileLastModifiedTimestampIfItExists()
    {
        $oldLastModifiedTimestamp = time() - 100;
        $this->mockLockFlagFile->lastModified($oldLastModifiedTimestamp);

        $this->lockManager->lockSiteOrExit();

        self::assertNotEquals($oldLastModifiedTimestamp, $this->mockLockFlagFile->filemtime());
    }

    #[Test]
    public function lockSiteOrExitExitsIfSiteIsLocked()
    {
        $mockLockResource = fopen($this->mockLockFile->url(), 'w+');
        $this->mockLockFile->lock($mockLockResource, LOCK_EX | LOCK_NB);
        $this->lockManager->expects($this->once())->method('doExit');
        $this->lockManager->lockSiteOrExit();
    }

    #[Test]
    public function lockSiteOrExitDoesNotExitIfSiteIsNotLocked()
    {
        $this->lockManager->expects($this->never())->method('doExit');
        $this->lockManager->lockSiteOrExit();
    }

    #[Test]
    public function unlockSiteClosesLockResource()
    {
        $mockLockResource = fopen($this->mockLockFile->url(), 'w+');
        $this->mockLockFile->lock($mockLockResource, LOCK_EX | LOCK_NB);
        $this->inject($this->lockManager, 'lockResource', $mockLockResource);

        $this->lockManager->unlockSite();
        self::assertFalse(is_resource($mockLockResource));
    }

    #[Test]
    public function unlockSiteRemovesLockFlagFile()
    {
        $this->lockManager->unlockSite();
        self::assertFileDoesNotExist($this->mockLockFlagFile->url());
    }
}
