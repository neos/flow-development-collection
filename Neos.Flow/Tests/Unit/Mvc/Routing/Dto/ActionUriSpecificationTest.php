<?php

namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Routing\Dto\ActionUriSpecification;
use Neos\Flow\Tests\UnitTestCase;

class ActionUriSpecificationTest extends UnitTestCase
{
    /** @test */
    public function specificationWithoutQueryParametersDontModifyTheUri()
    {
        $specification = ActionUriSpecification::create("Neos.Cool", ActionController::class, "index");

        self::assertEquals(
            new Uri("http://localhost/index?param1=foo&param2[0]=bar"),
            $specification->mergeQueryParametersIntoUri(new Uri("http://localhost/index?param1=foo&param2[0]=bar"))
        );
    }

    /** @test */
    public function queryParametersAddedToUriWithoutQueryParameters()
    {
        $specification = ActionUriSpecification::create("Neos.Cool", ActionController::class, "index")
            ->withQueryParameters([
                "param" => 123,
            ]);

        self::assertEquals(
            new Uri("http://localhost/index?param=123"),
            $specification->mergeQueryParametersIntoUri(new Uri("http://localhost/index"))
        );
    }

    /** @test */
    public function nestedQueryParametersAreMergedCorrectly()
    {
        $specification = ActionUriSpecification::create("Neos.Cool", ActionController::class, "index")
            ->withQueryParameters([
                "param2" => [
                    "b" => "huhu"
                ],
                "param3" => 123,
            ]);

        self::assertEquals(
            new Uri("http://localhost/index?param1=foo&param2[a]=bar&param2[b]=huhu&param3=123"),
            $specification->mergeQueryParametersIntoUri(new Uri("http://localhost/index?param1=foo&param2[a]=bar"))
        );
    }

    /** @test */
    public function toRouteValues()
    {
        $specification = ActionUriSpecification::create("Neos.Cool", ActionController::class, "index")
            ->withRoutingArguments(["neos" => 42])
            ->withQueryParameters(["q" => "lol"]) // will not be encoded
            ->withSubpackageKey("Application")
            ->withFormat("json");

        self::assertEquals(
            [
                'neos' => 42,
                '@action' => 'index',
                '@controller' => 'neos\flow\mvc\controller\actioncontroller',
                '@package' => 'neos.cool',
                '@subpackage' => 'application',
                '@format' => 'json',
            ],
            $specification->toRouteValues()
        );
    }
}
