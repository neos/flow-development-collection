<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 */
class BaseViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderTakesBaseUriFromControllerContext()
    {
        $baseUri = new \TYPO3\Flow\Http\Uri('http://typo3.org/');

        $this->request->expects($this->any())->method('getHttpRequest')->will($this->returnValue(\TYPO3\Flow\Http\Request::create($baseUri)));

        $viewHelper = new \TYPO3\Fluid\ViewHelpers\BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expectedResult = '<base href="' . $baseUri . '" />';
        $actualResult = $viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }
}
