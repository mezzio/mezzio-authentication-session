# Configuration

You will need to provide configuration for the adapter to work correctly:

- You will need to alias the mezzio-authentication
  `AuthenticationInterface` to the package's `PhpSession` implementation.

- You will need to ensure a mezzio-authentication
  `UserRepositoryInterface` implementation is available and configured.

- You will need to provide a factory capable of generating a `UserInterface`
  instance, if you do not want to use the default provided by
  mezzio-authentication.

- You will need to provide a URL or path to which the authentication middleware
  will **redirect** if no user is discovered in the session.

## Example

Below is an example demonstrating authentication configuration you might provide
when using mezzio-authentication-session. In particular:

- It aliases the `PdoDatabase` user repository implementation from
  mezzio-authentication as the `UserRepositoryInterface` service.

- It aliases the `PhpSession` adapter from this package to the
  `AuthenticationInterface` service.

- It **does not** configure a `Mezzio\Authentication\UserInterface`
  service, opting to use the default provided by mezzio-authentication.

- It configures the path `/login` as the URL to which unauthenticated users will
  be redirected.


```php
// in a config/autoload/*.global.php file:

declare(strict_types=1);

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Authentication\UserRepository\PdoDatabase;

return [
    'dependencies' => [
        'aliases' => [
            AuthenticationInterface::class => PhpSession::class,
            UserRepositoryInterface::class => PdoDatabase::class,
        ],
    ],

    'authentication' => [
        'redirect' => '/login',
    ],
];
```

## Handling the login

Once you have configured the adapter, [you will also need to write a handler that
will handle login attempts](login-handler.md).
