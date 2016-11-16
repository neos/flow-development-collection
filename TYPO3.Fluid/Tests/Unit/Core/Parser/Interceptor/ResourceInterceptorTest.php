<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\Interceptor;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\ObjectManagement\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Parser\Interceptor\ResourceInterceptor;
use TYPO3\Fluid\Core\Parser\InterceptorInterface;
use TYPO3\Fluid\Core\Parser\ParsingState;
use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper;

/**
 * Testcase for Interceptor\ResourceInterceptor
 *
 */
class ResourceInterceptorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resourcesInCssUrlsAreReplacedCorrectly()
    {
        $mockDummyNode = $this->createMock(NodeInterface::class);
        $mockPathNode = $this->createMock(NodeInterface::class);
        $mockViewHelper = $this->createMock(AbstractViewHelper::class);

        $originalText1 = '<style type="text/css">
			#loginscreen {
				height: 768px;
				background-image: url(';
        $originalText2 = '../../../../Public/Backend/Media/Images/Login/MockLoginScreen.png';
        $path = 'Backend/Media/Images/Login/MockLoginScreen.png';
        $originalText3 = ')
				background-repeat: no-repeat;
			}';
        $originalText = $originalText1 . $originalText2 . $originalText3;
        $mockTextNode = $this->getMockBuilder(TextNode::class)->setMethods(array('evaluateChildNodes'))->setConstructorArgs(array($originalText))->getMock();
        $this->assertEquals($originalText, $mockTextNode->evaluate($this->createMock(RenderingContextInterface::class)));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(RootNode::class)->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(1))->method('get')->with(TextNode::class, $originalText1)->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(2))->method('get')->with(TextNode::class, $path)->will($this->returnValue($mockPathNode));
        $mockObjectManager->expects($this->at(3))->method('get')->with(ResourceViewHelper::class)->will($this->returnValue($mockViewHelper));
        $mockObjectManager->expects($this->at(4))->method('get')->with(ViewHelperNode::class, $mockViewHelper, array('path' => $mockPathNode))->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(5))->method('get')->with(TextNode::class, $originalText3)->will($this->returnValue($mockDummyNode));

        $interceptor = new ResourceInterceptor();
        $interceptor->injectObjectManager($mockObjectManager);
        $interceptor->process($mockTextNode, InterceptorInterface::INTERCEPT_TEXT, $this->createMock(ParsingState::class));
    }

    /**
     * Return source parts and expected results.
     *
     * @return array
     * @see supportedUrlsAreDetected
     */
    public function supportedUrls()
    {
        return array(
            array( // mostly harmless
                '<link rel="stylesheet" type="text/css" href="',
                '../../../Public/Backend/Styles/Login.css',
                '">',
                'Backend/Styles/Login.css',
                'Acme.Demo'
            ),
            array( // refer to another package
                '<link rel="stylesheet" type="text/css" href="',
                '../../../../Acme.OtherPackage/Resources/Public/Backend/Styles/FromOtherPackage.css',
                '">',
                'Backend/Styles/FromOtherPackage.css',
                'Acme.OtherPackage'
            ),
            array( // refer to another package in different category
                '<link rel="stylesheet" type="text/css" href="',
                '../../../Plugins/Acme.OtherPackage/Resources/Public/Backend/Styles/FromOtherPackage.css',
                '">',
                'Backend/Styles/FromOtherPackage.css',
                'Acme.OtherPackage'
            ),
            array( // path with spaces (ugh!)
                '<link rel="stylesheet" type="text/css" href="',
                '../../Public/Backend/Styles/With Spaces.css',
                '">',
                'Backend/Styles/With Spaces.css',
                'Acme.Demo'
            ),
            array( // single quote around url and spaces
                '<link rel="stylesheet" type="text/css" href=\'',
                '../Public/Backend/Styles/With Spaces.css',
                '\'>',
                'Backend/Styles/With Spaces.css',
                'Acme.Demo'
            )
        );
    }

    /**
     * @dataProvider supportedUrls
     * @test
     */
    public function supportedUrlsAreDetected($part1, $part2, $part3, $expectedPath, $expectedPackageKey)
    {
        $mockDummyNode = $this->createMock(NodeInterface::class);
        $mockPathNode = $this->createMock(NodeInterface::class);
        $mockPackageKeyNode = $this->createMock(NodeInterface::class);
        $mockViewHelper = $this->createMock(AbstractViewHelper::class);

        $originalText = $part1 . $part2 . $part3;
        $mockTextNode = $this->getMockBuilder(TextNode::class)->setMethods(array('evaluateChildNodes'))->setConstructorArgs(array($originalText))->getMock();
        $this->assertEquals($originalText, $mockTextNode->evaluate($this->createMock(RenderingContextInterface::class)));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(RootNode::class)->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(1))->method('get')->with(TextNode::class, $part1)->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(2))->method('get')->with(TextNode::class, $expectedPath)->will($this->returnValue($mockPathNode));
        $mockObjectManager->expects($this->at(3))->method('get')->with(TextNode::class, $expectedPackageKey)->will($this->returnValue($mockPackageKeyNode));
        $mockObjectManager->expects($this->at(4))->method('get')->with(ResourceViewHelper::class)->will($this->returnValue($mockViewHelper));
        $mockObjectManager->expects($this->at(5))->method('get')->with(ViewHelperNode::class, $mockViewHelper, array('path' => $mockPathNode, 'package' => $mockPackageKeyNode))->will($this->returnValue($mockDummyNode));
        $mockObjectManager->expects($this->at(6))->method('get')->with(TextNode::class, $part3)->will($this->returnValue($mockDummyNode));

        $interceptor = new ResourceInterceptor();
        $interceptor->injectObjectManager($mockObjectManager);
        $interceptor->setDefaultPackageKey('Acme.Demo');
        $interceptor->process($mockTextNode, InterceptorInterface::INTERCEPT_TEXT, $this->createMock(ParsingState::class));
    }
}
