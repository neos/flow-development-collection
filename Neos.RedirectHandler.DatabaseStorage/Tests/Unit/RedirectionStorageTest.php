<?php
namespace Neos\RedirectHandler\Tests\Unit\Storage;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectionRepository;
use Neos\RedirectHandler\DatabaseStorage\RedirectionStorage;
use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the RedirectionService class
 */
class RedirectionStorageTest extends UnitTestCase
{
    /**
     * @var RedirectionStorageInterface
     */
    protected $redirectionStorage;

    /**
     * @var RedirectionRepository
     */
    protected $mockRedirectionRepository;

    /**
     * @var RouterCachingService
     */
    protected $mockRouterCachingService;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
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
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue(null));
        $this->assertNull($this->redirectionStorage->getOneBySourceUriPathAndHost('some/relative/path'));
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsMatchingRedirect()
    {
        $mockRedirect = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\Redirection')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue($mockRedirect));
        $this->assertSame($mockRedirect, $this->redirectionStorage->getOneBySourceUriPathAndHost('some/relative/path'));
    }

    /**
     * @test
     */
    public function getAllReturnsAllRedirects()
    {
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockQueryResult));
        $this->assertSame($mockQueryResult, $this->redirectionStorage->getAll());
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathExitsIfNoMatchingRedirectWasFound()
    {
        $sourceUriPath = '/some/relative/path/';
        $mockRedirect = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\Redirection')->disableOriginalConstructor()->getMock();

        $this->mockRedirectionRepository->expects($this->atLeastOnce())->method('findOneBySourceUriPath')->with($sourceUriPath)->will($this->returnValue($mockRedirect));
        $this->mockRedirectionRepository->expects($this->once())->method('remove')->with($mockRedirect);
        $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathRemovesMatchingRedirect()
    {
        $sourceUriPath = '/some/relative/path/';

        $this->mockRedirectionRepository->expects($this->atLeastOnce())->method('findOneBySourceUriPath')->with($sourceUriPath)->will($this->returnValue(null));
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
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionStorage->expects($this->once())->method('findByTargetUriPath')->will($this->returnValue($mockQueryResult));

        $this->mockRouterCachingService->expects($this->once())->method('flushCachesForUriPath')->with('some/relative/path');

        $this->redirectionStorage->addRedirection('some/relative/path', 'target');
    }
}
