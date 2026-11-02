<?php

declare(strict_types=1);

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
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\TemplateView;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for the ActionController
 */
final class ViewsConfigurationTest extends FunctionalTestCase
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
    protected function setUp(): void
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

    #[Test]
    public function templatePathAndFilenameIsChanged()
    {
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/first');
        self::assertEquals('Changed on Package Level', $response->getBody()->getContents());
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationb/first');
        self::assertEquals('Changed on Controller Level', $response->getBody()->getContents());
    }

    #[Test]
    public function viewObjectNameChanged()
    {
        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationc/index');
        self::assertEquals(TemplateView::class, $response->getBody()->getContents());
    }

    #[Test]
    public function changeTemplatePathAndFilenameForWidget()
    {
        if ($this->objectManager->get(PackageManager::class)->isPackageAvailable('Neos.FluidAdaptor') === false) {
            $this->markTestSkipped('No Fluid adaptor installed');
        }

        $response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/widget');
        self::assertSame('Changed on Package Level', trim($response->getBody()->getContents()));
    }
}
