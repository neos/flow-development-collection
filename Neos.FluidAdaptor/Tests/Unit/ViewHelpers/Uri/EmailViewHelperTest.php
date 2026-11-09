<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Uri;

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Uri\EmailViewHelper;
use PHPUnit\Framework\Attributes\Test;

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
 * Testcase for the email uri view helper
 */
final class EmailViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Uri\EmailViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(EmailViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderPrependsEmailWithMailto()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['email' => 'some@email.tld']);
        $actualResult = $this->viewHelper->render();

        self::assertEquals('mailto:some@email.tld', $actualResult);
    }
}
