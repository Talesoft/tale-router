
# Tale Router
**A Tale Framework Component**

# What is Tale Router?


# Installation

Install via Composer

```bash
composer require "talesoft/tale-router:*"
composer install
```

# Usage

```php

use Tale\App;
use Tale\Router;

$app = new App();

$app->append(Router::class);

$router = $app->get(Router::class);
$router->get('/:controller?/:action?/:id?.:format?', function($request, $response, $next) {
    
    $controller = $request->getAttribute('controller', 'index');
    
    //Handle controller $controller
    
    return $next($request, $response);
});

$app->display();

```
