<?php

namespace Tale\Http\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Emitter;
use Tale\Runtime\App;
use Tale\Runtime\MiddlewareInterface;

class HelloMiddleware implements MiddlewareInterface
{

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        $response->getBody()->write('Hello ');

        return $next($request, $response);
    }
}

class WorldMiddleware implements MiddlewareInterface
{

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        $response = $next($request, $response);
        $response->getBody()->write('World!');
        return $response;
    }
}

class FuckingMiddleware implements MiddlewareInterface
{

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        $response->getBody()->write('fucking ');
        return $next($request, $response);
    }
}

class AppTest extends \PHPUnit_Framework_TestCase
{

    public function testMiddleware()
    {

        $app = new App();



        $this->assertEquals('Hello fucking World!',
            (string)$app->with(new HelloMiddleware())
                ->with(new WorldMiddleware())
                ->with(new FuckingMiddleware())
                ->run()->getBody()
        );
    }
}