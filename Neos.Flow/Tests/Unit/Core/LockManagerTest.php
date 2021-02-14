<?php
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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Neos\Flow\Core\LockManager;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the LockManager
 */
class LockManagerTest extends UnitTestCase
{
    /**
     * @var LockManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $lockManager;

    /**
     * @var vfsStreamDirectory
     */
    protected $mockLockDirectory;

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
        $this->mockLockDirectory = vfsStream::setup('LockPath');
        $this->mockLockFile = vfsStream::newFile(md5(FLOW_PATH_ROOT) . '_Flow.lock')->at($this->mockLockDirectory);
        $this->mockLockFlagFile = vfsStream::newFile(md5(FLOW_PATH_ROOT) . '_FlowIsLocked')->at($this->mockLockDirectory);

        $this->lockManager = $this->getMockBuilder(LockManager::class)->setMethods(['getLockPath', 'doExit'])->disableOriginalConstructor()->getMock();
        $this->lockManager->expects(self::atLeastOnce())->method('getLockPath')->will(self::returnValue($this->mockLockDirectory->url() . '/'));
        $this->lockManager->__construct();
    }

    /**
     * @test
     */
    public function constructorDoesNotRemoveLockFilesIfTheyAreNotExpired()
    {
        self::assertFileExists($this->mockLockFile->url());
        self::assertFileExists($this->mockLockFlagFile->url());
    }

    /**
     * @test
     */
    public function constructorRemovesExpiredLockFiles()
    {
        $this->mockLockFlagFile->lastModified(time() - (LockManager::LOCKFILE_MAXIMUM_AGE + 1));
        self::assertFileExists($this->mockLockFile->url());
        self::assertFileExists($this->mockLockFlagFile->url());

        $this->lockManager->__construct();

        self::assertFileDoesNotExist($this->mockLockFile->url());
        self::assertFileDoesNotExist($this->mockLockFlagFile->url());
    }

    /**
     * @test
     */
    public function isSiteLockedReturnsTrueIfTheFlagFileExists()
    {
        self::assertTrue($this->lockManager->isSiteLocked());
    }

    /**
     * @test
     */
    public function isSiteLockedReturnsFalseIfTheFlagFileDoesNotExist()
    {
        unlink($this->mockLockFlagFile->url());
        self::assertFalse($this->lockManager->isSiteLocked());
    }

    /**
     * @test
     */
    public function exitIfSiteLockedExitsIfSiteIsLocked()
    {
        $this->lockManager->expects(self::once())->method('doExit');
        $this->lockManager->exitIfSiteLocked();
    }

    /**
     * @test
     */
    public function exitIfSiteLockedDoesNotExitIfSiteIsNotLocked()
    {
        $this->lockManager->unlockSite();
        $this->lockManager->expects(self::never())->method('doExit');
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

    /**
     * @test
     */
    public function lockSiteOrExitUpdatesLockFlagFileLastModifiedTimestampIfItExists()
    {
        $oldLastModifiedTimestamp = time() - 100;
        $this->mockLockFlagFile->lastModified($oldLastModifiedTimestamp);

        $this->lockManager->lockSiteOrExit();

        self::assertNotEquals($oldLastModifiedTimestamp, $this->mockLockFlagFile->filemtime());
    }

    /**
     * @test
     */
    public function lockSiteOrExitExitsIfSiteIsLocked()
    {
        $mockLockResource = fopen($this->mockLockFile->url(), 'w+');
        $this->mockLockFile->lock($mockLockResource, LOCK_EX | LOCK_NB);
        $this->lockManager->expects(self::once())->method('doExit');
        $this->lockManager->lockSiteOrExit();
    }

    /**
     * @test
     */
    public function lockSiteOrExitDoesNotExitIfSiteIsNotLocked()
    {
        $this->lockManager->expects(self::never())->method('doExit');
        $this->lockManager->lockSiteOrExit();
    }

    /**
     * @test
     */
    public function unlockSiteClosesLockResource()
    {
        $mockLockResource = fopen($this->mockLockFile->url(), 'w+');
        $this->mockLockFile->lock($mockLockResource, LOCK_EX | LOCK_NB);
        $this->inject($this->lockManager, 'lockResource', $mockLockResource);

        $this->lockManager->unlockSite();
        self::assertFalse(is_resource($mockLockResource));
    }

    /**
     * @test
     */
    public function unlockSiteRemovesLockFlagFile()
    {
        $this->lockManager->unlockSite();
        self::assertFileDoesNotExist($this->mockLockFlagFile->url());
    }
}
