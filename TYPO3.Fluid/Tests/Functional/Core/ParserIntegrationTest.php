<?php
namespace TYPO3\Fluid\Tests\Functional\Core;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Tests\Functional\View\Fixtures\View\StandaloneView;
use TYPO3\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Testcase for Parser, checking whether basic parsing features work
 */
class ParserIntegrationTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableHttpEnabled = true;

    /**
     * @return array
     */
    public function exampleTemplates()
    {
        return array(
            'simple object access works' => array(
                'source' => 'Hallo {name}',
                'variables' => array('name' => 'Welt'),
                'Hallo Welt'
            ),
            'arrays as ViewHelper arguments work' => array(
                'source' => '<f:for each="{0: 10, 1: 20}" as="number">{number}</f:for>',
                'variables' => array(),
                '1020'
            ),
            'arrays outside ViewHelper arguments are not parsed' => array(
                'source' => '{0: 10, 1: 20}',
                'variables' => array(),
                '{0: 10, 1: 20}'
            )
        );
    }

    /**
     * @test
     * @dataProvider exampleTemplates
     */
    public function templateIsEvaluatedCorrectly($source, $variables, $expected)
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new \TYPO3\Flow\Mvc\ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, uniqid());
        $standaloneView->assignMultiple($variables);
        $standaloneView->setTemplateSource($source);

        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function layoutViewHelperCanContainIfCondition()
    {
        $request = Request::create(new Uri('http://localhost'));
        $actionRequest = $request->createActionRequest();

        $standaloneView = new StandaloneView($actionRequest, uniqid());

        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts');
        \file_put_contents('vfs://MyLayouts/foo', 'foo: <f:render section="content" />');
        \file_put_contents('vfs://MyLayouts/bar', 'bar: <f:render section="content" />');
        $standaloneView->setLayoutRootPath('vfs://MyLayouts');

        $source = '<f:layout name="{f:if(condition: \'1 == 1\', then: \'foo\', else: \'bar\')}" /><f:section name="content">Content</f:section>';
        $standaloneView->setTemplateSource($source);

        $uncompiledResult = $standaloneView->render();
        $compiledResult = $standaloneView->render();

        $this->assertSame($uncompiledResult, $compiledResult, 'The rendered compiled template did not match the rendered uncompiled template.');
        $this->assertSame('foo: Content', $standaloneView->render());
    }
}
