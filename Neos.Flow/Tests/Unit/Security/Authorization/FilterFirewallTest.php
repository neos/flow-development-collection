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
use Neos\Flow\Security\Authorization\Interceptor\AccessGrant;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Authorization\InterceptorResolver;
use Neos\Flow\Security\Authorization\RequestFilter;
use Neos\Flow\Security\Exception\AccessDeniedException;
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
        $accessGrant = $this->createMock(AccessGrant::class);
        $testInterceptor = $this->createMock(InterceptorInterface::class);

        $getObjectCallback = function () use ($mockRequestPattern1, $mockRequestPattern2, $accessGrant, $testInterceptor) {
            $args = func_get_args();

            if ($args[0] === 'mockPatternURI') {
                self::assertSame(['uriPattern' => '/some/url/.*'], $args[1]);
                return $mockRequestPattern1;
            } elseif ($args[0] === 'mockPatternTest') {
                self::assertSame(['uriPattern' => '/some/url/blocked.*'], $args[1]);
                return $mockRequestPattern2;
            } elseif ($args[0] === 'mockInterceptorAccessGrant') {
                return $accessGrant;
            } elseif ($args[0] === 'mockInterceptorTest') {
                return $testInterceptor;
            } elseif ($args[0] === RequestFilter::class) {
                if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') {
                    return new RequestFilter($mockRequestPattern1, $accessGrant);
                }
                if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') {
                    return new RequestFilter($mockRequestPattern2, $testInterceptor);
                }
            }
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnCallBack($getObjectCallback));
        $mockPatternResolver = $this->getMockBuilder(RequestPatternResolver::class)->disableOriginalConstructor()->getMock();
        $mockPatternResolver->expects(self::any())->method('resolveRequestPatternClass')->will(self::returnCallBack($resolveRequestPatternClassCallback));
        $mockInterceptorResolver = $this->getMockBuilder(InterceptorResolver::class)->disableOriginalConstructor()->getMock();
        $mockInterceptorResolver->expects(self::any())->method('resolveInterceptorClass')->will(self::returnCallBack($resolveInterceptorClassCallback));

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
        $firewall->injectSettings(['security' => ['firewall' => ['rejectAll' => false,'filters' => $settings]]]);

        $result = $firewall->_get('filters');

        self::assertContainsOnly(RequestFilter::class, $result);
        self::assertEquals($mockRequestPattern1, $result[0]->getRequestPattern());
        self::assertEquals($accessGrant, $result[0]->getSecurityInterceptor());
        self::assertEquals($mockRequestPattern2, $result[1]->getRequestPattern());
        self::assertEquals($testInterceptor, $result[1]->getSecurityInterceptor());
    }


    /**
     * @test
     */
    public function allConfiguredFiltersAreCalled()
    {
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter1->expects(self::once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter2 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter2->expects(self::once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter3 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter3->expects(self::once())->method('filterRequest')->with($mockActionRequest);

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['dummy'], [], '', false);
        $firewall->_set('filters', [$mockFilter1, $mockFilter2, $mockFilter3]);

        $firewall->blockIllegalRequests($mockActionRequest);
    }

    /**
     * @test
     */
    public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown()
    {
        $this->expectException(AccessDeniedException::class);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter1->expects(self::once())->method('filterRequest')->with($mockActionRequest)->will(self::returnValue(false));
        $mockFilter2 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter2->expects(self::once())->method('filterRequest')->with($mockActionRequest)->will(self::returnValue(false));
        $mockFilter3 = $this->getMockBuilder(RequestFilter::class)->disableOriginalConstructor()->getMock();
        $mockFilter3->expects(self::once())->method('filterRequest')->with($mockActionRequest)->will(self::returnValue(false));

        $firewall = $this->getAccessibleMock(FilterFirewall::class, ['dummy'], [], '', false);
        $firewall->_set('filters', [$mockFilter1, $mockFilter2, $mockFilter3]);
        $firewall->_set('rejectAll', true);

        $firewall->blockIllegalRequests($mockActionRequest);
    }
}
