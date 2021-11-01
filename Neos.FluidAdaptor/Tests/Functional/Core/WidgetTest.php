<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the widget mechanism
 */
class WidgetTest extends FunctionalTestCase
{
    /**
     * Additional setup: Routes
     */
    protected function setUp(): void
    {
        parent::setUp();

        $route = new Route();
        $route->setName('WidgetTest');
        $route->setUriPattern('test/widget/{@controller}(/{@action})');
        $route->setDefaults([
            '@package' => 'Neos.FluidAdaptor',
            '@subpackage' => 'Tests\Functional\Core\Fixtures',
            '@action' => 'index',
            '@format' => 'html'
        ]);
        $route->setAppendExceedingArguments(true);
        $this->router->addRoute($route);
    }

    /**
     * This sends a request to the helper controller (AjaxTestController) which includes
     * the AJAX widget in its template. The indexAction renders that template which
     * in turn lets the "someAjax" widget call the indexAction of its own controller
     * (SomeAjaxController).
     *
     * @test
     */
    public function ifIncludedInATemplateTheWidgetReturnsResultOfItsOwnIndexAction(): void
    {
        $response = $this->browser->request('http://localhost/test/widget/ajaxtest');
        [$confirmation,] = explode(chr(10), $response->getBody()->getContents());
        self::assertSame('SomeAjaxController::indexAction()', $confirmation);
    }

    /**
     * This sends a request to the helper controller (AjaxTestController) which includes
     * the AJAX widget in its template. The second line of the output created by the
     * indexAction() of the "someAjax" widget contains a URI which allows for directly
     * sending a request (from outside) to the widget, calling the ajaxAction().
     *
     * We send a request to this URI and check if the AJAX widget was really invoked.
     *
     * @test
     */
    public function theGeneratedUriLeadsToASpecificActionOfTheAjaxController(): void
    {
        $response = $this->browser->request('http://localhost/test/widget/ajaxtest');
        [, $ajaxWidgetUri] = explode(chr(10), $response->getBody()->getContents());

        $response = $this->browser->request('http://localhost/' . $ajaxWidgetUri);
        self::assertSame('SomeAjaxController::ajaxAction("value1", "value2")', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function redirectWithoutDelayAndNoParameterImmediatelyRedirectsToTargetAction(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="redirect-no-delay-no-param"]')->attr('href');
        $response = $this->browser->request(urldecode($redirectTriggerUri));
        $response->getBody()->rewind();
        self::assertSame('<div id="parameter"></div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function redirectWithoutDelayAndWithParameterImmediatelyRedirectsToTargetAction(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="redirect-no-delay-with-param"]')->attr('href');
        $response = $this->browser->request(urldecode($redirectTriggerUri));
        self::assertSame('<div id="parameter">foo, via redirect</div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function redirectWithDelayAndNoParameterOutputsRefreshMetaHeader(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="redirect-with-delay-no-param"]')->attr('href');

        $this->browser->setFollowRedirects(false);
        $this->browser->request(urldecode($redirectTriggerUri));
        $this->browser->setFollowRedirects(true);
        $redirectHeader = $this->browser->getCrawler()->filterXPath('//meta[@http-equiv="refresh"]')->attr('content');
        self::assertSame('2;url=', substr($redirectHeader, 0, 6));

        $redirectTargetUri = substr($redirectHeader, 6);
        $response = $this->browser->request(urldecode($redirectTargetUri));
        self::assertSame('<div id="parameter"></div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function redirectWithDelayAndWithParameterOutputsRefreshMetaHeader(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="redirect-with-delay-with-param"]')->attr('href');

        $this->browser->setFollowRedirects(false);
        $this->browser->request(urldecode($redirectTriggerUri));
        $this->browser->setFollowRedirects(true);
        $redirectHeader = $this->browser->getCrawler()->filterXPath('//meta[@http-equiv="refresh"]')->attr('content');
        self::assertSame('2;url=', substr($redirectHeader, 0, 6));

        $redirectTargetUri = substr($redirectHeader, 6);
        $response = $this->browser->request(urldecode($redirectTargetUri));
        self::assertSame('<div id="parameter">bar, via redirect</div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function redirectToDifferentControllerThrowsException(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="redirect-other-controller"]')->attr('href');

        $response = $this->browser->request(urldecode($redirectTriggerUri));
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('1380284579', $response->getHeaderLine('X-Flow-ExceptionCode'));
    }

    /**
     * @test
     */
    public function forwardWithoutParameterTriggersTargetAction(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="forward-no-param"]')->attr('href');

        $response = $this->browser->request(urldecode($redirectTriggerUri));
        self::assertSame('<div id="parameter"></div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function forwardWithParameterTriggersTargetAction(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="forward-with-param"]')->attr('href');

        $response = $this->browser->request(urldecode($redirectTriggerUri));
        self::assertSame('<div id="parameter">baz, via forward</div>', trim($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function forwardToDifferentControllerThrowsException(): void
    {
        $this->browser->request('http://localhost/test/widget/redirecttest');
        $redirectTriggerUri = $this->browser->getCrawler()->filterXPath('//*[@id="forward-other-controller"]')->attr('href');

        $response = $this->browser->request(urldecode($redirectTriggerUri));
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('1380284579', $response->getHeaderLine('X-Flow-ExceptionCode'));
    }

    /**
     * @test
     */
    public function aCustomViewResponseIsRespectedInAjaxContext(): void
    {
        $response = $this->browser->request('http://localhost/test/widget/ajaxtest');
        [,, $ajaxWidgetUri] = explode(chr(10), $response->getBody()->getContents());

        $response = $this->browser->request('http://localhost/' . $ajaxWidgetUri . '&@format=custom');
        self::assertSame(418, $response->getStatusCode());
        self::assertSame('Hello World!', $response->getBody()->getContents());
    }
}
