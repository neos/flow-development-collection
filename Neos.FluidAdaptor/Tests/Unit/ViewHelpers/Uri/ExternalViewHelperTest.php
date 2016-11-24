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

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = new \Neos\FluidAdaptor\ViewHelpers\Uri\ExternalViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderReturnsSpecifiedUri()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('http://www.some-domain.tld');

        $this->assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('www.some-domain.tld');

        $this->assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('some-domain.tld', 'ftp');

        $this->assertEquals('ftp://some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('some-domain.tld', '');

        $this->assertEquals('some-domain.tld', $actualResult);
    }
}
