<?php
namespace Neos\RedirectHandler\DatabaseStorage\Tests\Unit;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect;
use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectRepository;
use Neos\RedirectHandler\DatabaseStorage\RedirectStorage;
use Neos\RedirectHandler\Redirect as RedirectDto;
use Neos\RedirectHandler\RedirectService;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the RedirectStorage class
 */
class RedirectStorageTest extends UnitTestCase
{
    /**
     * @var RedirectStorage
     */
    protected $redirectStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RedirectRepository
     */
    protected $mockRedirectRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterCachingService
     */
    protected $mockRouterCachingService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RedirectService
     */
    protected $redirectServiceMock;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        parent::setUp();

        $this->redirectStorage = new RedirectStorage();

        $this->mockRedirectRepository = $this->getMockBuilder(RedirectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($this->redirectStorage, 'redirectRepository', $this->mockRedirectRepository);

        $this->mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($this->redirectStorage, 'routerCachingService', $this->mockRouterCachingService);

        $this->redirectServiceMock = $this->getMockBuilder(RedirectService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($this->redirectStorage, '_redirectService', $this->redirectServiceMock);

        $loggerMock = $this->getMockBuilder(SystemLoggerInterface::class)
            ->getMock();
        $this->inject($this->redirectStorage, '_logger', $loggerMock);

    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsNullIfNoMatchingRedirectWasFound()
    {
        $this->mockRedirectRepository->expects($this->once())
            ->method('findOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->will($this->returnValue(null));

        $this->assertNull($this->redirectStorage->getOneBySourceUriPathAndHost('some/relative/path'));
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsMatchingRedirect()
    {
        $mockRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedirect
            ->expects($this->once())
            ->method('getSourceUriPath')
            ->willReturn('some/relative/path');
        $mockRedirect
            ->expects($this->once())
            ->method('getTargetUriPath')
            ->willReturn('some/relative/path/target');
        $mockRedirect
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(301);

        $this->mockRedirectRepository
            ->expects($this->once())
            ->method('findOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->willReturn($mockRedirect);

        $dto = $this->redirectStorage->getOneBySourceUriPathAndHost('some/relative/path');

        $this->assertInstanceOf(RedirectDto::class, $dto);
        $this->assertSame('some/relative/path', $dto->getSourceUriPath());
        $this->assertSame('some/relative/path/target', $dto->getTargetUriPath());
        $this->assertSame(301, $dto->getStatusCode());
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathExitsIfNoMatchingRedirectWasFound()
    {
        $sourceUriPath = '/some/relative/path/';
        $mockRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRedirectRepository
            ->expects($this->atLeastOnce())
            ->method('findOneBySourceUriPathAndHost')
            ->with($sourceUriPath)
            ->willReturn($mockRedirect);
        $this->mockRedirectRepository
            ->expects($this->once())
            ->method('remove')
            ->with($mockRedirect);

        $this->redirectStorage->removeOneBySourceUriPathAndHost($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathRemovesMatchingRedirect()
    {
        $sourceUriPath = '/some/relative/path/';

        $this->mockRedirectRepository
            ->expects($this->atLeastOnce())
            ->method('findOneBySourceUriPathAndHost')
            ->with($sourceUriPath)->will($this->returnValue(null));
        $this->mockRedirectRepository->expects($this->never())->method('remove');
        $this->redirectStorage->removeOneBySourceUriPathAndHost($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeAllRemovesAllRegisteredRedirects()
    {
        $this->mockRedirectRepository->expects($this->once())->method('removeAll');
        $this->redirectStorage->removeAll();
    }

    /**
     * @test
     */
    public function addRedirectEmitSignalAndFlushesRouterCacheForAffectedUri()
    {
        $this->mockRedirectRepository
            ->expects($this->atLeastOnce())
            ->method('findByTargetUriPathAndHost')
            ->willReturn([]);

        $this->mockRouterCachingService
            ->expects($this->once())
            ->method('flushCachesForUriPath')
            ->with('some/relative/path');

        $this->redirectServiceMock
            ->expects($this->atLeastOnce())
            ->method('emitRedirectCreated');

        $this->redirectStorage->addRedirect('some/relative/path', 'target');
    }
}
