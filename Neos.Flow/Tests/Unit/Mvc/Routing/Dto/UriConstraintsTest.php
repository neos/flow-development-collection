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
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Testcase for the UriConstraintsTest DTO
 */
class UriConstraintsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function mergeCombinesTwoInstancesWithPrecedenceToTheLatter()
    {
        $uriConstraints1 = UriConstraints::create()->withPath('some/path');
        $uriConstraints2 = UriConstraints::create()->withPath('some/overridden/path');

        $mergedUriConstraints = $uriConstraints1->merge($uriConstraints2);
        self::assertSame('some/overridden/path', $mergedUriConstraints->getPathConstraint());
    }

    public function applyToDataProvider()
    {
        return [
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld:80/'],

            ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-other-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-other-domain.tld/'],

            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.en.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['en.']]], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.new-host.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.new-host.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.de.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.', 'ch.']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.', 'some']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => []]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => ['en.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => ['de.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld/'],

            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.com']]], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://new-host.tld.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://new-host.tld.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de', '.tld']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de']]], 'templateUri' => 'http://some-domain.de', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de', '.domain']]], 'templateUri' => 'http://some-domain.de', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => ['.com']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => ['.tld']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.tld']]], 'templateUri' => 'http://some-domain.net', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.net/'],

            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld:8080/'],
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_PORT => 443], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld/'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/path'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefix'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefix'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefixsome/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix/', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefix/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefixsome/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix/', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefix/some/path'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/suffix'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/suffix'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/pathsuffix'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/pathsuffix'],
        ];
    }

    /**
     * @test
     * @dataProvider applyToDataProvider
     */
    public function applyToTests(array $constraints, string $templateUri, bool $forceAbsoluteUri, string $expectedUri)
    {
        $uriConstraints = UriConstraints::create();
        $this->inject($uriConstraints, 'constraints', $constraints);
        $resultingUri = $uriConstraints->applyTo(new Uri($templateUri), $forceAbsoluteUri);
        self::assertSame($expectedUri, (string)$resultingUri);
    }

    /**
     * @test
     */
    public function withSchemeReturnsANewInstanceWithSchemeConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withScheme('scheme-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_SCHEME => 'scheme-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withHostReturnsANewInstanceWithHostConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withHost('host-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_HOST => 'host-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withHostPrefixReturnsANewInstanceWitSubDomainConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withHostPrefix('host-prefix', ['replace', 'prefixes']);
        $expectedResult = [
            UriConstraints::CONSTRAINT_HOST_PREFIX => [
                'prefix' => 'host-prefix',
                'replacePrefixes' => ['replace', 'prefixes'],
            ]
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withHostSuffixReturnsANewInstanceWitSubDomainConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withHostSuffix('host-suffix', ['replace', 'suffixes']);
        $expectedResult = [
            UriConstraints::CONSTRAINT_HOST_SUFFIX => [
                'suffix' => 'host-suffix',
                'replaceSuffixes' => ['replace', 'suffixes'],
            ]
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }


    /**
     * @test
     */
    public function withPortReturnsANewInstanceWitPortConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPort(1234);
        $expectedResult = [
            UriConstraints::CONSTRAINT_PORT => 1234
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathReturnsANewInstanceWitPathConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPath('path-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH => 'path-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathPrefixReturnsANewInstanceWitPathPrefixConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPathPrefix('path-prefix-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_PREFIX => 'path-prefix-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathPrefixPrependsNewPrefixByDefault()
    {
        $uriConstraints = UriConstraints::create()->withPathPrefix('prefix1')->withPathPrefix('prefix2');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix2prefix1'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathPrefixAppendsNewPrefixIfSpecified()
    {
        $uriConstraints = UriConstraints::create()->withPathPrefix('prefix1')->withPathPrefix('prefix2', true);
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix1prefix2'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathSuffixReturnsANewInstanceWitPathSuffixConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('path-suffix-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'path-suffix-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathSuffixAppendsNewSuffixByDefault()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('suffix1')->withPathSuffix('suffix2');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix1suffix2'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathSuffixPrependsNewSuffixIfSpecified()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('suffix1')->withPathSuffix('suffix2', true);
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix2suffix1'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function getPathConstraintReturnsNullByDefault()
    {
        self::assertNull(UriConstraints::create()->getPathConstraint());
    }

    /**
     * @test
     */
    public function getPathConstraintReturnsPathConstraintWithoutPrefixAndSuffix()
    {
        $uriConstraints = UriConstraints::create()
            ->withPath('some/path')
            ->withPathPrefix('prefix')
            ->withPathSuffix('suffix');
        self::assertSame('some/path', $uriConstraints->getPathConstraint());
    }
}
