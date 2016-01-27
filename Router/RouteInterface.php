<?php

namespace Tale\Router;

interface RouteInterface
{

    public function getMethods();
    public function getPattern();
    public function getHandler();
}