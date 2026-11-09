<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the URI request pattern
 */
final class UriTest extends UnitTestCase
{
    public static function matchRequestDataProvider(): \Iterator
    {
        yield ['uriPath' => '', 'pattern' => '.*', 'shouldMatch' => true];
        yield ['uriPath' => '', 'pattern' => '/some/nice/.*', 'shouldMatch' => false];
        yield ['uriPath' => '/some/nice/path/to/index.php', 'pattern' => '/some/nice/.*', 'shouldMatch' => true];
        yield ['uriPath' => '/some/other/path', 'pattern' => '.*/other/.*', 'shouldMatch' => true];
    }

    #[DataProvider('matchRequestDataProvider')]
    #[Test]
    public function matchRequestTests($uriPath, $pattern, $shouldMatch)
    {
        $mockActionRequest = $this->createMock(ActionRequest::class);

        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockUri = $this->createMock(UriInterface::class);
        $mockHttpRequest->expects($this->atLeastOnce())->method('getUri')->willReturn(($mockUri));

        $mockUri->expects($this->atLeastOnce())->method('getPath')->willReturn(($uriPath));

        $requestPattern = new UriPattern(['uriPattern' => $pattern]);

        if ($shouldMatch) {
            self::assertTrue($requestPattern->matchRequest($mockActionRequest));
        } else {
            self::assertFalse($requestPattern->matchRequest($mockActionRequest));
        }
    }
}
