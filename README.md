
# Tale Runtime
**A Tale Framework Component**

# What is Tale Runtime?

Tale Runtime is a small PSR-7 compliant, middleware-based HTTP runtime for any kind
of PHP project.

It acts as a foundation for web applications with PHP.

# Installation

Install via Composer

```bash
composer require "talesoft/tale-runtime:*"
composer install
```

# Usage

```php

$app = new Tale\Runtime\App();

class HelloMiddleware implements Tale\Runtime\MiddlewareInterface
{

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        $response->getBody()->write('Hello ');

        return $next($request, $response);
    }
}

class WorldMiddleware implements Tale\Runtime\MiddlewareInterface
{

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        $response->getBody()->write('World!');
        
        return $next($request, $response);
    }
}

$app = $app->with(new HelloMiddleware())
    ->with(new WorldMiddleware());
    

Tale\Http\Emitter::emit($app->run()); //Outputs "Hello World!" to the client
    
```
