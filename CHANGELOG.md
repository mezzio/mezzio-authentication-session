# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.4.0 - 2018-03-15

### Added

- Adds support for mezzio-authentication 0.4.0 and up.

### Changed

- [zendframework/zend-expressive-authentication-session#10](https://github.com/zendframework/zend-expressive-authentication-session/pull/10)
  changes the constructor of the `Mezzio\Authentication\Session\PhpSession`
  class to accept a callable `$responseFactory` instead of a
  `Psr\Http\Message\ResponseInterface` response prototype. The
  `$responseFactory` should produce a `ResponseInterface` implementation when
  invoked.

- [zendframework/zend-expressive-authentication-session#10](https://github.com/zendframework/zend-expressive-authentication-session/pull/10)
  updates the `PhpSessionFactory` to no longer use
  `Mezzio\Authentication\ResponsePrototypeTrait`, and instead always
  depend on the `Psr\Http\Message\ResponseInterface` service to correctly return
  a PHP callable capable of producing a `ResponseInterface` instance.

### Deprecated

- Nothing.

### Removed

- Removes support for mezzio-authentication versions prior to 0.4.0.

### Fixed

- Nothing.

## 0.3.1 - 2018-02-26

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-authentication-session#7](https://github.com/zendframework/zend-expressive-authentication-session/pull/7)
  fixes an issue with the default username and password values defined in the
  `ConfigProvider`. Previously, these were issued as empty strings; however,
  they needed to be `null` values to ensure lookups did not provide a false
  positive.

## 0.3.0 - 2018-01-25

### Added

- [zendframework/zend-expressive-authentication-session#6](https://github.com/zendframework/zend-expressive-authentication-session/pull/6)
  adds support for mezzio-authentication-session 0.3.0.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-authentication-session#6](https://github.com/zendframework/zend-expressive-authentication-session/pull/6)
  drops support for mezzio-authentication-session versions less than 0.3.0.

### Fixed

- Nothing.

## 0.2.2 - 2018-01-08

### Added

- [zendframework/zend-expressive-authentication-session#5](https://github.com/zendframework/zend-expressive-authentication-session/pull/5)
  adds support for the 1.0.0-dev branch of mezzio-session.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.1 - 2017-12-13

### Added

- [zendframework/zend-expressive-authentication-session#2](https://github.com/zendframework/zend-expressive-authentication-session/pull/2)
  adds support for the 1.0.0-dev branch of mezzio-authentication.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.0 - 2017-11-28

### Added

- Adds support for mezzio-authentication 0.2.0.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Adds support for mezzio-authentication 0.1.0.

### Fixed

- Nothing.

## 0.1.1 - 2017-11-14

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-authentication-session#1](https://github.com/zendframework/zend-expressive-authentication-session/pull/1)
  fixes how the `PhpSession` adapter both stores user details in the session,
  and retrieves them. Since mezzio-session does not allow object
  serialization, the class now stores the username and role in the session, and
  then populates an anonymous class implementing
  `Mezzio\Authentication\UserInterface` with the values on subsequent
  requests.

## 0.1.0 - 2017-11-09

Initial release.

### Added

- Everything.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
