<?php
namespace Neos\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Uri as UriPattern;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the URI request pattern
 */
class UriTest extends UnitTestCase
{
    public function matchRequestDataProvider()
    {
        return [
            ['uriPath' => '', 'pattern' => '.*', 'shouldMatch' => true],
            ['uriPath' => '', 'pattern' => '/some/nice/.*', 'shouldMatch' => false],
            ['uriPath' => '/some/nice/path/to/index.php', 'pattern' => '/some/nice/.*', 'shouldMatch' => true],
            ['uriPath' => '/some/other/path', 'pattern' => '.*/other/.*', 'shouldMatch' => true],
        ];
    }

    /**
     * @test
     * @dataProvider matchRequestDataProvider
     */
    public function matchRequestTests($uriPath, $pattern, $shouldMatch)
    {
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects(self::atLeastOnce())->method('getHttpRequest')->will(self::returnValue($mockHttpRequest));

        $mockUri = $this->getMockBuilder(UriInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects(self::atLeastOnce())->method('getUri')->will(self::returnValue($mockUri));

        $mockUri->expects(self::atLeastOnce())->method('getPath')->will(self::returnValue($uriPath));

        $requestPattern = new UriPattern(['uriPattern' => $pattern]);

        if ($shouldMatch) {
            self::assertTrue($requestPattern->matchRequest($mockActionRequest));
        } else {
            self::assertFalse($requestPattern->matchRequest($mockActionRequest));
        }
    }
}
