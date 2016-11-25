<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\FluidAdaptor\ViewHelpers\BaseViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 */
class BaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderTakesBaseUriFromControllerContext()
    {
        $baseUri = new Uri('http://typo3.org/');

        $this->request->expects($this->any())->method('getHttpRequest')->will($this->returnValue(Request::create($baseUri)));

        $viewHelper = new BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expectedResult = '<base href="' . htmlspecialchars($baseUri) . '" />';
        $actualResult = $viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderEscapesBaseUri()
    {
        $baseUri = new Uri('<some nasty uri>');

        $this->request->expects($this->any())->method('getHttpRequest')->will($this->returnValue(Request::create($baseUri)));

        $viewHelper = new BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expectedResult = '<base href="http://' . htmlspecialchars($baseUri) . '/" />';
        $actualResult = $viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }
}
