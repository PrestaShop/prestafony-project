# Create modern module controllers

> This feature is available since 1.7.5.

* Since 1.7.3 you create and override templates and services in your modules.
* Since 1.7.4, you can create and override forms and console commands.
* Since 1.7.5, you can create your own "modern" controllers!

## What does it means?

This means you can rely on modern environment to add new entry points to your applications.
Using modern pages, you will have access to the PrestaShop debug toolbar, the service container, Twig and Doctrine.
For your views, the PrestaShop UI Kit is available, on top of Bootstrap 4 and ensuring your views are consistent with the PrestaShop Back Office.

## How to declare a new Controller?

Somewhere in your module declare a new class that will act as a Controller:

```php
// modules/your-module/controller/DemoController.php

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class DemoController extends FrameworkBundleAdminController
{
    public function demoAction()
    {
        return $this->render('@Modules/your-module/templates/admin/demo.html.twig');
    }
}
```

You have access to the Container, to Twig as rendering engine, the Doctrine ORM, everything from Symfony framework ecosystem.
Note that you must return a `Response` object, but this can be a `JsonResponse` if you plan to make a single point application (or "SPA").

> This controller is the same than the ones used in Back Office. 

Now we have created your controller, you need to declare a route. A route map an action of your controller to an URI.

## How to map an action of your controller to an URI?

This is really simple (and very well documented in [Routing component documentation](https://symfony.com/doc/3.4/routing.html)):

For instance:

```yaml
# modules/your-module/config/routes.yml
your_route_name:
    path: your-module/demo
    methods: [GET]
    defaults:
      _controller: 'DemoController::demoAction'
```

> Any callable can be used to populate the ``_controller`` attribute, you don't even need to create your own controller!
  For instance, this could be a public function from your module main class.

