<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Config\DelegateTrait;
use Tale\Http\Method;
use Tale\Http\Runtime;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\MiddlewareTrait;
use Tale\Router\Route;

/**
 * Class Router
 *
 * @package Tale
 */
class Router implements MiddlewareInterface
{
    use MiddlewareTrait;
    use DelegateTrait;

    /**
     * @var \Tale\App
     */
    private $_app;

    /** @var Route[] */
    private $_routes;

    /**
     * Router constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {

        $this->_app = $app;
        $this->_routes = [];

        foreach ($app->getOption('routes', []) as $route => $handler)
            $this->addRoute(Route::create($route, $handler));
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route)
    {

        $this->_routes[] = $route;
        return $this;
    }

    /**
     * @param string $pattern
     * @param callable|string $handler
     *
     * @return Router
     */
    public function all($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::GET, Method::POST], $pattern, $handler)
        );
    }

    /**
     * @param string $pattern
     * @param callable|string $handler
     *
     * @return Router
     */
    public function get($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::GET], $pattern, $handler)
        );
    }

    /**
     * @param string $pattern
     * @param callable|string $handler
     *
     * @return Router
     */
    public function post($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::POST], $pattern, $handler)
        );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function route(ServerRequestInterface $request)
    {

        /** @var Route[] $routes */
        $routes = array_reverse($this->_routes);
        foreach ($routes as $route) {

            $baseUri = $this->getOption('baseUri', '');
            $path = $baseUri.$request->getUri()->getPath();

            if (empty($path))
                $path = '/';

            $vars = null;
            if (!in_array($request->getMethod(), $route->getMethods())
                || ($vars = $route->match($path)) === false)
                continue;

            $subRequest = $request;
            foreach ($vars as $key => $value)
                $subRequest = $subRequest->withAttribute($key, $value);

            $this->_app->prepend(function($request, $response, $next) use ($route, $subRequest) {

                $handler = $this->_app->prepareMiddleware($route->getHandler());
                return $handler($subRequest, $response, $next);
            });
        }

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    protected function handleRequest()
    {

        $this->route($this->getRequest());
        return $this->handleNext();
    }

    protected function getOptionNameSpace()
    {

        return 'router';
    }

    protected function getTargetConfigurableObject()
    {

        return $this->_app;
    }
}