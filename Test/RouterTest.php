<?php

namespace Tale\Http\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Response;
use Tale\Http\ServerRequest;
use Tale\Http\Uri;
use Tale\Router;
use Tale\Runtime\App;

class RouterTest extends \PHPUnit_Framework_TestCase
{

    public function testRouting()
    {

        $router = new Router();

        $called = false;
        $app = (new App())
            ->with($router)
            ->with(function(ServerRequestInterface $request) use (&$called) {

                $response = $request->getAttribute('router.result');

                $this->assertInstanceOf(ResponseInterface::class, $response, 'is instance of');
                $this->assertTrue($response->hasHeader('test-global-header'), 'has global header');
                $this->assertTrue($response->hasHeader('test-header'), 'has header');
                $this->assertFalse($response->hasHeader('test-negative-header'), 'has negative header');
                $this->assertEquals(['Test-Value'], $response->getHeader('test-global-header'), 'global header equals');
                $this->assertEquals(['Test-Value'], $response->getHeader('test-header'), 'header equals');

                $called = true;
            });

        $router->all('/.*', function(ServerRequestInterface $request, ResponseInterface $response, callable $next) {

            return $next($request, $response->withHeader('Test-Global-Header', 'Test-Value'));
        });

        $router->get('/some-file', function(ServerRequestInterface $request, ResponseInterface $response) {

            return $response->withHeader('Test-Header', 'Test-Value');
        });

        $router->get('/some-other-file', function(ServerRequestInterface $request, ResponseInterface $response) {

            return $response->withHeader('Test-Negative-Header', 'Test-Value');
        });

        $request = new ServerRequest((new Uri())->withPath('/some-file'));
        $app->dispatch($request, new Response());
        $this->assertTrue($called, 'route worker called');
    }
}