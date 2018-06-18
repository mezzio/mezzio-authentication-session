# zend-expressive-authentication-session

This library provides a [zend-expressive-authentication](https://github.com/zendframework/zend-expressive-authentication/)
adapter that handles form-based username/password authentication credentials
where the user details are subsequently stored in a session.

Documentation forthcoming...

## Configuration

You will need to provide configuration for this module to work correctly. The
following demonstrates:

- Mapping a custom `UserRepositoryInterface` implementation for use as a backend
  to the functionality this authentication adapter provides.
- Mapping the `PhpSession` adapter as the `AuthenticationInterface`
  implementation to use in your application.
- Providing configuration for this adapter, including custom field names for the
  username and password, as well as a path in the application to which to redirect
  when no valid credentials are present.

```php
<?php
// in a config/autoload/*.global.php file:

declare(strict_types=1);

//use App\Infrastructure\Repository\UserRepository;
//use App\Infrastructure\Repository\UserRepositoryFactory;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Authentication\UserRepositoryInterface;

return [
    'dependencies' => [
        'aliases' => [
            AuthenticationInterface::class => PhpSession::class,
            UserRepositoryInterface::class => UserRepository::class,
        ],

        'factories' => [
            UserRepository::class => UserRepositoryFactory::class,
        ],
    ],

    'authentication' => [
        'username' => null, // provide a custom field name for the username
        'password' => null, // provide a custom field name for the password
        'redirect' => '/login', // URI to which to redirect if no valid credentials present
    ],
];
```
