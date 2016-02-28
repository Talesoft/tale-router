<?php

namespace Tale\Router;

use Tale\Http\Method;
use InvalidArgumentException;

class Route
{

    private $_methods;
    private $_pattern;
    private $_handler;

    public function __construct(array $methods, $pattern, $handler)
    {

        $this->_methods = $methods;
        $this->_pattern = $pattern;
        $this->_handler = $handler;

        if (count($this->_methods) < 1)
            throw new InvalidArgumentException(
                "Argument 1 passed to Route->__construct needs to have at ".
                "least one array item"
            );

        foreach ($this->_methods as $i => $method) {

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
     * @return string
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->_pattern;
    }

    /**
     * @return callable
     */
    public function getHandler()
    {
        return $this->_handler;
    }

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
        }, $this->_pattern)).'$/';
    }

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