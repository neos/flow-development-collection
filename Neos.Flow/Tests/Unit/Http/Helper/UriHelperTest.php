<?php

namespace Neos\Flow\Tests\Unit\Http\Helper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class UriHelperTest extends UnitTestCase
{
    #[Test]
    public function specificationWithoutQueryParametersDontModifyTheUri()
    {
        self::assertEquals(
            new Uri('http://localhost/index?param1=foo&param2[0]=bar'),
            UriHelper::uriWithAdditionalQueryParameters(new Uri('http://localhost/index?param1=foo&param2[0]=bar'), [])
        );
    }

    #[Test]
    public function queryParametersAddedToUriWithoutQueryParameters()
    {
        self::assertEquals(
            new Uri('http://localhost/index?param=123'),
            UriHelper::uriWithAdditionalQueryParameters(new Uri('http://localhost/index'), ['param' => 123])
        );
    }

    #[Test]
    public function nestedQueryParametersAreMergedCorrectly()
    {
        self::assertEquals(
            new Uri('http://localhost/index?param1=foo&param2[a]=bar&param2[b]=huhu&param3=123'),
            UriHelper::uriWithAdditionalQueryParameters(
                new Uri('http://localhost/index?param1=foo&param2[a]=bar'),
                [
                    'param2' => [
                        'b' => 'huhu'
                    ],
                    'param3' => 123,
                ]
            )
        );
    }

    #[Test]
    public function additionalQueryParametersAreEncoded()
    {
        self::assertEquals(
            new Uri('http://localhost/index?param=with+space&second=sp%C3%A4cial-chars%26stuff%3A'),
            UriHelper::uriWithAdditionalQueryParameters(new Uri('http://localhost/index'), ['param' => 'with space', 'second' => 'späcial-chars&stuff:'])
        );
    }
}
