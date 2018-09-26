# Configuring Handlers To Require Authentication

In the [previous chapter](login-handler.md), we detailed writing a handler to
display a login form for submitting credentials. That handler has the form post
to the original URL in order to validate the credentials.

This means that the original handler needs to have the
`AuthenticationMiddleware` as part of its pipeline.

Additionally, this package depends on the zend-expressive-session
`SessionMiddleware` being present and in the pipeline before the
zend-expressive-authentication `AuthenticationMiddleware`, as the `PhpSession`
adapter it provides requires access to the session container via the request.

There are three ways to accomplish this:

- Requiring authentication everywhere.
- Requiring authentication for subpaths of the application.
- Requiring authentication for individual routes.

## Requiring authentication everywhere

With this approach, every request other than the one to the login form itself
will require authentication. To make this possible, you will need to decorate
the zend-expressive-authentication `AuthenticationMiddleware` so that you can
exclude that particular path.

As an example, you could do the following in the `config/pipeline.php` file,
before the `RouteMiddleware` somewhere:

```php
// Pipe in the session middleware
$app->pipe(Zend\Expressive\Session\SessionMiddleware::class);

// Grab the authentication middleware, and then decorate it when piping it to
// the application:
$authenticationMiddleware = $container->get(
    Zend\Expressive\Authentication\AuthenticationMiddleware::class
);
$app->pipe($factory->callable(
    function ($request, $handler) use ($authenticationMiddleware) {
        if ($request->getUri()->getPath() === '/login') {
            return $handler->handle($request);
        }

        return $authenticationMiddleware->process($request, $handler);
    }
));
```

## Requiring authentication for subpaths of the application

If you know all handlers under a given subpath of the application require
authentication, you can use Stratigility's [path segregation features](https://docs.zendframework.com/zend-stratigility/v3/api/#path)
to add authentication.

For example, consider the following within the `config/pipeline.php` file, which
adds authentication to any path starting with `/admin`:

```php
// Add this within the import section of the file:
use function Zend\Stratigility\path;

// Add this within the callback, before the routing middleware:
$app->pipe(path('/admin', $factory->pipeline(
    Zend\Expressive\Session\SessionMiddleware::class,
    Zend\Expressive\Authentication\AuthenticationMiddleware::class,
)));
```

## Requiring authentication for individual routes

The most granular approach involves adding authentication to individual routes.
In such cases, you will create a [route-specific middleware
pipeline](https://docs.zendframework.com/zend-expressive/v3/cookbook/route-specific-pipeline/).

As an example, if we wanted authentication for each of the routes that use the
path `/admin/users[/\d+]`, we could do the following within our
`config/routes.php` file:

```php
$app->get('/admin/users[/\d+]', [
    Zend\Expressive\Session\SessionMiddleware::class,
    Zend\Expressive\Authentication\AuthenticationMiddleware::class,
    App\Users\UsersHandler::class,
], 'users');
$app->post('/admin/users', [
    Zend\Expressive\Session\SessionMiddleware::class,
    Zend\Expressive\Authentication\AuthenticationMiddleware::class,
    App\Users\CreateUserHandler::class,
]);
$app->post('/admin/users[/\d+]', [
    Zend\Expressive\Session\SessionMiddleware::class,
    Zend\Expressive\Authentication\AuthenticationMiddleware::class,
    App\Users\UpdateUserHandler::class,
]);
```

Note that each pipeline contains both the session and authentication middleware!
