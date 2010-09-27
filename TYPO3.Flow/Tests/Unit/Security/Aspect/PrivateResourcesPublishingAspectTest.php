<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the private resources publishing aspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PrivateResourcesPublishingAspectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed() {
		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);

		$this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext() {
		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role5'),
			new \F3\FLOW3\Security\Policy\Role('Role6')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfThePublishingConfigurationContainsNoAllowedRoles() {
		$allowedRoles = array ();

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role5'),
			new \F3\FLOW3\Security\Policy\Role('Role6')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInLinkModeAndHasAFileName() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceFileName.ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);

		$expectedResult = 'TheBaseURI/Persistent/TheCurrentSessionId/Role2/ResourceHash/ResourceFileName.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInCopyModeAndHasAFileName() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceTitle.ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);

		$expectedResult = 'TheBaseURI/Persistent/TheCurrentSessionId/ResourceHash/ResourceTitle.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed() {
		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);

		$this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext() {
		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role5'),
			new \F3\FLOW3\Security\Policy\Role('Role6')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);

		$this->assertFalse($publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndNoFilenameIsRequested() {
		$temporaryDirectoryPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockAccessRestrictionPublisher = $this->getMock('F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectEnvironment($mockEnvironment);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'Persistent/TheCurrentSessionId/Role2/';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);

		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($temporaryDirectoryPath);
		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndTheFilenameIsRequested() {
		$temporaryDirectoryPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(TRUE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectEnvironment($mockEnvironment);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$expectedResult = $publishPath . 'Persistent/TheCurrentSessionId/Role2/ResourceHash.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);

		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($temporaryDirectoryPath);
		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndNoFilenameIsRequested() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('TheBasePath/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$expectedResult = 'TheBasePath/Persistent/TheCurrentSessionId/';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndTheFilenameIsRequested() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('TheBasePath/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(TRUE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$expectedResult = 'TheBasePath/Persistent/TheCurrentSessionId/ResourceHash.ResourceFileExtension';

		$result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertEquals($result, $expectedResult);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesTheSessionDirectoryIfNeeded() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertFileExists('vfs://Foo/Web/_Resources/Persistent/TheCurrentSessionId/');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function inLinkModeRewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesRoleDirectoriesForEachAllowedRoleAndSymlinksThemIntoTheCurrentSessionDirectory() {
		$temporaryDirectoryPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestTemporaryDirectory')) . '/';
		$publishPath = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3PrivateResourcesPublishingAspectTestPublishDirectory')) . '/';

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($publishPath));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectEnvironment($mockEnvironment);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

		$this->assertFileExists($temporaryDirectoryPath . 'PrivateResourcePublishing/Role2/');
		$this->assertFileExists($temporaryDirectoryPath . 'PrivateResourcePublishing/Role3/');
		$this->assertFileExists($publishPath . 'Persistent/TheCurrentSessionId/Role2');
		$this->assertFileExists($publishPath . 'Persistent/TheCurrentSessionId/Role3');
		$this->assertEquals($temporaryDirectoryPath . 'PrivateResourcePublishing/Role2', rtrim(readlink($publishPath . 'Persistent/TheCurrentSessionId/Role2'), '/'));
		$this->assertEquals($temporaryDirectoryPath . 'PrivateResourcePublishing/Role3', rtrim(readlink($publishPath . 'Persistent/TheCurrentSessionId/Role3'), '/'));

		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($temporaryDirectoryPath);
		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($publishPath);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCallsTheAccessRestrictionPublisherWithTheCalculatedSessionDirectoryPublishPath() {
		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$allowedRoles = array (
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockPublishingConfiguration = $this->getMock('F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', FALSE);
		$mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

		$actualRoles = array(
			new \F3\FLOW3\Security\Policy\Role('Role1'),
			new \F3\FLOW3\Security\Policy\Role('Role2'),
			new \F3\FLOW3\Security\Policy\Role('Role3')
		);

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

		$mockPublishingTargetProxy = $this->getMock('F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', FALSE);
		$mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

		$mockResourcePointer = $this->getMock('F3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
		$mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
		$mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(FALSE));
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

		$mockAccessRestrictionPublisher = $this->getMock('\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', FALSE);
		$mockAccessRestrictionPublisher->expects($this->once())->method('publishAccessRestrictionsForPath')->with('vfs://Foo/Web/_Resources/Persistent/TheCurrentSessionId/');

		$publishingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', FALSE);
		$publishingAspect->injectSecurityContext($mockSecurityContext);
		$publishingAspect->injectSession($mockSession);
		$publishingAspect->injectSettings($settings);
		$publishingAspect->injectAccessRestrictionPublisher($mockAccessRestrictionPublisher);

		$publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);
	}
}
?>
