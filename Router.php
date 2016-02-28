<?php

namespace Tale;

use Tale\App\Plugin\Middleware;
use Tale\App\PluginInterface;
use Tale\App\PluginTrait;
use Tale\Http\Method;
use Tale\Http\Runtime;
use Tale\Router\Route;
use Tale\Router\RouteInterface;

class Router implements PluginInterface
{
    use PluginTrait;

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

    public function createRoute($route, $handler)
    {

        $methods = [Method::GET, Method::POST];
        if (preg_match('/^(?:(?<method>get|post|all)\s+)?/', $route, $matches)) {

            $method = strtoupper(isset($matches[1]) ? $matches[1] : 'all');

            if ($method !== 'ALL')
                $methods = [constant(Method::class."::$method")];

            $route = substr($route, strlen($matches[0]));
        }

        if (is_string($handler) && $handler[0] === '@') {

            $className = substr($handler, 1 );
            $app = $this->getApp();

            if (is_subclass_of($className, PluginInterface::class))
                $handler = new Middleware($app, $className);
            else {

                if (!$app->has($className))
                    $app->register($className);

                $handler = $app->get($className);
            }
        }

        if (!Runtime::isMiddleware($handler))
            throw new \InvalidArgumentException(
                "Passed handler for route $route is not a valid ".
                "middleware"
            );

        return new Route($methods, $route, $handler);
    }

    public function all($pattern, $handler)
    {

        return $this->addRoute(
            $this->createRoute($pattern, $handler)
        );
    }

    public function get($pattern, $handler)
    {

        return $this->addRoute(
            $this->createRoute("GET $pattern", $handler)
        );
    }

    public function post($pattern, $handler)
    {

        return $this->addRoute(
            $this->createRoute("POST $pattern", $handler)
        );
    }

    public function getRegularExpression(RouteInterface $route)
    {

        return '/^'.str_replace('/', '\\/', preg_replace_callback(
            '#(.)?:([a-zA-Z\-\_][a-zA-Z0-9\-\_]*)(\?)?#i',
            function ($matches) {

                $key = $matches[2];
                $initiator = '';
                $optional = '';

                if (!empty($matches[1]))
                    $initiator = '(?<'.$key.'Initiator>'.preg_quote($matches[1]).')';

                if (!empty($matches[3]))
                    $optional = '?';

                return '(?:'.$initiator.'(?<'.$key.'>[a-zA-Z0-9\-\_]+?))'.$optional;
        }, $route->getPattern())).'$/';
    }

    public function match(RouteInterface $route, $string)
    {

        $isMatch = preg_match($this->getRegularExpression($route), $string, $matches);

        if (!$isMatch)
            return false;

        $vars = [];
        if (!empty($matches))
            foreach ($matches as $name => $value)
                if (is_string($name) && !empty($value))
                    $vars[$name] = $value;

        return $vars;
    }

    protected function handle()
    {

        $app = $this->getApp();
        $configuredRoutes = $app->getOption('router.routes', []);
        $routes = $this->_routes;

        $request = $this->getRequest();
        $response = $this->getResponse();

        foreach ($configuredRoutes as $route => $handler)
            $routes[] = $this->createRoute($route, $handler);

        foreach ($routes as $route) {

            $vars = null;
            if (!in_array($request->getMethod(), $route->getMethods())
                || ($vars = $this->match($route, $request->getRequestTarget())) === false)
                continue;

            foreach ($vars as $key => $value)
                $request = $request->withAttribute($key, $value);

            return call_user_func(
                $route->getHandler(),
                $request,
                $response,
                $this->_next
            );
        }

        return $this->next();
    }
}