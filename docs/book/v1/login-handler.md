# Handling an initial login

When you have configured the adapter, you can drop in the
mezzio-authentication `AuthenticationMiddleware` anywhere you need to
ensure you have an authenticated user. However, how do you handle the initial
authentication?

In the [previous chapter](config.md), we indicated that you need to configure a
path to which to redirect when the adapter does not detect a user. In this
chapter, we'll detail how to create a login handler for processing user
credentials.

Roughly, what we need to do is:

- Create a handler that will both display and handle a login form, redirecting
  to the originally requested location once a successful authentication occurs.

- Create a template with a form for capturing the username and password.

- Create a route to the new handler.

## Create the handler

We will use the [mezzio CLI tooling](https://docs.mezzio.dev/mezzio/v3/reference/cli-tooling)
to generate our handler, as well as the related factory and template:

```bash
$ ./vendor/bin/mezzio handler:create "App\Login\LoginHandler"
```

By default, if you have a configured template engine, this will do the
following:

- Create the handler for you.

- Create logic in the handler to render a template and return the contents in
  a response.

- Create a factory for the handler.

- Create a template for you in an appropriate directory.

When it does these things, it provides you with the paths to each as well. In
our case, we are using the [PlatesPHP templating integration](https://docs.mezzio.dev/mezzio/v3/features/template/plates/),
with a flat application structure, and the following files were either created
or updated:

- `src/App/Login/LoginHandler.php`, which contains the handler class itself.

- `src/App/Login/LoginHandlerFactory.php`, which contains the factory for the handler.

- `config/autoload/mezzio-tooling-factories.global.php`, which maps the
  handler to its factory for the DI container.

- `templates/app/login.phtml`, which contains our template.

Now that we have created the handler, we can edit it to do the work we need.

Our handler will react to two different HTTP methods.

For an initial login request, the `GET` method will be used, and we will need to
display our template. When we do, we will also memoize the originally requested
URI (using the `Referer` request header).

When the user submits the form, it will be via the `POST` method. When this
happens, we will need to validate the submitted credentials; we will do this
using the `PhpSession` adapter from this package. If login is successful, we
will redirect to the originally requested URI, using the value we previously
stored in our session. If login fails, we will display our template, adding an
error message indicating the credentials were invalid.

The generated handler will already compose the `TemplateRendererInterface`, and
render a template. We will need to add a constructor dependency on the
`PhpSession` adapter, and store that value in a property. Additionally, since we
will be performing a redirect for successful POST requests, we will need to add
a requirement on `Laminas\Diactoros\Response\RedirectResponse` in addition to the
logic changes in the handler.

The end result should look like this:

```php
namespace App\Login;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;           // add this line
use Mezzio\Authentication\Session\PhpSession;  // add this line
use Mezzio\Session\SessionInterface;           // add this line
use Mezzio\Authentication\UserInterface;       // add this line
use Mezzio\Template\TemplateRendererInterface;


class LoginHandler implements RequestHandlerInterface
{
    private const REDIRECT_ATTRIBUTE = 'authentication:redirect';

    /** @var PhpSession */
    private $adapter;

    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer, PhpSession $adapter)
    {
        $this->renderer = $renderer;
        $this->adapter = $adapter;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $session  = $request->getAttribute('session');
        $redirect = $this->getRedirect($request, $session);

        // Handle submitted credentials
        if ('POST' === $request->getMethod()) {
            return $this->handleLoginAttempt($request, $session, $redirect);
        }

        // Display initial login form
        $session->set(self::REDIRECT_ATTRIBUTE, $redirect);
        return new HtmlResponse($this->renderer->render(
            'app::login',
            []
        ));
    }

    private function getRedirect(
        ServerRequestInterface $request,
        SessionInterface $session
    ) : string {
        $redirect = $session->get(self::REDIRECT_ATTRIBUTE);

        if (! $redirect) {
            $redirect = $request->getHeaderLine('Referer');
            if (in_array($redirect, ['', '/login'], true)) {
                $redirect = '/';
            }
        }

        return $redirect;
    }

    private function handleLoginAttempt(
        ServerRequestInterface $request,
        SessionInterface $session,
        string $redirect
    ) : ResponseInterface {
        // User session takes precedence over user/pass POST in
        // the auth adapter so we remove the session prior
        // to auth attempt
        $session->unset(UserInterface::class);

        // Login was successful
        if ($this->adapter->authenticate($request)) {
            $session->unset(self::REDIRECT_ATTRIBUTE);
            return new RedirectResponse($redirect);
        }

        // Login failed
        return new HtmlResponse($this->renderer->render(
            'app::login',
            ['error' => 'Invalid credentials; please try again']
        ));
    }
}
```

With these changes in place, our handler is now ready. However, we need to
update our factory, as we've added a new dependency!

To do this, run the following from the command line, in the project root
directory:

```bash
$ rm src/App/Login/LoginHandlerFactory.php
$ ./vendor/bin/mezzio factory:create "App\Login\LoginHandler"
```

This will regenerate the factory for you.

## Edit the template

We will now edit the template. The main considerations are:

- It needs to have a form that submits back to the login page.

- The form needs both a `username` and a `password` field.

- We need to display an error message if one was provided.

Our application is built off the skeleton, and so we are currently using
[Bootstrap](https://getbootstrap.com) for a UI framework. We are also using
PlatesPHP as noted earlier. As such, we will update the template in
`templates/app/login.phtml` to read as follows:

```php
<div class="container">
    <div class="row">
        <div class="col-sm"><form action="<?= $this->url('login') ?>" method="post">
            <?php if (isset($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $this->escapeHtml($error) ?>
            </div>
            <?php endif ?>

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

> ### Template location and structure
>
> Keep in mind the following when reading the above sample:
>
> - If you are using the modular structure, the template may be in a different
>   location. Use the output from the `mezzio handler:create` command to
>   determine the exact location.
>
> - If you are using a different template engine, the syntax of the template
>   may vary.
>
> - The HTML may need to vary based on your own site's UI framework and CSS.

## Create the route

Now that we have the handler and template created, we need to create a route for
the new handler that handles two HTTP methods: `GET` for displaying the initial
form, and `POST` for validating submitted credentials. Open up your
`config/routes.php` file, and edit it to add the following within its callback:

```php
$app->route(
    '/login',
    [
        Mezzio\Session\SessionMiddleware::class,
        App\Login\LoginHandler::class,
    ],
    ['GET', 'POST'],
    'login'
);
```

> ### Understanding the routing
>
> You may not be familiar with the `route()` method, or middleware pipelines. If
> the above doesn't make sense, keep reading for an explanation.
>
> First, we are using the `route()` method, as we want to create a _single_ route
> to respond to _multiple_ HTTP methods. This method has a required third argument,
> which is an array of HTTP methods; we specify `GET` and `POST` in this array.
>
> Second, we are indicating that we want the route to respond to the exact path
> `/login`; we provide this via the initial method argument.
>
> Third, we are providing a name for this route via the optional fourth argument;
> this is what allows us to call `$this->url('login')` in our template in order to
> generate the URL to the login page.
>
> Finally, for the middleware argument, we are providing a [pipeline](https://docs.mezzio.dev/mezzio/v1/getting-started/features/#pipelines),
> by providing an _array_ of middleware to execute. The first item in the pipeline
> is the `SessionMiddleware` from mezzio-session; this is required to
> ensure we have a session container injected into the request. The second item is
> our login handler itself, which will then do the actual work of creating a
> response.

With this route in place, any routes we write that require authentication will
now:

- Redirect to the `/login` page, which will require that:

- A user provides credentials and submits the form back to the `/login` page,
  which will:

- Process the credentials via the `PhpSession` adapter, which will store
  identified user details in the session, and:

- Ultimately give them access (assuming any roles associated with them are
  authorized), and:

- Redirect them back to the originally requested page.

In the next chapter, we will [detail how to require authentication for
individual handlers](requiring-authentication.md).
