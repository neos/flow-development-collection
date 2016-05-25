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
use TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Tests\Functional\View\Fixtures\View\StandaloneView;

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
     * @var StandaloneView
     */
    protected $view;

    public function setUp()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new \TYPO3\Flow\Mvc\ActionRequest($httpRequest);

        $this->view = new StandaloneView($actionRequest, uniqid());
    }

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
        $this->view->assignMultiple($variables);
        $this->view->setTemplateSource($source);

        $actual = $this->view->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function layoutViewHelperCanContainIfCondition()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyLayouts');
        \file_put_contents('vfs://MyLayouts/foo', 'foo: <f:render section="content" />');
        \file_put_contents('vfs://MyLayouts/bar', 'bar: <f:render section="content" />');
        $this->view->setLayoutRootPath('vfs://MyLayouts');

        $source = '<f:layout name="{f:if(condition: \'1 == 1\', then: \'foo\', else: \'bar\')}" /><f:section name="content">Content</f:section>';
        $this->view->setTemplateSource($source);

        $uncompiledResult = $this->view->render();
        $compiledResult = $this->view->render();

        $this->assertSame($uncompiledResult, $compiledResult, 'The rendered compiled template did not match the rendered uncompiled template.');
        $this->assertSame('foo: Content', $this->view->render());
    }

    /**
     * @test
     */
    public function isMethodCanBeAccessedDirectly()
    {
        $post = new Post();
        $post->setPrivate(true);
        $this->view->assignMultiple(array('post' => $post));
        $this->view->setTemplateSource('{post.isPrivate}');

        $actual = $this->view->render();
        $this->assertSame('Private!', $actual);
    }

    /**
     * @test
     */
    public function getHasMethodIsStillUsed()
    {
        $post = new Post();
        $post->setCategory('SomeCategory');
        $this->view->assignMultiple(array('post' => $post));
        $this->view->setTemplateSource('<f:if condition="{post.hasCategory}">Private!</f:if>');

        $actual = $this->view->render();
        $this->assertSame('Private!', $actual);
    }

    /**
     * @test
     */
    public function propertiesThatStartWithIsAreStillAccessedNormally()
    {
        $post = new Post();
        $post->setPrivate(true);
        $this->view->assignMultiple(array('post' => $post));
        $this->view->setTemplateSource('<f:if condition="{post.isprivate}">Private!</f:if>');

        $actual = $this->view->render();
        $this->assertSame('', $actual);
    }

    /**
     * @test
     */
    public function nonExistingIsMethodWillNotThrowError()
    {
        $post = new Post();
        $post->setPrivate(true);
        $this->view->assignMultiple(array('post' => $post));
        $this->view->setTemplateSource('<f:if condition="{post.isNonExisting}">Wrong!</f:if>');

        $actual = $this->view->render();
        $this->assertSame('', $actual);
    }
}
