<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;

/**
 * Testcase for the filter firewall
 *
 */
class FilterFirewallTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function configuredFiltersAreCreatedCorrectly() {
		$resolveRequestPatternClassCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'URI') return 'mockPatternURI';
			elseif ($args[0] === 'TYPO3\TestRequestPattern') return 'mockPatternTest';
		};

		$resolveInterceptorClassCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'AccessGrant') return 'mockInterceptorAccessGrant';
			elseif ($args[0] === 'TYPO3\TestSecurityInterceptor') return 'mockInterceptorTest';
		};

		$mockRequestPattern1 = $this->getMock('TYPO3\Flow\Security\RequestPatternInterface', array(), array(), 'pattern1', FALSE);
		$mockRequestPattern1->expects($this->once())->method('setPattern')->with('/some/url/.*');
		$mockRequestPattern2 = $this->getMock('TYPO3\Flow\Security\RequestPatternInterface', array(), array(), 'pattern2', FALSE);
		$mockRequestPattern2->expects($this->once())->method('setPattern')->with('/some/url/blocked.*');

		$getObjectCallback = function() use (&$mockRequestPattern1, &$mockRequestPattern2) {
			$args = func_get_args();

			if ($args[0] === 'mockPatternURI') return $mockRequestPattern1;
			elseif ($args[0] === 'mockPatternTest') return $mockRequestPattern2;
			elseif ($args[0] === 'mockInterceptorAccessGrant') return 'AccessGrant';
			elseif ($args[0] === 'mockInterceptorTest') return 'InterceptorTest';

			elseif ($args[0] === 'TYPO3\Flow\Security\Authorization\RequestFilter') {
				if ($args[1] == $mockRequestPattern1 && $args[2] === 'AccessGrant') return 'filter1';
				if ($args[1] == $mockRequestPattern2 && $args[2] === 'InterceptorTest') return 'filter2';
			}
		};

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));
		$mockPatternResolver = $this->getMock('TYPO3\Flow\Security\RequestPatternResolver', array(), array(), '', FALSE);
		$mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
		$mockInterceptorResolver = $this->getMock('TYPO3\Flow\Security\Authorization\InterceptorResolver', array(), array(), '', FALSE);
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

		$firewall = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\FilterFirewall', array('blockIllegalRequests'), array(), '', FALSE);
		$firewall->_set('objectManager', $mockObjectManager);
		$firewall->_set('requestPatternResolver', $mockPatternResolver);
		$firewall->_set('interceptorResolver', $mockInterceptorResolver);

		$firewall->_call('buildFiltersFromSettings', $settings);
		$result = $firewall->_get('filters');

		$this->assertEquals(array('filter1', 'filter2'), $result, 'The filters were not built correctly.');
	}

	/**
	 * @test
	 */
	public function allConfiguredFiltersAreCalled() {
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockFilter1 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest);
		$mockFilter2 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest);
		$mockFilter3 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest);

		$firewall = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\FilterFirewall', array('dummy'), array(), '', FALSE);
		$firewall->_set('filters', array($mockFilter1, $mockFilter2, $mockFilter3));

		$firewall->blockIllegalRequests($mockActionRequest);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown() {
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockFilter1 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter1->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(FALSE));
		$mockFilter2 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter2->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(FALSE));
		$mockFilter3 = $this->getMock('TYPO3\Flow\Security\Authorization\RequestFilter', array(), array(), '', FALSE);
		$mockFilter3->expects($this->once())->method('filterRequest')->with($mockActionRequest)->will($this->returnValue(FALSE));

		$firewall = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\FilterFirewall', array('dummy'), array(), '', FALSE);
		$firewall->_set('filters', array($mockFilter1, $mockFilter2, $mockFilter3));
		$firewall->_set('rejectAll', TRUE);

		$firewall->blockIllegalRequests($mockActionRequest);
	}
}
