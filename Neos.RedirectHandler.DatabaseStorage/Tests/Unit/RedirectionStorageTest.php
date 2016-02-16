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

use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectionRepository;
use Neos\RedirectHandler\DatabaseStorage\RedirectionStorage;
use Neos\RedirectHandler\Redirection as RedirectionDto;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the RedirectionService class
 */
class RedirectionStorageTest extends UnitTestCase
{
    /**
     * @var RedirectionStorage
     */
    protected $redirectionStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RedirectionRepository
     */
    protected $mockRedirectionRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterCachingService
     */
    protected $mockRouterCachingService;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        parent::setUp();

        $this->redirectionStorage = new RedirectionStorage();

        $this->mockRedirectionRepository = $this->getMockBuilder(RedirectionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($this->redirectionStorage, 'redirectionRepository', $this->mockRedirectionRepository);
        $this->inject($this->redirectionStorage, 'routerCachingService', $this->mockRouterCachingService);
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsNullIfNoMatchingRedirectWasFound()
    {
        $this->mockRedirectionRepository->expects($this->once())
            ->method('findOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->will($this->returnValue(null));

        $this->assertNull($this->redirectionStorage->getOneBySourceUriPathAndHost('some/relative/path'));
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsMatchingRedirect()
    {
        $mockRedirection = $this->getMockBuilder(Redirection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedirection
            ->expects($this->once())
            ->method('getSourceUriPath')
            ->willReturn('some/relative/path');
        $mockRedirection
            ->expects($this->once())
            ->method('getTargetUriPath')
            ->willReturn('some/relative/path/target');
        $mockRedirection
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(301);

        $this->mockRedirectionRepository
            ->expects($this->once())
            ->method('findOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->willReturn($mockRedirection);

        $dto = $this->redirectionStorage->getOneBySourceUriPathAndHost('some/relative/path');

        $this->assertInstanceOf(RedirectionDto::class, $dto);
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
        $mockRedirection = $this->getMockBuilder(Redirection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRedirectionRepository
            ->expects($this->atLeastOnce())
            ->method('findOneBySourceUriPathAndHost')
            ->with($sourceUriPath)
            ->willReturn($mockRedirection);
        $this->mockRedirectionRepository
            ->expects($this->once())
            ->method('remove')
            ->with($mockRedirection);

        $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathRemovesMatchingRedirect()
    {
        $sourceUriPath = '/some/relative/path/';

        $this->mockRedirectionRepository
            ->expects($this->atLeastOnce())
            ->method('findOneBySourceUriPathAndHost')
            ->with($sourceUriPath)->will($this->returnValue(null));
        $this->mockRedirectionRepository->expects($this->never())->method('remove');
        $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeAllRemovesAllRegisteredRedirects()
    {
        $this->mockRedirectionRepository->expects($this->once())->method('removeAll');
        $this->redirectionStorage->removeAll();
    }

    /**
     * @test
     */
    public function addRedirectFlushesRouterCacheForAffectedUri()
    {
        $this->mockRedirectionRepository
            ->expects($this->atLeastOnce())
            ->method('findByTargetUriPathAndHost')
            ->willReturn([]);

        $this->mockRouterCachingService
            ->expects($this->once())
            ->method('flushCachesForUriPath')
            ->with('some/relative/path');

        $this->redirectionStorage->addRedirection('some/relative/path', 'target');
    }
}
