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

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Uri as UriPattern;
use Neos\Flow\Tests\UnitTestCase;

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

        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $mockUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->atLeastOnce())->method('getUri')->will($this->returnValue($mockUri));

        $mockUri->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue($uriPath));

        $requestPattern = new UriPattern(['uriPattern' => $pattern]);

        if ($shouldMatch) {
            $this->assertTrue($requestPattern->matchRequest($mockActionRequest));
        } else {
            $this->assertFalse($requestPattern->matchRequest($mockActionRequest));
        }
    }
}
