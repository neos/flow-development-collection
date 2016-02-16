<?php
namespace Neos\RedirectHandler\Tests\Unit;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\DBALException;
use Neos\RedirectHandler\Redirection;
use Neos\RedirectHandler\RedirectionService;
use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
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
     * @var RedirectionStorageInterface
     */
    protected $mockRedirectionStorage;

    /**
     * @var Request
     */
    protected $mockHttpRequest;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        $this->redirectionService = new RedirectionService();

        $this->mockRedirectionStorage = $this->getMockBuilder(RedirectionStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($this->redirectionService, 'redirectionStorage', $this->mockRedirectionStorage);

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUri = $this->getMockBuilder(Uri::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockHttpRequest
            ->expects($this->any())
            ->method('getBaseUri')
            ->willReturn($mockUri);
    }

    /**
     * @test
     */
    public function buildResponseIfApplicableReturnsSilentlyIfRedirectionRepositoryThrowsException()
    {
        $this->mockRedirectionStorage
            ->expects($this->atLeastOnce())
            ->method('getOneBySourceUriPathAndHost')
            ->will($this->throwException(new DBALException()));

        $this->redirectionService->buildResponseIfApplicable($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function buildResponseIfApplicableReturnsNullIfNoApplicableRedirectIsFound()
    {
        $this->mockHttpRequest
            ->expects($this->atLeastOnce())
            ->method('getRelativePath')
            ->will($this->returnValue('some/relative/path'));

        $this->mockRedirectionStorage
            ->expects($this->once())
            ->method('getOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->will($this->returnValue(null));

        $this->assertNull($this->redirectionService->buildResponseIfApplicable($this->mockHttpRequest));
    }

    /**
     * @test
     */
    public function buildResponseIfApplicableRetunsHttpRequestIfApplicableRedirectIsFound()
    {
        $this->mockHttpRequest
            ->expects($this->atLeastOnce())
            ->method('getRelativePath')
            ->willReturn('some/relative/path');

        $mockRedirection = $this->getMockBuilder(Redirection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRedirection
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(301);

        $this->mockRedirectionStorage
            ->expects($this->once())
            ->method('getOneBySourceUriPathAndHost')
            ->with('some/relative/path')
            ->willReturn($mockRedirection);

        $this->inject($this->redirectionService, 'redirectionStorage', $this->mockRedirectionStorage);

        $request = $this->redirectionService->buildResponseIfApplicable($this->mockHttpRequest);

        $this->assertInstanceOf(Response::class, $request);
    }
}
