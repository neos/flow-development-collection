<?php
namespace Neos\Flow\Tests\Unit\Security\Authorization;

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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\FilterFirewall;
use Neos\Flow\Security\Authorization\InterceptorResolver;
use Neos\Flow\Security\Authorization\RequestFilter;
use Neos\Flow\Security\RequestPattern\Uri;
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the filter firewall
 *
 */
class FilterFirewallTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function configuredFiltersAreCreatedCorrectlyUsingLegacySettingsFormat()
    {
        $resolveRequestPatternClassCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'URI') {
                return 'mockPatternURI';
            } elseif ($args[0] === 'Neos\\TestRequestPattern') {
                return 'mockPatternTest';
            }
        };

        $resolveInterceptorClassCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'AccessGrant') {
                return 'mockInterceptorAccessGrant';
            } elseif ($args[0] === 'Neos\\TestSecurityInterceptor') {
                return 'mockInterceptorTest';
            }
        };

        $mockRequestPattern1 = $this->createMock(Uri::class);
        $mockRequestPattern1->expects($this->once())->method('setPattern')->with('/some/url/.*');
        $mockRequestPattern2 = $this->createMock(Uri::class);
        $mockRequestPattern2->expects($this->once())->method('setPattern')->with('/some/url/blocked.*');

        $getObjectCallback = function () use (&$mockRequestPattern1, &$mockRequestPattern2) {
            $args = func_get_args();

            if ($args[0] === 'mockPatternURI') {
                return $mockRequestPattern1;
            } elseif ($args[0] === 'mockPatternTest') {
                return $mockRequestPattern2;
            } elseif ($args[0] === 'mockInterceptorAccessGrant') {
                return 'AccessGrant';
            } elseif ($args[0] === 'mockInterceptorTest') {
                return 'InterceptorTest';
            } elseif ($args[0] === RequestFilter::class) {
                if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') {
                    return 'filter1';
                }
                if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') {
                    return 'filter2';
                }
            }
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));
        $mockPatternResolver = $this->getMockBuilder(RequestPatternResolver::class)->disableOriginalConstructor()->getMock();
        $mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
        $mockInterceptorResolver = $this->getMockBuilder(InterceptorResolver::class)->disableOriginalConstructor()->getMock();
        $mockInterceptorResolver->expects($this->any())->method('resolveInterceptorClass')->will($this->returnCallback($resolveInterceptorClassCallback));

        $settings = [
            [
                'patternType' => 'URI',
                'patternValue' => '/some/url/.*',
                'interceptor' => 'AccessGrant'
            ],
            [
                'patternType' => 'Neos\TestRequestPattern',
                'patternValue' => '/some/url/blocked.*',
                'interceptor' => 'Neos\TestSecurityInterceptor'
            ]
        ];

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['blockIllegalRequests'], [], '', false);
        $firewall->_set('objectManager', $mockObjectManager);
        $firewall->_set('requestPatternResolver', $mockPatternResolver);
        $firewall->_set('interceptorResolver', $mockInterceptorResolver);

        $firewall->_call('buildFiltersFromSettings', $settings);
        $result = $firewall->_get('filters');

        $this->assertEquals(['filter1', 'filter2'], $result, 'The filters were not built correctly (legacy format).');
    }

    /**
     * @test
     * @return void
     */
    public function configuredFiltersAreCreatedCorrectlyUsingNewSettingsFormat()
    {
        $resolveRequestPatternClassCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'Uri') {
                return 'mockPatternURI';
            } elseif ($args[0] === 'TYPO3\TestRequestPattern') {
                return 'mockPatternTest';
            }
        };

        $resolveInterceptorClassCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'AccessGrant') {
                return 'mockInterceptorAccessGrant';
            } elseif ($args[0] === 'TYPO3\TestSecurityInterceptor') {
                return 'mockInterceptorTest';
            }
        };

        $mockRequestPattern1 = $this->createMock(Uri::class);
        $mockRequestPattern2 = $this->createMock(Uri::class);

        $getObjectCallback = function () use (&$mockRequestPattern1, &$mockRequestPattern2) {
            $args = func_get_args();

            if ($args[0] === 'mockPatternURI') {
                $this->assertSame(['uriPattern' => '/some/url/.*'], $args[1]);
                return $mockRequestPattern1;
            } elseif ($args[0] === 'mockPatternTest') {
                $this->assertSame(['uriPattern' => '/some/url/blocked.*'], $args[1]);
                return $mockRequestPattern2;
            } elseif ($args[0] === 'mockInterceptorAccessGrant') {
                return 'AccessGrant';
            } elseif ($args[0] === 'mockInterceptorTest') {
                return 'InterceptorTest';
            } elseif ($args[0] === RequestFilter::class) {
                if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') {
                    return 'filter1';
                }
                if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') {
                    return 'filter2';
                }
            }
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));
        $mockPatternResolver = $this->getMockBuilder(RequestPatternResolver::class)->disableOriginalConstructor()->getMock();
        $mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
        $mockInterceptorResolver = $this->getMockBuilder(InterceptorResolver::class)->disableOriginalConstructor()->getMock();
        $mockInterceptorResolver->expects($this->any())->method('resolveInterceptorClass')->will($this->returnCallback($resolveInterceptorClassCallback));

        $settings = [
            'Some.Package:AllowedUris' => [
                'pattern' => 'Uri',
                'patternOptions' => [
                    'uriPattern' => '/some/url/.*',
                ],
                'interceptor' => 'AccessGrant'
            ],
            'Some.Package:TestPattern' => [
                'pattern' => 'TYPO3\TestRequestPattern',
                'patternOptions' => [
                    'uriPattern' => '/some/url/blocked.*',
                ],
                'interceptor' => 'TYPO3\TestSecurityInterceptor'
            ]
        ];

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['blockIllegalRequests'], [], '', false);
        $firewall->_set('objectManager', $mockObjectManager);
        $firewall->_set('requestPatternResolver', $mockPatternResolver);
        $firewall->_set('interceptorResolver', $mockInterceptorResolver);

        $firewall->_call('buildFiltersFromSettings', $settings);
        $result = $firewall->_get('filters');

        $this->assertEquals(['filter1', 'filter2'], $result, 'The filters were not built correctly.');
    }


    /**
     * @test
     */
    public function allConfiguredFiltersAreCalled()
    {
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter2 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter3 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest);

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['dummy'], [], '', false);
        $firewall->_set('filters', [$mockFilter1, $mockFilter2, $mockFilter3]);

        $firewall->blockIllegalRequests($mockActionRequest);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown()
    {
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));
        $mockFilter2 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));
        $mockFilter3 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['dummy'], [], '', false);
        $firewall->_set('filters', [$mockFilter1, $mockFilter2, $mockFilter3]);
        $firewall->_set('rejectAll', true);

        $firewall->blockIllegalRequests($mockActionRequest);
    }
}
