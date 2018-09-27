# Handling an initial login

When you have configured the adapter, you can drop in the
zend-expressive-authentication `AuthenticationMiddleware` anywhere you need to
ensure you have an authenticated user. However, how do you handle the initial
authentication?

In the [previous chapter](config.md), we indicated that you need to configure a
path to which to redirect when the adapter does not detect a user. In this
chapter, we'll detail how to create a handler for getting these details, as well
as how to set up routing for it.

Roughly, the steps are:

- Create a handler that will display a form, directing it to the originally
  requested location.
- Create a template with a form for capturing the username and password, with
  the form action set to the originally requested location.
- Create a route to the new handler.

## Create the handler

We will use the [zend-expressive CLI tooling](https://docs.zendframework.com/zend-expressive/v3/reference/cli-tooling)
to generate our handler, as well as the related factory and template:

```bash
$ ./vendor/bin/expressive handler:create "App\Login\LoginHandler"
```

By default, if you have a configured template engine, this will do the
following:

- Create the handler for you.
- Create logic in the handler to render a template and return the contents in
  a response.
- Create a factory for the handler.
- Create a template for you in an appropriate directory.

When it does these things, it provides you with the paths to each as well. In
our case, we are using the [PlatesPHP templating
integration](https://docs.zendframework.com/zend-expressive/v3/features/template/plates/),
with a flat application structure, and the following files were either created
or updated:

- `src/App/Login/LoginHandler.php`, which contains the handler class itself.
- `src/App/Login/LoginHandlerFactory.php`, which contains the factory for the handler.
- `config/autoload/zend-expressive-tooling-factories.global.php`, which maps the
  handler to its factory for the DI container.
- `templates/app/login.phtml`, which contains our template.

## Edit the template

We will now edit the template. The main considerations are:

- It needs to have a form that submits back to a URL provided to the template.
- The form needs both a `username` and a `password` field.

Our application is built off the skeleton, and so we are currently using
[Bootstrap](https://getbootstrap.com) for a UI framework. We are also using
PlatesPHP as noted earlier. As such, we will update the template in
`templates/app/login.phtml` to read as follows:

```php
<div class="container">
    <div class="row">
        <div class="col-sm"><form action="<?= $action ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form></div>
    </div>
</div>
```

## Complete the handler

The generated handler will mostly work for our needs. However, we need to modify
it to do the following:

- Grab the session container.
- Attempt to grab a redirect URI from the session.
- If one is not available:
  - Check the `Referer` request header
  - If the header is empty, or points to the login page, use the home page.
  - Store the value in the session.
- Pass the redirect URI to the template engine.

The end result should look like this:

```php
namespace App\Login;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class LoginHandler implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $session = $request->getAttribute('session');
        $redirect = $session->get('authentication:redirect');

        if (! $redirect) {
            $redirect = $request->getHeaderLine('Referer');
            if (in_array($redirect, ['', '/login'], true)) {
                $redirect = '/';
            }
            $session->set('authentication:redirect', $redirect);
        }

        return new HtmlResponse($this->renderer->render(
            'app::login',
            [
                'action' => $redirect,
            ]
        ));
    }
}
```

With these changes in place, our handler is now ready.

## Create the route

From here, we need to create a route for the new handler. We will open up our
`config/routes.php` file, and edit it to add the following within its callback:

```php
$app->get(
    '/login',
    [
        Zend\Expressive\Session\SessionMiddleware::class,
        App\Login\LoginHandler::class,
    ],
    'login'
);
```

Let's unpack the above a bit.

First, we are creating a route that will respond to a `GET` request only.

Second, we tell it to respond to the `/login` path, as we configured in the
previous chapter.

Third, for the middleware, we are providing a _pipeline_. The first middleware
listed is the zend-expressive-session `SessionMiddleware`. This is required so
that we have a session available to which to write the user when authenticated,
but also so we can write and retrieve the referer redirect in our handler! The
second item in the pipeline is the handler we've written above.

With this in place, any routes we write that require authentication will now:

- Redirect to the `/login` page, which will require that:
- A user provides credentials and submits the form to the originally requested
  URL, which will:
- Allow the `PhpSession` adapter to validate the credentials and store the user
  details in the session, and:
- Ultimately give them access (assuming any roles associated with them are
  authorized).

In the next chapter, we will [detail how to require authentication for
individual handlers](requiring-authentication.md).
