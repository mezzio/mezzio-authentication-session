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

- It needs to have a form that submits back to the login page.
- The form needs both a `username` and a `password` field.

Our application is built off the skeleton, and so we are currently using
[Bootstrap](https://getbootstrap.com) for a UI framework. We are also using
PlatesPHP as noted earlier. As such, we will update the template in
`templates/app/login.phtml` to read as follows:

```php
<div class="container">
    <div class="row">
        <div class="col-sm"><form action="<?= $this->url('login') ?>" method="post">
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

Our handler will react to two different HTTP methods. For an initial login
request, the `GET` method will be used, and we will need to display our
template. When the user submits the form, it will be via the `POST` method. In
this situation, if the credentials are invalid, the `PhpSession` adapter will
redirect to the login page again, via a `GET`. However, if it is successful, it
will continue to the handler; at this point, we will want to redirect to the
original page that needed an authenticated user.

The generated handler will mostly work for our needs. However, we need to modify
it to do the following:

- Grab the session container.
- Attempt to grab a redirect URI from the session.
- If one is not available:
  - Check the `Referer` request header
  - If the header is empty, or points to the login page, use the home page.
- If this is a POST request:
  - Remove the redirect URI from the session.
  - Redirect to the redirect URI.
- If this is a GET request:
  - Store the redirect URI in the session.
  - Pass the redirect URI to the template engine.

Since we will be performing a redirect for successful POST requests, we will
need to add a requirement on `Zend\Diactoros\Response\RedirectResponse` in
addition to the logic changes in the handler.

The end result should look like this:

```php
namespace App\Login;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;           // add this line
use Zend\Expressive\Template\TemplateRendererInterface;

class LoginHandler implements RequestHandlerInterface
{
    private const REDIRECT_ATTRIBUTE = 'authentication:redirect';

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
        $redirect = $session->get(self::REDIRECT_ATTRIBUTE);

        if (! $redirect) {
            $redirect = $request->getHeaderLine('Referer');
            if (in_array($redirect, ['', '/login'], true)) {
                $redirect = '/';
            }
        }

        if ('POST' === $request->getMethod()) {
            // Successful login; redirect to original page.
            $session->unset(self::REDIRECT_ATTRIBUTE);
            return new RedirectResponse($redirect);
        }

        // Requesting login
        $session->set(self::REDIRECT_ATTRIBUTE, $redirect);
        return new HtmlResponse($this->renderer->render(
            'app::login',
            []
        ));
    }
}
```

With these changes in place, our handler is now ready.

## Create the route

From here, we need to create two routes for the new handler, one to handle the
incoming `GET` request for displaying the form, and the second to handle `POST`
requests for validating the credentials. Open up your `config/routes.php` file,
and edit it to add the following within its callback:

```php
$app->get(
    '/login',
    [
        Zend\Expressive\Session\SessionMiddleware::class,
        App\Login\LoginHandler::class,
    ],
    'login'
);
$app->post(
    '/login',
    [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Login\LoginHandler::class,
    ]
);
```

Let's unpack the above a bit.

First, we create a route that will respond to a `GET` request only. This
one contains only the session middleware and our handler. We need the session
middleware so that we have the session container available in our handler, for
the purpose of retrieving and setting our redirect URI.

Second, we create a route that handles the `POST` request. This one sandwiches
the `AuthenticationMiddleware` between the session middleware and our login
handler.

Why two separate routes?

When the `AuthenticationMiddleware` handles a request,
it calls on the adapter to authenticate the request. The adapter then returns
either a `UserInterface` instance, or `null`. In the latter case, the middleware
then produces an "unauthorized response", which is in turn generated by the
adapter. The `PhpSession` adapter redirects to the configured login URL at this
point.

As such, if we did a single route that handled both `GET` and `POST` requests,
we would end up in an infinite redirect loop situation the first time we
requested the login page, as we'd never have an authenticated user!

When we do a `POST` request, if authentication fails, it redirects to the login
page. However, if it succeeds, it continues on to the next handler. This is why
the handler checks to see if it was requested via `POST`, and redirects to the
original page when that condition occurs.

In each case, we are specifiying a _pipeline_ for the handler. This approach
allows us to only include the session and/or authentication middleware where its
needed. In the case of the `PhpSession` adapter provided by this package, we
need to _always_ include the `SessionMiddleware` in any pipeline where we are
checking for an authenticated user, as the adapter will query the session for a
user.

With this in place, any routes we write that require authentication will now:

- Redirect to the `/login` page, which will require that:
- A user provides credentials and submits the form back to the `/login` page,
  which will:
- Allow the `PhpSession` adapter to validate the credentials and store the user
  details in the session, and:
- Ultimately give them access (assuming any roles associated with them are
  authorized), and:
- Redirect them back to the originally requested page.

In the next chapter, we will [detail how to require authentication for
individual handlers](requiring-authentication.md).
