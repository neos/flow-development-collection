<?php
namespace Neos\Flow\Tests\Unit\Http\Fixtures;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SpyRequestHandler implements RequestHandlerInterface
{

    /**
     * @var ServerRequestInterface
     */
    protected $handledRequest;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->handledRequest = $request;
        return new Response();
    }

    public function getHandledRequest(): ServerRequestInterface
    {
        return $this->handledRequest;
    }
}
