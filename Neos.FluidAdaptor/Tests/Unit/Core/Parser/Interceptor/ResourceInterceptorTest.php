<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\Parser\Interceptor;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Parser\Interceptor\ResourceInterceptor;
use Neos\FluidAdaptor\Core\Parser\SyntaxTree\ResourceUriNode;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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

        $interceptor = new ResourceInterceptor();
        $resultingNodeTree = $interceptor->process($mockTextNode, InterceptorInterface::INTERCEPT_TEXT, $this->createMock(ParsingState::class));
        $this->assertInstanceOf(RootNode::class, $resultingNodeTree);
        $this->assertCount(3, $resultingNodeTree->getChildNodes());
        foreach ($resultingNodeTree->getChildNodes() as $parserNode) {
            if ($parserNode instanceof ResourceUriNode) {
                $this->assertEquals([
                    'path' => $path
                ], $parserNode->getArguments());
            }
        }
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
        $originalText = $part1 . $part2 . $part3;
        $mockTextNode = $this->getMockBuilder(TextNode::class)->setMethods(array('evaluateChildNodes'))->setConstructorArgs(array($originalText))->getMock();
        $this->assertEquals($originalText, $mockTextNode->evaluate($this->createMock(RenderingContextInterface::class)));

        $interceptor = new ResourceInterceptor();
        $interceptor->setDefaultPackageKey('Acme.Demo');
        $resultingNodeTree = $interceptor->process($mockTextNode, InterceptorInterface::INTERCEPT_TEXT, $this->createMock(ParsingState::class));

        $this->assertInstanceOf(RootNode::class, $resultingNodeTree);
        $this->assertCount(3, $resultingNodeTree->getChildNodes());
        foreach ($resultingNodeTree->getChildNodes() as $parserNode) {
            if ($parserNode instanceof ResourceUriNode) {
                $this->assertEquals([
                    'path' => $expectedPath,
                    'package' => $expectedPackageKey
                ], $parserNode->getArguments());
            }
        }
    }
}
