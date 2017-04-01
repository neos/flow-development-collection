<?php
namespace TYPO3\Flow\Security\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Policy\Role;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the private resources publishing aspect
 *
 */
class PrivateResourcesPublishingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $temporaryDirectoryPath;

    /**
     * @var string
     */
    protected $publishPath;

    /**
     */
    public function setUp()
    {
        vfsStream::setup('Foo');
        $temporaryDirectoryBase = realpath(sys_get_temp_dir()) . '/' . str_replace('\\', '_', __CLASS__);

        $this->temporaryDirectoryPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($temporaryDirectoryBase, 'FlowPrivateResourcesPublishingAspectTestTemporaryDirectory'));
        \TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->temporaryDirectoryPath);
        $this->publishPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($temporaryDirectoryBase, 'FlowPrivateResourcesPublishingAspectTestPublishDirectory'));
        \TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->publishPath);
    }

    public function tearDown()
    {
        \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->temporaryDirectoryPath);
        \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->publishPath);
    }

    /**
     * @test
     */
    public function rewritePersistentResourceWebUriForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed()
    {
        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', false);

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);

        $this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
    }

    /**
     * @test
     */
    public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext()
    {
        $allowedRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role5'),
            new Role('Role6')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);

        $this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
    }

    /**
     * @test
     */
    public function rewritePersistentResourceWebUriForPrivateResourcesReturnsFalseIfThePublishingConfigurationContainsNoAllowedRoles()
    {
        $allowedRoles = array();

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role5'),
            new Role('Role6')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);

        $this->assertFalse($publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint));
    }

    /**
     * @test
     */
    public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInLinkModeAndHasAFilename()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceFilename.ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('settings', $settings);

        $expectedResult = 'TheBaseURI/Persistent/TheCurrentSessionId/Role2/ResourceHash/ResourceFilename.ResourceFileExtension';

        $result = $publishingAspect->_call('rewritePersistentResourceWebUriForPrivateResources', $mockJoinPoint);

        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function rewritePersistentResourceWebUriForPrivateResourcesCalculatesTheCorrectUriForAPrivateResourceThatIsPublishedInCopyModeAndHasAFilename()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesBaseUri')->will($this->returnValue('TheBaseURI/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFilename')->will($this->returnValue('ResourceTitle.ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
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
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsTheResultOfTheOriginalMethodIfNoSecurityPublishingConfigurationIsPassed()
    {
        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Resource\Publishing\PublishingConfigurationInterface', array(), array(), '', false);

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('resultOfTheOriginalMethod'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);

        $this->assertEquals('resultOfTheOriginalMethod', $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesReturnsFalseIfNoneOfTheAllowedRolesIsInTheCurrentSecurityContext()
    {
        $allowedRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role5'),
            new Role('Role6')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);

        $this->assertFalse($publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint));
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndNoFilenameIsRequested()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($this->publishPath));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($this->temporaryDirectoryPath));

        $mockAccessRestrictionPublisher = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('environment', $mockEnvironment);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $expectedResult = $this->publishPath . '/Persistent/TheCurrentSessionId/Role2/';

        $result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInLinkModeAndTheFilenameIsRequested()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($this->publishPath));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(true));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($this->temporaryDirectoryPath));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('environment', $mockEnvironment);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $expectedResult = $this->publishPath . '/Persistent/TheCurrentSessionId/Role2/ResourceHash.ResourceFileExtension';

        $result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndNoFilenameIsRequested()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($this->publishPath . 'TheBasePath/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->any())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $expectedResult = $this->publishPath . 'TheBasePath/Persistent/TheCurrentSessionId/';

        $result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCalculatesTheCorrectPathForAPrivateResourceThatIsPublishedInCopyModeAndTheFilenameIsRequested()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($this->publishPath . 'TheBasePath/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(true));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $expectedResult = $this->publishPath . 'TheBasePath/Persistent/TheCurrentSessionId/ResourceHash.ResourceFileExtension';

        $result = $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesTheSessionDirectoryIfNeeded()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
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
    public function inLinkModeRewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCreatesRoleDirectoriesForEachAllowedRoleAndSymlinksThemIntoTheCurrentSessionDirectory()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($this->temporaryDirectoryPath));

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue($this->publishPath));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('environment', $mockEnvironment);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);

        $this->assertFileExists($this->temporaryDirectoryPath . '/PrivateResourcePublishing/Role2/');
        $this->assertFileExists($this->temporaryDirectoryPath . '/PrivateResourcePublishing/Role3/');
        $this->assertFileExists($this->publishPath . '/Persistent/TheCurrentSessionId/Role2');
        $this->assertFileExists($this->publishPath . '/Persistent/TheCurrentSessionId/Role3');

        $role2PrivateResourcePath = realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($this->temporaryDirectoryPath, 'PrivateResourcePublishing/Role2')));
        $role2SymlinkedPath = realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($this->publishPath, 'Persistent/TheCurrentSessionId/Role2')));
        $this->assertEquals($role2PrivateResourcePath, $role2SymlinkedPath);

        $role3PrivateResourcePath = realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($this->temporaryDirectoryPath, 'PrivateResourcePublishing/Role3')));
        $role3SymlinkedPath = realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($this->publishPath, 'Persistent/TheCurrentSessionId/Role3')));
        $this->assertEquals($role3PrivateResourcePath, $role3SymlinkedPath);
    }

    /**
     * @test
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResourcesCallsTheAccessRestrictionPublisherWithTheCalculatedSessionDirectoryPublishPath()
    {
        $settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

        $allowedRoles = array(
            new Role('Role2'),
            new Role('Role3')
        );

        $mockPublishingConfiguration = $this->getMock('TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration', array(), array(), '', false);
        $mockPublishingConfiguration->expects($this->once())->method('getAllowedRoles')->will($this->returnValue($allowedRoles));

        $actualRoles = array(
            new Role('Role1'),
            new Role('Role2'),
            new Role('Role3')
        );

        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue($actualRoles));

        $mockPublishingTargetProxy = $this->getMock('TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget', array(), array(), '', false);
        $mockPublishingTargetProxy->expects($this->once())->method('getResourcesPublishingPath')->will($this->returnValue('vfs://Foo/Web/_Resources/'));

        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->once())->method('getHash')->will($this->returnValue('ResourceHash'));

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array(), array(), '', false);
        $mockResource->expects($this->once())->method('getPublishingConfiguration')->will($this->returnValue($mockPublishingConfiguration));
        $mockResource->expects($this->once())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
        $mockResource->expects($this->once())->method('getFileExtension')->will($this->returnValue('ResourceFileExtension'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->at(0))->method('getMethodArgument')->with('resource')->will($this->returnValue($mockResource));
        $mockJoinPoint->expects($this->at(1))->method('getMethodArgument')->with('returnFilename')->will($this->returnValue(false));
        $mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockPublishingTargetProxy));

        $mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface', array(), array(), '', false);
        $mockSession->expects($this->once())->method('getID')->will($this->returnValue('TheCurrentSessionId'));

        $mockAccessRestrictionPublisher = $this->getMock('\TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface', array(), array(), '', false);
        $mockAccessRestrictionPublisher->expects($this->once())->method('publishAccessRestrictionsForPath')->with('vfs://Foo/Web/_Resources/Persistent/TheCurrentSessionId/');

        $publishingAspect = $this->getAccessibleMock('TYPO3\Flow\Security\Aspect\PrivateResourcesPublishingAspect', array('dummy'), array(), '', false);
        $publishingAspect->_set('securityContext', $mockSecurityContext);
        $publishingAspect->_set('session', $mockSession);
        $publishingAspect->_set('settings', $settings);
        $publishingAspect->_set('accessRestrictionPublisher', $mockAccessRestrictionPublisher);

        $publishingAspect->_call('rewritePersistentResourcePublishPathAndFilenameForPrivateResources', $mockJoinPoint);
    }
}
