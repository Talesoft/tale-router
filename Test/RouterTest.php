<?php

namespace Tale\Test;

use Psr\Http\Message\ResponseInterface;
use Tale\App;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\MiddlewareTrait;
use Tale\Http\ServerRequest;
use Tale\Http\Uri;
use Tale\Router;

class Dispatcher implements MiddlewareInterface
{
    use MiddlewareTrait;

    protected function handleRequest(callable $next)
    {

        return $next($this->request, $this->response
            ->withHeader('X-Controller', $this->request->getAttribute('controller', 'index'))
            ->withHeader('X-Action', $this->request->getAttribute('action', 'index'))
            ->withHeader('X-ID', $this->request->getAttribute('id', ''))
            ->withHeader('X-Format', $this->request->getAttribute('format', 'html'))
        );
    }
}

class RouterTest extends \PHPUnit_Framework_TestCase
{

    public function testRouting()
    {

        $app = new App([
            'router' => [
                'routes' => [
                    '/.*' => function($req, ResponseInterface $res, callable $next) {

                        return $next($req, $res->withHeader('X-Global', 'global as fuck'));
                    },
                    '/some-file' => function($req, ResponseInterface $res) {

                        return $res->withHeader('X-Test-Header', 'some-file');
                    },
                    '/some-other-file' => function($req, ResponseInterface $res) {

                        return $res->withHeader('X-Test-Header', 'some-other-file');
                    },
                    '/:controller?/:action?/:id?.:format?' => Dispatcher::class
                ]
            ]
        ]);

        $app->append(Router::class);

        $response = $app->run(new ServerRequest((new Uri())->withPath('/some-file')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-test-header'), 'x-test-header /some-file');
        $this->assertEquals('some-file', $response->getHeaderLine('x-test-header'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /some-file');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));

        $response = $app->run(new ServerRequest((new Uri())->withPath('/some-other-file')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-test-header'), 'x-test-header /some-other-file');
        $this->assertEquals('some-other-file', $response->getHeaderLine('x-test-header'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /some-other-file');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));

        $response = $app->run(new ServerRequest((new Uri())->withPath('/some-controller')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /some-controller');
        $this->assertEquals('some-controller', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /some-controller');
        $this->assertEquals('index', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /some-controller');
        $this->assertEquals('', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /some-controller');
        $this->assertEquals('html', $response->getHeaderLine('x-format'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /some-controller');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));


        $response = $app->run(new ServerRequest((new Uri())->withPath('/user/details/1.json')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /user/details...');
        $this->assertEquals('user', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /user/details...');
        $this->assertEquals('details', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /user/details...');
        $this->assertEquals('1', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /user/details...');
        $this->assertEquals('json', $response->getHeaderLine('x-format'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /user/details...');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));
    }

    public function testBaseUri()
    {

        $app = new App([
            'router' => [
                'baseUri' => '/sub-path',
                'routes' => [
                    '/.*' => function($req, ResponseInterface $res, callable $next) {

                        return $next($req, $res->withHeader('X-Global', 'global as fuck'));
                    },
                    '/:controller?/:action?/:id?.:format?' => Dispatcher::class
                ]
            ]
        ]);

        $app->append(Router::class);

        $response = $app->run(new ServerRequest((new Uri())->withPath('/sub-path/some-controller')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /some-controller');
        $this->assertEquals('some-controller', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /some-controller');
        $this->assertEquals('index', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /some-controller');
        $this->assertEquals('', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /some-controller');
        $this->assertEquals('html', $response->getHeaderLine('x-format'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /some-controller');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));


        $response = $app->run(new ServerRequest((new Uri())->withPath('/sub-path/user/details/1.json')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /user/details...');
        $this->assertEquals('user', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /user/details...');
        $this->assertEquals('details', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /user/details...');
        $this->assertEquals('1', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /user/details...');
        $this->assertEquals('json', $response->getHeaderLine('x-format'));
        $this->assertTrue($response->hasHeader('x-global'), 'x-global /user/details...');
        $this->assertEquals('global as fuck', $response->getHeaderLine('x-global'));
    }
}