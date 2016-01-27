<?php

namespace Tale\Router;

use Tale\Http\Method;
use InvalidArgumentException;

class Route implements RouteInterface
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

        if (!is_callable($handler))
            throw new InvalidArgumentException(
                "Argument 3 passed to Route->__construct needs to be valid callback"
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
}