<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the filter firewall
 *
 */
class FilterFirewallTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * __test
     * @return void
     */
    public function configuredFiltersAreCreatedCorrectlyUsingLegacySettingsFormat()
    {
        $resolveRequestPatternClassCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'URI') {
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

        $mockRequestPattern1 = $this->getMock(\TYPO3\Flow\Security\RequestPattern\Uri::class, array(), array(), 'pattern1', false);
        $mockRequestPattern1->expects($this->once())->method('setPattern')->with('/some/url/.*');
        $mockRequestPattern2 = $this->getMock(\TYPO3\Flow\Security\RequestPattern\Uri::class, array(), array(), 'pattern2', false);
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
            } elseif ($args[0] === \TYPO3\Flow\Security\Authorization\RequestFilter::class) {
                if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') {
                    return 'filter1';
                }
                if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') {
                    return 'filter2';
                }
            }
        };

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));
        $mockPatternResolver = $this->getMock(\TYPO3\Flow\Security\RequestPatternResolver::class, array(), array(), '', false);
        $mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
        $mockInterceptorResolver = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorResolver::class, array(), array(), '', false);
        $mockInterceptorResolver->expects($this->any())->method('resolveInterceptorClass')->will($this->returnCallback($resolveInterceptorClassCallback));

        $settings = array(
            array(
                'patternType' => 'URI',
                'patternValue' => '/some/url/.*',
                'interceptor' => 'AccessGrant'
            ),
            array(
                'patternType' => 'TYPO3\TestRequestPattern',
                'patternValue' => '/some/url/blocked.*',
                'interceptor' => 'TYPO3\TestSecurityInterceptor'
            )
        );

        $firewall = $this->getAccessibleMock(\TYPO3\Flow\Security\Authorization\FilterFirewall::class, array('blockIllegalRequests'), array(), '', false);
        $firewall->_set('objectManager', $mockObjectManager);
        $firewall->_set('requestPatternResolver', $mockPatternResolver);
        $firewall->_set('interceptorResolver', $mockInterceptorResolver);

        $firewall->_call('buildFiltersFromSettings', $settings);
        $result = $firewall->_get('filters');

        $this->assertEquals(array('filter1', 'filter2'), $result, 'The filters were not built correctly (legacy format).');
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

        $mockRequestPattern1 = $this->getMock(\TYPO3\Flow\Security\RequestPattern\Uri::class, array(), array(), 'pattern1', false);
        $mockRequestPattern2 = $this->getMock(\TYPO3\Flow\Security\RequestPattern\Uri::class, array(), array(), 'pattern2', false);

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
            } elseif ($args[0] === \TYPO3\Flow\Security\Authorization\RequestFilter::class) {
                if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') {
                    return 'filter1';
                }
                if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') {
                    return 'filter2';
                }
            }
        };

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));
        $mockPatternResolver = $this->getMock(\TYPO3\Flow\Security\RequestPatternResolver::class, array(), array(), '', false);
        $mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
        $mockInterceptorResolver = $this->getMock(\TYPO3\Flow\Security\Authorization\InterceptorResolver::class, array(), array(), '', false);
        $mockInterceptorResolver->expects($this->any())->method('resolveInterceptorClass')->will($this->returnCallback($resolveInterceptorClassCallback));

        $settings = array(
            'Some.Package:AllowedUris' => array(
                'pattern' => 'Uri',
                'patternOptions' => array(
                    'uriPattern' => '/some/url/.*',
                ),
                'interceptor' => 'AccessGrant'
            ),
            'Some.Package:TestPattern' => array(
                'pattern' => 'TYPO3\TestRequestPattern',
                'patternOptions' => array(
                    'uriPattern' => '/some/url/blocked.*',
                ),
                'interceptor' => 'TYPO3\TestSecurityInterceptor'
            )
        );

        $firewall = $this->getAccessibleMock(\TYPO3\Flow\Security\Authorization\FilterFirewall::class, array('blockIllegalRequests'), array(), '', false);
        $firewall->_set('objectManager', $mockObjectManager);
        $firewall->_set('requestPatternResolver', $mockPatternResolver);
        $firewall->_set('interceptorResolver', $mockInterceptorResolver);

        $firewall->_call('buildFiltersFromSettings', $settings);
        $result = $firewall->_get('filters');

        $this->assertEquals(array('filter1', 'filter2'), $result, 'The filters were not built correctly.');
    }


    /**
     * __test
     */
    public function allConfiguredFiltersAreCalled()
    {
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter2 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest);
        $mockFilter3 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest);

        $firewall = $this->getAccessibleMock(\TYPO3\Flow\Security\Authorization\FilterFirewall::class, array('dummy'), array(), '', false);
        $firewall->_set('filters', array($mockFilter1, $mockFilter2, $mockFilter3));

        $firewall->blockIllegalRequests($mockActionRequest);
    }

    /**
     * __test
     * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
     */
    public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown()
    {
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockFilter1 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));
        $mockFilter2 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));
        $mockFilter3 = $this->getMock(\TYPO3\Flow\Security\Authorization\RequestFilter::class, array(), array(), '', false);
        $mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(false));

        $firewall = $this->getAccessibleMock(\TYPO3\Flow\Security\Authorization\FilterFirewall::class, array('dummy'), array(), '', false);
        $firewall->_set('filters', array($mockFilter1, $mockFilter2, $mockFilter3));
        $firewall->_set('rejectAll', true);

        $firewall->blockIllegalRequests($mockActionRequest);
    }
}
