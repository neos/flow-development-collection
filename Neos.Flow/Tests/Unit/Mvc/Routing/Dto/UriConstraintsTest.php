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
        self::assertSame('/some/overridden/path', (string)$mergedUriConstraints->toUri());
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

            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld:80', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld/'],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld:8080/'],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-other-domain.tld', UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-other-domain.tld:8080/'],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => '/'],
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_PORT => 443], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld/'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/base/path/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path/'], 'templateUri' => 'http://some-domain.tld/base/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path/'],

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

            ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'some-query-string'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/?some-query-string'],
            ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'foo=bar&bar=fös'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/?foo=bar&bar=f%C3%B6s'],
            ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'query'], 'templateUri' => 'http://some-domain.tld/some/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path?query'],

            ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/#fragment'],
            ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/#fragment'],
            ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'frägment'], 'templateUri' => 'http://some-domain.tld/some/path', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path#fr%C3%A4gment'],
            ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld/some/path#replaceme', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path#fragment'],
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
    public function withHostPrefixReturnsANewInstanceWithSubDomainConstraintSet()
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
    public function withHostSuffixReturnsANewInstanceWithSubDomainConstraintSet()
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
    public function withPortReturnsANewInstanceWithPortConstraintSet()
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
    public function withPathReturnsANewInstanceWithPathConstraintSet()
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
    public function withQueryStringReturnsANewInstanceWithQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withQueryString('some=query&string');
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some=query&string'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withAddedQueryValuesReturnsANewInstanceWithQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withAddedQueryValues(['some' => ['nested' => ['páram' => 'some vàlue', 'new' => 'some other válue']]]);
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some%5Bnested%5D%5Bp%C3%A1ram%5D=some+v%C3%A0lue&some%5Bnested%5D%5Bnew%5D=some+other+v%C3%A1lue'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withAddedQueryValuesReturnsANewInstanceWithMergedQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withQueryString('some[nested][páram]=vâlue&some[other]=valúe')->withAddedQueryValues(['some' => ['nested' => ['páram' => 'overridden', 'new' => 'new válue']]]);
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some%5Bnested%5D%5Bp%C3%A1ram%5D=overridden&some%5Bnested%5D%5Bnew%5D=new+v%C3%A1lue&some%5Bother%5D=val%C3%BAe'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withFragmentReturnsANewInstanceWithFragmentConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withFragment('some-fragment');
        $expectedResult = [
            UriConstraints::CONSTRAINT_FRAGMENT => 'some-fragment'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathPrefixReturnsANewInstanceWithPathPrefixConstraintSet()
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
     * Note: This test merely documents the current behavior – I'm not sure if it makes sense really
     * @test
     */
    public function withPathPrefixReturnsTheCurrentInstanceIfPathPrefixIsEmpty()
    {
        $uriConstraints = UriConstraints::create();
        $uriConstraints2 = $uriConstraints->withPathPrefix('');
        self::assertSame($uriConstraints, $uriConstraints2);
    }

    /**
     * @test
     */
    public function withPathPrefixThrowsExceptionIfPrefixStartsWithASlash()
    {
        $this->expectException(\InvalidArgumentException::class);
        UriConstraints::create()->withPathPrefix('/prefix1');
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

    public function fromUriDataProvider()
    {
        return [
            ['uri' => '', 'expectedConstraints' => []],
            ['uri' => 'https://neos.io', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_HOST => 'neos.io']],
            ['uri' => 'http://localhost:8080', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http', UriConstraints::CONSTRAINT_HOST => 'localhost', UriConstraints::CONSTRAINT_PORT => 8080]],
            ['uri' => '/some/path', 'expectedConstraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path']],
            ['uri' => '?some&query=string', 'expectedConstraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'some&query=string']],
            ['uri' => '#fragment', 'expectedConstraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment']],

            ['uri' => 'https://neos.io:1234/some/path?the[query]=string#some-fragment', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_HOST => 'neos.io', UriConstraints::CONSTRAINT_PORT => 1234, UriConstraints::CONSTRAINT_PATH => '/some/path', UriConstraints::CONSTRAINT_QUERY_STRING => 'the%5Bquery%5D=string', UriConstraints::CONSTRAINT_FRAGMENT => 'some-fragment']],
        ];
    }

    /**
     * @test
     * @dataProvider fromUriDataProvider
     * @param string $uri
     * @param array $expectedConstraints
     */
    public function fromUriTests(string $uri, array $expectedConstraints)
    {
        $uriConstraints = UriConstraints::fromUri(new Uri($uri));
        self::assertSame($expectedConstraints, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }
}
