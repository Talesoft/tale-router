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
     * @var App
     */
    private $app;

    /** @var Route[] */
    private $routes;

    /**
     * Router constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {

        $this->app = $app;
        $this->routes = [];

        foreach ($this->getOption('routes', []) as $route => $handler)
            $this->addRoute(Route::create($route, $handler));
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route)
    {

        $this->routes[] = $route;
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
        $routes = array_reverse($this->routes);
        foreach ($routes as $route) {

            $path = $request->getUri()->getPath();
            $baseUri = $this->getOption('baseUri', '');

            $len = strlen($baseUri);
            if ($len > 0) {

                if (strncmp($baseUri, $path, $len) !== 0)
                    continue;

                $path = substr($path, $len);
            }

            if (empty($path))
                $path = '/';

            $vars = null;
            if (!in_array($request->getMethod(), $route->getMethods())
                || ($vars = $route->match($path)) === false)
                continue;

            $subRequest = $request;
            foreach ($vars as $key => $value)
                $subRequest = $subRequest->withAttribute($key, $value);

            $this->app->prepend(function($request, $response, $next) use ($route, $subRequest) {

                $handler = $this->app->prepareMiddleware($route->getHandler());
                return $handler($subRequest, $response, $next);
            });
        }

        return $this;
    }

    /**
     * @param callable $next
     *
     * @return ResponseInterface
     */
    protected function handleRequest(callable $next)
    {
        $this->route($this->request);
        return $next($this->request, $this->response);
    }

    protected function getOptionNameSpace()
    {

        return 'router';
    }

    protected function getTargetConfigurableObject()
    {

        return $this->app;
    }
}