<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for the email uri view helper
 */
class EmailViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Uri\EmailViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = new \TYPO3\Fluid\ViewHelpers\Uri\EmailViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderPrependsEmailWithMailto()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('some@email.tld');

        $this->assertEquals('mailto:some@email.tld', $actualResult);
    }
}
