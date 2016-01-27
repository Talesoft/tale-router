<?php

namespace Tale;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Method;
use Tale\Router\Args;
use Tale\Router\Route;
use Tale\Router\RouteInterface;
use Tale\Runtime\App;
use Tale\Runtime\MiddlewareInterface;

class Router implements MiddlewareInterface
{

    /** @var Route[] */
    private $_routes;

    public function __construct()
    {

        $this->_routes = [];
    }

    public function addRoute(RouteInterface $route)
    {

        $this->_routes[] = $route;
        return $this;
    }

    public function createRoute(array $methods, $pattern, $handler)
    {

        $route = new Route($methods, $pattern, $handler);
        return $this->addRoute($route);
    }

    public function all($pattern, $handler)
    {

        return $this->createRoute([Method::GET, Method::POST], $pattern, $handler);
    }

    public function get($pattern, $handler)
    {

        return $this->createRoute([Method::GET], $pattern, $handler);
    }

    public function post($pattern, $handler)
    {

        return $this->createRoute([Method::POST], $pattern, $handler);
    }

    public function getRegEx(RouteInterface $route)
    {

        return '/^'.str_replace('/', '\\/', preg_replace_callback('#(.)?:([a-z\_]\w*)(\?)?#i', function ($m) {

            $key = $m[2];
            $initiator = '';
            $optional = '';

            if (!empty($m[1])) {

                $initiator = '(?<'.$key.'Initiator>'.preg_quote($m[1]).')';
            }

            if (!empty($m[3]))
                $optional = '?';

            return '(?:'.$initiator.'(?<'.$key.'>[a-z0-9\_\-]*?))'.$optional;

        }, $route->getPattern())).'$/i';
    }

    public function match(RouteInterface $route, $string)
    {

        $isMatch = preg_match($this->getRegEx($route), $string, $matches);

        if (!$isMatch)
            return false;

        $vars = [];
        if (!empty($matches))
            foreach ($matches as $name => $value)
                if (is_string($name) && !empty($value))
                    $vars[$name] = $value;

        return $vars;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        $app = new App();
        /** @var ServerRequestInterface $request */
        $request = $request->withAttribute('router', $this);

        foreach ($this->_routes as $route) {

            if (in_array($request->getMethod(), $route->getMethods(), true)
                && ($data = $this->match($route, $request->getUri()->getPath())) !== false) {

                $app = $app->with(function(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    callable $next) use ($route, $data) {

                    return call_user_func(
                        $route->getHandler(),
                        $request->withAttribute('router.data', $data),
                        $response,
                        $next
                    );
                });
            }
        }

        $request = $request->withAttribute('router.result', $app->dispatch($request, $response));

        return $next($request, $response);
    }
}