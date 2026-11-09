<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the UriConstraintsTest DTO
 */
final class UriConstraintsTest extends UnitTestCase
{
    #[Test]
    public function mergeCombinesTwoInstancesWithPrecedenceToTheLatter()
    {
        $uriConstraints1 = UriConstraints::create()->withPath('some/path');
        $uriConstraints2 = UriConstraints::create()->withPath('some/overridden/path');

        $mergedUriConstraints = $uriConstraints1->merge($uriConstraints2);
        self::assertSame('/some/overridden/path', (string)$mergedUriConstraints->toUri());
    }

    public static function applyToDataProvider(): \Iterator
    {
        yield ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld:80/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-other-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-other-domain.tld/'];
        // 'localhost' is HTTP_DEFAULT_HOST — an explicit withHost('localhost') must not be overwritten by the base URI host
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'localhost', UriConstraints::CONSTRAINT_PATH => '/some-path'], 'templateUri' => 'http://other.localhost:8081', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://localhost:8081/some-path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'localhost', UriConstraints::CONSTRAINT_PATH => '/some-path'], 'templateUri' => 'http://other.localhost:8081', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://localhost:8081/some-path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.en.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['en.']]], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://en.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.new-host.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.new-host.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => []]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.de.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.', 'ch.']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => 'en.', 'replacePrefixes' => ['de.', 'some']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://en.some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => []]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => ['en.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_PREFIX => ['prefix' => '', 'replacePrefixes' => ['de.']]], 'templateUri' => 'http://de.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.com']]], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.com', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://new-host.tld.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => []], UriConstraints::CONSTRAINT_HOST => 'new-host.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://new-host.tld.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de', '.tld']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de']]], 'templateUri' => 'http://some-domain.de', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.de', '.domain']]], 'templateUri' => 'http://some-domain.de', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.com/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => []]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => ['.com']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '', 'replaceSuffixes' => ['.tld']]], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST_SUFFIX => ['suffix' => '.com', 'replaceSuffixes' => ['.tld']]], 'templateUri' => 'http://some-domain.net', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.net/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld:80', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld:8080/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-other-domain.tld', UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-other-domain.tld:8080/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld:8080', 'forceAbsoluteUri' => false, 'expectedUri' => '/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_PORT => 443], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/base/path/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld/base/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path/'], 'templateUri' => 'http://some-domain.tld/base/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/base/path/some/path/'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefixsome/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix/', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefix/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefixsome/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix/', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefix/some/path'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/suffix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/suffix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/pathsuffix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/pathsuffix'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'some-query-string'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/?some-query-string'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'foo=bar&bar=fös'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/?foo=bar&bar=f%C3%B6s'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'query'], 'templateUri' => 'http://some-domain.tld/some/path/', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path?query'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/#fragment'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/#fragment'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'frägment'], 'templateUri' => 'http://some-domain.tld/some/path', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path#fr%C3%A4gment'];
        yield ['constraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment'], 'templateUri' => 'http://some-domain.tld/some/path#replaceme', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path#fragment'];
    }

    #[DataProvider('applyToDataProvider')]
    #[Test]
    public function applyToTests(array $constraints, string $templateUri, bool $forceAbsoluteUri, string $expectedUri)
    {
        $uriConstraints = UriConstraints::create();
        $this->inject($uriConstraints, 'constraints', $constraints);
        $resultingUri = $uriConstraints->applyTo(new Uri($templateUri), $forceAbsoluteUri);
        self::assertSame($expectedUri, (string)$resultingUri);
    }

    #[Test]
    public function withSchemeReturnsANewInstanceWithSchemeConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withScheme('scheme-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_SCHEME => 'scheme-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withHostReturnsANewInstanceWithHostConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withHost('host-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_HOST => 'host-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function withPortReturnsANewInstanceWithPortConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPort(1234);
        $expectedResult = [
            UriConstraints::CONSTRAINT_PORT => 1234
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withPathReturnsANewInstanceWithPathConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPath('path-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH => 'path-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withQueryStringReturnsANewInstanceWithQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withQueryString('some=query&string');
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some=query&string'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withAddedQueryValuesReturnsANewInstanceWithQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withAddedQueryValues(['some' => ['nested' => ['páram' => 'some vàlue', 'new' => 'some other válue']]]);
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some%5Bnested%5D%5Bp%C3%A1ram%5D=some+v%C3%A0lue&some%5Bnested%5D%5Bnew%5D=some+other+v%C3%A1lue'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withAddedQueryValuesReturnsANewInstanceWithMergedQueryStringConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withQueryString('some[nested][páram]=vâlue&some[other]=valúe')->withAddedQueryValues(['some' => ['nested' => ['páram' => 'overridden', 'new' => 'new válue']]]);
        $expectedResult = [
            UriConstraints::CONSTRAINT_QUERY_STRING => 'some%5Bnested%5D%5Bp%C3%A1ram%5D=overridden&some%5Bnested%5D%5Bnew%5D=new+v%C3%A1lue&some%5Bother%5D=val%C3%BAe'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withFragmentReturnsANewInstanceWithFragmentConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withFragment('some-fragment');
        $expectedResult = [
            UriConstraints::CONSTRAINT_FRAGMENT => 'some-fragment'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withPathPrefixReturnsANewInstanceWithPathPrefixConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPathPrefix('path-prefix-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_PREFIX => 'path-prefix-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withPathPrefixPrependsNewPrefixByDefault()
    {
        $uriConstraints = UriConstraints::create()->withPathPrefix('prefix1')->withPathPrefix('prefix2');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_PREFIX => 'prefix2prefix1'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
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
     */
    #[Test]
    public function withPathPrefixReturnsTheCurrentInstanceIfPathPrefixIsEmpty()
    {
        $uriConstraints = UriConstraints::create();
        $uriConstraints2 = $uriConstraints->withPathPrefix('');
        self::assertSame($uriConstraints, $uriConstraints2);
    }

    #[Test]
    public function withPathPrefixThrowsExceptionIfPrefixStartsWithASlash()
    {
        $this->expectException(\InvalidArgumentException::class);
        UriConstraints::create()->withPathPrefix('/prefix1');
    }

    #[Test]
    public function withPathSuffixReturnsANewInstanceWitPathSuffixConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('path-suffix-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'path-suffix-constraint'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withPathSuffixAppendsNewSuffixByDefault()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('suffix1')->withPathSuffix('suffix2');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix1suffix2'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function withPathSuffixPrependsNewSuffixIfSpecified()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('suffix1')->withPathSuffix('suffix2', true);
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix2suffix1'
        ];
        self::assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    #[Test]
    public function getPathConstraintReturnsNullByDefault()
    {
        self::assertNull(UriConstraints::create()->getPathConstraint());
    }

    #[Test]
    public function getPathConstraintReturnsPathConstraintWithoutPrefixAndSuffix()
    {
        $uriConstraints = UriConstraints::create()
            ->withPath('some/path')
            ->withPathPrefix('prefix')
            ->withPathSuffix('suffix');
        self::assertSame('some/path', $uriConstraints->getPathConstraint());
    }

    public static function fromUriDataProvider(): \Iterator
    {
        yield ['uri' => '', 'expectedConstraints' => []];
        yield ['uri' => 'https://neos.io', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_HOST => 'neos.io', UriConstraints::CONSTRAINT_PORT => 443]];
        yield ['uri' => 'http://www.neos.io', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http', UriConstraints::CONSTRAINT_HOST => 'www.neos.io', UriConstraints::CONSTRAINT_PORT => 80]];
        yield ['uri' => 'http://localhost:8080', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http', UriConstraints::CONSTRAINT_HOST => 'localhost', UriConstraints::CONSTRAINT_PORT => 8080]];
        yield ['uri' => '/some/path', 'expectedConstraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path']];
        yield ['uri' => '?some&query=string', 'expectedConstraints' => [UriConstraints::CONSTRAINT_QUERY_STRING => 'some&query=string']];
        yield ['uri' => '#fragment', 'expectedConstraints' => [UriConstraints::CONSTRAINT_FRAGMENT => 'fragment']];
        yield ['uri' => 'https://neos.io:1234/some/path?the[query]=string#some-fragment', 'expectedConstraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_HOST => 'neos.io', UriConstraints::CONSTRAINT_PORT => 1234, UriConstraints::CONSTRAINT_PATH => '/some/path', UriConstraints::CONSTRAINT_QUERY_STRING => 'the%5Bquery%5D=string', UriConstraints::CONSTRAINT_FRAGMENT => 'some-fragment']];
    }

    /**
     * @param string $uri
     * @param array $expectedConstraints
     */
    #[DataProvider('fromUriDataProvider')]
    #[Test]
    public function fromUriTests(string $uri, array $expectedConstraints)
    {
        $uriConstraints = UriConstraints::fromUri(new Uri($uri));
        self::assertSame($expectedConstraints, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }
}
