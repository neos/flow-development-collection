<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for the external uri view helper
 *
 */
class ExternalViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Uri\ExternalViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Uri\ExternalViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderReturnsSpecifiedUri()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'http://www.some-domain.tld']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'www.some-domain.tld']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'some-domain.tld', 'defaultScheme' => 'ftp']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('ftp://some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'some-domain.tld', 'defaultScheme' => '']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('some-domain.tld', $actualResult);
    }
}
