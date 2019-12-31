# mezzio-authentication-session

[![Build Status](https://travis-ci.org/mezzio/mezzio-authentication-session.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-authentication-session)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-authentication-session/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-authentication-session?branch=master)

This library provides a [mezzio-authentication](https://github.com/mezzio/mezzio-authentication/)
adapter that handles form-based username/password authentication credentials
where the user details are subsequently stored in a session.

## Installation

Run the following to install this library:

```bash
$ composer require mezzio/mezzio-authentication-session
```

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
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserRepositoryInterface;

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

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://docs.mezzio.dev/mezzio-authentication-session/).
