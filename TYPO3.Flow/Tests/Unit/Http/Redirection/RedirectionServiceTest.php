<?php
namespace TYPO3\Flow\Tests\Unit\Http\Redirection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Redirection\RedirectionService;
use TYPO3\Flow\Http\Redirection\Redirection;
use TYPO3\Flow\Http\Redirection\RedirectionRepository;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the RedirectionService class
 */
class RedirectionServiceTest extends UnitTestCase
{
    /**
     * @var RedirectionService
     */
    protected $redirectionService;

    /**
     * @var RedirectionRepository
     */
    protected $mockRedirectionRepository;

    /**
     * @var Request
     */
    protected $mockHttpRequest;

    /**
     * @var RouterCachingService
     */
    protected $mockRouterCachingService;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        $this->redirectionService = new RedirectionService();

        $this->mockRedirectionRepository = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\RedirectionRepository')->setMethods(array('findOneBySourceUriPath', 'findByTargetUriPath', 'findAll', 'add', 'update', 'remove', 'removeAll'))->disableOriginalConstructor()->getMock();
        $this->inject($this->redirectionService, 'redirectionRepository', $this->mockRedirectionRepository);

        $this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

        $this->mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
        $this->inject($this->redirectionService, 'routerCachingService', $this->mockRouterCachingService);
    }

    /**
     * @test
     */
    public function triggerRedirectIfApplicableReturnsSilentlyIfRedirectionRepositoryThrowsException()
    {
        $this->mockRedirectionRepository->expects($this->atLeastOnce())->method('findOneBySourceUriPath')->will($this->throwException(new \Doctrine\DBAL\DBALException()));

        $this->redirectionService->triggerRedirectIfApplicable($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function triggerRedirectIfApplicableTrimsTrailingSlashFromRequestPath()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('some/relative/path/'));
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path');

        $this->redirectionService->triggerRedirectIfApplicable($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function triggerRedirectIfApplicableTrimsLeadingSlashesFromRequestPath()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('//some/relative/path'));
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path');

        $this->redirectionService->triggerRedirectIfApplicable($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function triggerRedirectReturnsIfNoApplicableRedirectIsFound()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('some/relative/path'));
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue(null));

        $this->redirectionService->triggerRedirectIfApplicable($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function triggerRedirectSendsRedirectHeadersAndExitsIfApplicableRedirectIsFound()
    {
        $redirectionService = $this->getAccessibleMock('TYPO3\Flow\Http\Redirection\RedirectionService', array('sendRedirectHeaders'));
        $exitTriggered = false;
        $this->inject($redirectionService, 'exit', function () use (&$exitTriggered) { $exitTriggered = true; });
        $redirectionService->expects($this->once())->method('sendRedirectHeaders');

        $mockRedirect = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\Redirection')->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('some/relative/path'));

        $this->inject($redirectionService, 'redirectionRepository', $this->mockRedirectionRepository);
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue($mockRedirect));

        $redirectionService->triggerRedirectIfApplicable($this->mockHttpRequest);

        $this->assertTrue($exitTriggered);
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathTrimsTrailingSlashesFromSourceUriPath()
    {
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path');
        $this->redirectionService->getOneBySourceUriPath('some/relative/path//');
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathTrimsLeadingSlashesFromSourceUriPath()
    {
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path');
        $this->redirectionService->getOneBySourceUriPath('//some/relative/path');
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsNullIfNoMatchingRedirectWasFound()
    {
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue(null));
        $this->assertNull($this->redirectionService->getOneBySourceUriPath('some/relative/path'));
    }

    /**
     * @test
     */
    public function getOneBySourceUriPathReturnsMatchingRedirect()
    {
        $mockRedirect = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\Redirection')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findOneBySourceUriPath')->with('some/relative/path')->will($this->returnValue($mockRedirect));
        $this->assertSame($mockRedirect, $this->redirectionService->getOneBySourceUriPath('some/relative/path'));
    }

    /**
     * @test
     */
    public function getAllReturnsAllRedirects()
    {
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockQueryResult));
        $this->assertSame($mockQueryResult, $this->redirectionService->getAll());
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathExitsIfNoMatchingRedirectWasFound()
    {
        $sourceUriPath = '/some/relative/path/';
        $trimmedSourceUriPath = 'some/relative/path';
        $mockRedirect = $this->getMockBuilder('TYPO3\Flow\Http\Redirection\Redirection')->disableOriginalConstructor()->getMock();

        $this->mockRedirectionRepository->expects($this->atLeastOnce())->method('findOneBySourceUriPath')->with($trimmedSourceUriPath)->will($this->returnValue($mockRedirect));
        $this->mockRedirectionRepository->expects($this->once())->method('remove')->with($mockRedirect);
        $this->redirectionService->removeOneBySourceUriPath($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeOneBySourceUriPathRemovesMatchingRedirect()
    {
        $sourceUriPath = '/some/relative/path/';
        $trimmedSourceUriPath = 'some/relative/path';

        $this->mockRedirectionRepository->expects($this->atLeastOnce())->method('findOneBySourceUriPath')->with($trimmedSourceUriPath)->will($this->returnValue(null));
        $this->mockRedirectionRepository->expects($this->never())->method('remove');
        $this->redirectionService->removeOneBySourceUriPath($sourceUriPath);
    }

    /**
     * @test
     */
    public function removeAllRemovesAllRegisteredRedirects()
    {
        $this->mockRedirectionRepository->expects($this->once())->method('removeAll');
        $this->redirectionService->removeAll();
    }

    /**
     * @test
     */
    public function addRedirectTrimsTrailingSlashesFromSourceUriPath()
    {
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findByTargetUriPath')->with('some/relative/path')->will($this->returnValue($mockQueryResult));
        $this->redirectionService->addRedirection('some/relative/path//', 'target');
    }

    /**
     * @test
     */
    public function addRedirectTrimsLeadingSlashesFromSourceUriPath()
    {
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findByTargetUriPath')->with('some/relative/path')->will($this->returnValue($mockQueryResult));
        $this->redirectionService->addRedirection('//some/relative/path', 'target');
    }

    /**
     * @test
     */
    public function addRedirectFlushesRouterCacheForAffectedUri()
    {
        $mockQueryResult = $this->getMockBuilder('TYPO3\Flow\Persistence\QueryResultInterface')->disableOriginalConstructor()->getMock();
        $this->mockRedirectionRepository->expects($this->once())->method('findByTargetUriPath')->will($this->returnValue($mockQueryResult));

        $this->mockRouterCachingService->expects($this->once())->method('flushCachesForUriPath')->with('some/relative/path');

        $this->redirectionService->addRedirection('some/relative/path', 'target');
    }
}
