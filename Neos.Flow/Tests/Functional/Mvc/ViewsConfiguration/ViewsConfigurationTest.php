<?php
namespace Neos\Flow\Tests\Functional\Mvc\ViewsConfiguration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Package\PackageManagerInterface;

use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the ActionController
 */
class ViewsConfigurationTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableHttpEnabled = true;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * Additional setup: Routes
     */
    public function setUp()
    {
        parent::setUp();

        $this->registerRoute('viewsconfigurationa', 'test/mvc/viewsconfigurationa(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
            '@controller' => 'ViewsConfigurationTestA',
            '@action' => 'first',
            '@format' => 'html'
        ]);

        $this->registerRoute('viewsconfigurationb', 'test/mvc/viewsconfigurationb(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
            '@controller' => 'ViewsConfigurationTestB',
            '@action' => 'first',
            '@format' => 'html'
        ]);

        $this->registerRoute('viewsconfigurationc', 'test/mvc/viewsconfigurationc(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
            '@controller' => 'ViewsConfigurationTestC',
            '@action' => 'index',
            '@format' => 'html'
        ]);
    }

    /**
     *
     *
     * @test
     */
    public function templatePathAndFilenameIsChanged()
    {
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/first');
        $this->assertEquals('Changed on Package Level', $response->getContent());
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationb/first');
        $this->assertEquals('Changed on Controller Level', $response->getContent());
    }

    /**
     *
     *
     * @test
     */
    public function viewObjectNameChanged()
    {
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationc/index');
        $this->assertEquals(Fixtures\TemplateView::class, $response->getContent());
    }

    /**
     * @test
     */
    public function changeTemplatePathAndFilenameForWidget()
    {
        if ($this->objectManager->get(PackageManagerInterface::class)->isPackageActive('Neos.FluidAdaptor') === false) {
            $this->markTestSkipped('No Fluid adaptor installed');
        }

        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/widget');
        $this->assertEquals('Changed on Package Level', trim($response->getContent()));
    }
}
