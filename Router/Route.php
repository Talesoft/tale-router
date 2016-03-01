<?php

namespace Tale\Router;

use Tale\Http\Method;
use InvalidArgumentException;

/**
 * Class Route
 *
 * @package Tale\Router
 */
class Route
{

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var (callable|string)[]
     */
    private $handler;

    /**
     * Route constructor.
     *
     * @param string[]        $methods
     * @param string          $pattern
     * @param callable|string $handler
     */
    public function __construct(array $methods, $pattern, $handler)
    {

        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;

        if (count($this->methods) < 1)
            throw new InvalidArgumentException(
                "Argument 1 passed to Route->__construct needs to have at ".
                "least one array item"
            );

        foreach ($this->methods as $i => $method) {

            if (!is_string($method) || !in_array($method, [Method::GET, Method::POST]))
                throw new InvalidArgumentException(
                    "Argument 1 passed to Route->__construct needs to be POST or GET"
                );
        }

        if (!is_string($pattern))
            throw new InvalidArgumentException(
                "Argument 2 passed to Route->__construct needs to be route pattern string"
            );

        if (!is_callable($handler) && !(is_string($handler) && class_exists($handler)))
            throw new InvalidArgumentException(
                "Argument 3 passed to Route->__construct needs to be valid callback, ".
                gettype($handler)." given"
            );
    }

    /**
     * @return string[]
     */
    public function getMethods()
    {

        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern()
    {

        return $this->pattern;
    }

    /**
     * @return callable|string
     */
    public function getHandler()
    {

        return $this->handler;
    }

    /**
     * @return string
     */
    public function getRegularExpression()
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
        }, $this->pattern)).'$/';
    }

    /**
     * @param string $string
     *
     * @return array|false
     */
    public function match($string)
    {

        $isMatch = preg_match($this->getRegularExpression(), $string, $matches);

        if (!$isMatch)
            return false;

        $vars = [];
        if (!empty($matches))
            foreach ($matches as $name => $value)
                if (is_string($name) && !empty($value))
                    $vars[$name] = $value;

        return $vars;
    }

    /**
     * @param string $route
     * @param callable|string $handler
     *
     * @return static
     */
    public static function create($route, $handler)
    {

        $methods = [Method::GET, Method::POST];
        if (preg_match('/^(?:(?<method>get|post|all)\s+)?/', $route, $matches)) {

            $method = strtoupper(isset($matches[1]) ? $matches[1] : 'all');

            if ($method !== 'ALL')
                $methods = [constant(Method::class."::$method")];

            $route = substr($route, strlen($matches[0]));
        }

        return new static($methods, $route, $handler);
    }
}