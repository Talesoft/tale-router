
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

$app->usePlugin(Router::class);

$router = $app->get(Router::class);
$router->get('/:controller?/:action?/:id?', function($request, $response) {
    
    var_dump($request->getAttribute('routeData')); // ['controller' => 'user', 'action' => 'details', 'id' => '1'];
    return $response;
});

$app->display();

```
