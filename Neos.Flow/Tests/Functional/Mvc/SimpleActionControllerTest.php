<?php
namespace Neos\Flow\Tests\Functional\Mvc;

use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 *
 */
class SimpleActionControllerTest extends FunctionalTestCase
{
    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * Additional setup: Routes
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoute('test', 'test/mvc/simplecontrollertest(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'SimpleActionControllerTest',
            '@action' => 'index',
            '@format' => 'html'
        ]);

        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
    }

    /**
     * Checks if a simple request is handled correctly. The route matching the
     * specified URI defines a default action "index" which results in indexAction
     * being called.
     *
     * @test
     */
    public function defaultActionSpecifiedInRouteIsCalledAndResponseIsReturned()
    {
        $response = $this->browser->request('http://localhost/test/mvc/simplecontrollertest');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('index', $response->getBody()->getContents());
    }

    /**
     * Checks if a simple request is handled correctly if another than the default
     * action is specified.
     *
     * @test
     */
    public function actionSpecifiedInActionRequestIsCalledAndResponseIsReturned()
    {
        $response = $this->browser->request('http://localhost/test/mvc/simplecontrollertest/simplereponse');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Simple', $response->getBody()->getContents());
    }

    /**
     * Checks if the content type and json content is correctly passed through
     *
     * @test
     */
    public function responseContainsContentTypeAndContent()
    {
        $response = $this->browser->request('http://localhost/test/mvc/simplecontrollertest/jsonresponse');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['application/json'], $response->getHeader('Content-Type'));
        self::assertEquals(json_encode(['foo' => 'bar', 'baz' => 123]), $response->getBody()->getContents());
    }

    /**
     * Checks if arguments and format are passed
     *
     * @test
     */
    public function responseContainsArgumentContent()
    {
        $argument = '<DIV><h1>Some markup</h1></DIV>';
        $response = $this->browser->request('http://localhost/test/mvc/simplecontrollertest/arguments?testArgument=' . urlencode($argument));
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['text/html'], $response->getHeader('Content-Type'));
        self::assertEquals(strtolower($argument), $response->getBody()->getContents());
    }
}
