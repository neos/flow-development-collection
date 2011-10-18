<?php
namespace TYPO3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Utility\Files;
use \TYPO3\FLOW3\Security\Policy\Role;

/**
 * Testcase for the private resources publishing aspect
 *
 */
class PrivateResourcesPublishingAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed() {
		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);

		$this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext() {
		$allowedRoles = array (
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role5'),
			new Role('Role6')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfThePublishingConfigurationContainsNoAllowedRoles() {
		$allowedRoles = array ();

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role5'),
			new Role('Role6')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInLinkModeAndHasAFileName() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceFileName.ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);

		$expectedResult = 'TheBaseURI/Persistent/TheCurrentSessionId/Role2/ResourceHash/ResourceFileName.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInCopyModeAndHasAFileName() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceTitle.ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);

		$expectedResult = 'TheBaseURI/Persistent/TheCurrentSessionId/ResourceHash/ResourceTitle.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed() {
		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);

		$this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext() {
		$allowedRoles = array (
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role5'),
			new Role('Role6')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndNoFilenameIsRequested() {
		$temporaryDirectoryPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockAccessRestrictionPublisher = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('environment', $mockEnvironment);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'Persistent/TheCurrentSessionId/Role2/';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);

		Files::removeDirectoryRecursively($temporaryDirectoryPath);
		Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndTheFilenameIsRequested() {
		$temporaryDirectoryPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(TRUE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('environment', $mockEnvironment);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'Persistent/TheCurrentSessionId/Role2/ResourceHash.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);

		Files::removeDirectoryRecursively($temporaryDirectoryPath);
		Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndNoFilenameIsRequested() {
		$publishPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath . 'TheBasePath/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'TheBasePath/Persistent/TheCurrentSessionId/';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndTheFilenameIsRequested() {
		$publishPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath . 'TheBasePath/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(TRUE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'TheBasePath/Persistent/TheCurrentSessionId/ResourceHash.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesTheSessionDirectoryIfNeeded() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertFileExists('vfs://Foo/Web/_Resources/Persistent/TheCurrentSessionId/');
	}

	/**
	 * @test
	 */
	public function inLinkModeRewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesRoleDirectoriesForEachAllowedRoleAndSymlinksThemIntoTheCurrentSessionDirectory() {
		$temporaryDirectoryPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(dirname(tempnam('', '')), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('environment', $mockEnvironment);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertFileExists($temporaryDirectoryPath . 'PrivateResourcePublishing/Role2/');
		$this->assertFileExists($temporaryDirectoryPath . 'PrivateResourcePublishing/Role3/');
		$this->assertFileExists($publishPath . 'Persistent/TheCurrentSessionId/Role2');
		$this->assertFileExists($publishPath . 'Persistent/TheCurrentSessionId/Role3');

		$temporaryDirectoryPath = realpath($temporaryDirectoryPath) . '/';

		$role2PrivateResourcePath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($temporaryDirectoryPath, 'PrivateResourcePublishing/Role2'));
		$role2SymlinkedPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(realpath($publishPath . 'Persistent/TheCurrentSessionId/Role2')));
		$this->assertEquals($role2PrivateResourcePath, $role2SymlinkedPath);

		$role3PrivateResourcePath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($temporaryDirectoryPath, 'PrivateResourcePublishing/Role3'));
		$role3SymlinkedPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(realpath($publishPath . 'Persistent/TheCurrentSessionId/Role3')));
		$this->assertEquals($role3PrivateResourcePath, $role3SymlinkedPath);

		@Files::removeDirectoryRecursively($temporaryDirectoryPath);
		@Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCallsTheAccessRestrictionPublisherWithTheCalculatedSessionDirectoryPublishPath() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new Role('Role2'),
			new Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new Role('Role1'),
			new Role('Role2'),
			new Role('Role3')
		);

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);
		$mockAccessRestrictionPublisher->expects($this->once())->method('publishAccessRestrictionsForPath')->with('vfs://Foo/Web/_Resources/Persistent/TheCurrentSessionId/');

		$publishingAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->_set('securityContext', $mockSecurityContext);
		$publishingAspect->_set('session', $mockSession);
		$publishingAspect->_set('settings', $settings);
		$publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);
	}
}
?>
