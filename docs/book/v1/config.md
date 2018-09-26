# Configuration

You will need to provide configuration for the adapter to work correctly:

- You will need to alias the zend-expressive-authentication
  `AuthenticationInterface` to the package's `PhpSession` implementation.

- You will need to ensure a zend-expressive-authentication
  `UserRepositoryInterface` implementation is available and configured.

- You will need to provide a factory capable of generating a `UserInterface`
  instance, if you do not want to use the default provided by
  zend-expressive-authentication.

- You will need to provide a URL or path to which the authentication middleware
  will **redirect** if no user is discovered in the session.

## Example

Below is an example demonstrating authentication configuration you might provide
when using zend-expressive-authentication-session. In particular:

- It aliases the `PdoDatabase` user repository implementation from
  zend-expressive-authentication as the `UserRepositoryInterface` service.

- It maps the `PhpSession` adapter from this package as the
  `AuthenticationInterface` implementation.

- It **does not** configure a `Zend\Expressive\Authentication\UserInterface`
  service, opting to use the default provided by zend-expressive-authentication.

- It configures the path `/login` as the redirect URL to which unauthenticated
  users will be redirected.


```php
<?php
// in a config/autoload/*.global.php file:

declare(strict_types=1);

use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Authentication\UserRepositoryInterface;
use Zend\Expressive\Authentication\UserRepository\PdoDatabase;

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

Once you have configured the adapter, you will also need to write a handler that
will handle login attempts; [see the next section for details on how to
accomplish that](login-handler.md).
