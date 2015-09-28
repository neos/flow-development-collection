<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
