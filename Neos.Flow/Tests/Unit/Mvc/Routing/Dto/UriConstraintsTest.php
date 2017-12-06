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

use Neos\Flow\Http\Uri;
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
        $this->assertSame('some/overridden/path', $mergedUriConstraints->getPathConstraint());
    }

    public function applyToDataProvider()
    {
        return [
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'http'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => ''],
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld:80'],

            ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => ''],
            ['constraints' => [UriConstraints::CONSTRAINT_HOST => 'some-other-domain.tld'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-other-domain.tld'],

            ['constraints' => [UriConstraints::CONSTRAINT_SUB_DOMAIN => 'sub-domain'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://sub-domain.some-domain.tld'],
            ['constraints' => [UriConstraints::CONSTRAINT_SUB_DOMAIN => 'new-sub-domain'], 'templateUri' => 'http://old-sub-domain.some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://new-sub-domain.some-domain.tld'],
            ['constraints' => [UriConstraints::CONSTRAINT_SUB_DOMAIN => 'sub-domain'], 'templateUri' => 'http://localhost', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://sub-domain.localhost'],

            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 80], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => ''],
            ['constraints' => [UriConstraints::CONSTRAINT_PORT => 8080], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'http://some-domain.tld:8080'],
            ['constraints' => [UriConstraints::CONSTRAINT_SCHEME => 'https', UriConstraints::CONSTRAINT_PORT => 443], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => 'https://some-domain.tld'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/some/path'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => '/prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => ''],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => '/prefix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => '/prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => '/prefix/some/path'],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_PREFIX => '/prefix', UriConstraints::CONSTRAINT_PATH => '/some/path'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld/prefix/some/path'],

            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => false, 'expectedUri' => ''],
            ['constraints' => [UriConstraints::CONSTRAINT_PATH_SUFFIX => 'suffix'], 'templateUri' => 'http://some-domain.tld', 'forceAbsoluteUri' => true, 'expectedUri' => 'http://some-domain.tld'],
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
        $this->assertSame($expectedUri, (string)$resultingUri);
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
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
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
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withSubDomainReturnsANewInstanceWitSubDomainConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withSubDomain('subDomain-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_SUB_DOMAIN => 'subDomain-constraint'
        ];
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
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
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
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
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
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
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function withPathSuffixeturnsANewInstanceWitPathSuffixConstraintSet()
    {
        $uriConstraints = UriConstraints::create()->withPathSuffix('path-suffix-constraint');
        $expectedResult = [
            UriConstraints::CONSTRAINT_PATH_SUFFIX => 'path-suffix-constraint'
        ];
        $this->assertSame($expectedResult, ObjectAccess::getProperty($uriConstraints, 'constraints', true));
    }

    /**
     * @test
     */
    public function getPathConstraintReturnsNullByDefault()
    {
        $this->assertNull(UriConstraints::create()->getPathConstraint());
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
        $this->assertSame('some/path', $uriConstraints->getPathConstraint());
    }
}
