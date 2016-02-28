<?php

namespace Tale\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App;
use Tale\Http\ServerRequest;
use Tale\Http\Uri;
use Tale\Router;

class Dispatcher implements App\PluginInterface
{
    use App\PluginTrait;

    protected function handle()
    {

        $request = $this->getRequest();
        $this->setResponse(
            $this->getResponse()
                ->withHeader('X-Controller', $request->getAttribute('controller', 'index'))
                ->withHeader('X-Action', $request->getAttribute('action', 'index'))
                ->withHeader('X-ID', $request->getAttribute('id', ''))
                ->withHeader('X-Format', $request->getAttribute('format', 'html'))
        );

        return $this->next();
    }
}

class RouterTest extends \PHPUnit_Framework_TestCase
{

    public function testRouting()
    {

        $app = new App([
            'router' => [
                'routes' => [
                    '/some-file' => function(ServerRequestInterface $request, ResponseInterface $response) {

                        return $response->withHeader('Test-Header', 'some-file');
                    },
                    '/some-other-file' => function(ServerRequestInterface $request, ResponseInterface $response) {

                        return $response->withHeader('Test-Header', 'some-other-file');
                    },
                    '/:controller?/:action?/:id?.:format?' => '@Tale\\Test\\Dispatcher'
                ]
            ]
        ]);

        $app->usePlugin(Router::class);

        $response = $app->dispatch(new ServerRequest((new Uri())->withPath('/some-file')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('test-header'));
        $this->assertEquals('some-file', $response->getHeaderLine('test-header'));

        $response = $app->dispatch(new ServerRequest((new Uri())->withPath('/some-other-file')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('test-header'));
        $this->assertEquals('some-other-file', $response->getHeaderLine('test-header'));

        $response = $app->dispatch(new ServerRequest((new Uri())->withPath('/some-controller')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /some-controller');
        $this->assertEquals('some-controller', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /some-controller');
        $this->assertEquals('index', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /some-controller');
        $this->assertEquals('', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /some-controller');
        $this->assertEquals('html', $response->getHeaderLine('x-format'));


        $response = $app->dispatch(new ServerRequest((new Uri())->withPath('/user/details/1.json')));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('x-controller'), 'x-controller /user/details...');
        $this->assertEquals('user', $response->getHeaderLine('x-controller'));
        $this->assertTrue($response->hasHeader('x-action'), 'x-action /user/details...');
        $this->assertEquals('details', $response->getHeaderLine('x-action'));
        $this->assertTrue($response->hasHeader('x-id'), 'x-id /user/details...');
        $this->assertEquals('1', $response->getHeaderLine('x-id'));
        $this->assertTrue($response->hasHeader('x-format'), 'x-format /user/details...');
        $this->assertEquals('json', $response->getHeaderLine('x-format'));
    }
}