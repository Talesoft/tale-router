<?php

namespace Tale;

use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Method;
use Tale\Http\Runtime;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Router\Route;

class Router implements MiddlewareInterface
{
    use Runtime\MiddlewareTrait;

    private $_app;
    /** @var Route[] */
    private $_routes;

    public function __construct(App $app)
    {

        $this->_app = $app;
        $this->_routes = [];

        foreach ($app->getOption('router.routes', []) as $route => $handler)
            $this->addRoute(Route::create($route, $handler));
    }

    public function addRoute(Route $route)
    {

        $this->_routes[] = $route;
        return $this;
    }

    public function all($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::GET, Method::POST], $pattern, $handler)
        );
    }

    public function get($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::GET], $pattern, $handler)
        );
    }

    public function post($pattern, $handler)
    {

        return $this->addRoute(
            new Route([Method::POST], $pattern, $handler)
        );
    }

    public function route(ServerRequestInterface $request)
    {

        /** @var Route[] $routes */
        $routes = array_reverse($this->_routes);
        foreach ($routes as $route) {

            $path = $request->getUri()->getPath();
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

    protected function handleRequest()
    {

        $this->route($this->getRequest());
        return $this->handleNext();
    }
}