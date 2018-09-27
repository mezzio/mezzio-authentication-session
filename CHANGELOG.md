# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.0.0 - 2018-09-27

### Added

- [#19](https://github.com/zendframework/zend-expressive-authentication-session/pull/19) adds comprehensive documentation detailing usage and configuration
  of the package.

### Changed

- [#19](https://github.com/zendframework/zend-expressive-authentication-session/pull/19) updates the package to depend on zend-expressive-authentication
  1.0.0 instead of a pre-release version.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.0 - 2018-06-18

### Added

- Nothing.

### Changed

- [#16](https://github.com/zendframework/zend-expressive-authentication-session/pull/16) updates the package to depend on the 0.5 series of zend-expressive-authentication.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#14](https://github.com/zendframework/zend-expressive-authentication-session/pull/14) removes the default "redirect" configuration entry, as providing
  it by default can lead to difficult-to-debug errors. Users should provide this
  configuration, with a path to the login URI specific to their application.

## 0.4.1 - 2018-03-20

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#12](https://github.com/zendframework/zend-expressive-authentication-session/pull/12)
  fixes the status code issued by the `PhpSession::unauthorizedResponse()`
  method to use 302 (temporary) vs 301 (permanent) redirects.

## 0.4.0 - 2018-03-15

### Added

- Adds support for zend-expressive-authentication 0.4.0 and up.

### Changed

- [#10](https://github.com/zendframework/zend-expressive-authentication-session/pull/10)
  changes the constructor of the `Zend\Expressive\Authentication\Session\PhpSession`
  class to accept a callable `$responseFactory` instead of a
  `Psr\Http\Message\ResponseInterface` response prototype. The
  `$responseFactory` should produce a `ResponseInterface` implementation when
  invoked.

- [#10](https://github.com/zendframework/zend-expressive-authentication-session/pull/10)
  updates the `PhpSessionFactory` to no longer use
  `Zend\Expressive\Authentication\ResponsePrototypeTrait`, and instead always
  depend on the `Psr\Http\Message\ResponseInterface` service to correctly return
  a PHP callable capable of producing a `ResponseInterface` instance.

### Deprecated

- Nothing.

### Removed

- Removes support for zend-expressive-authentication versions prior to 0.4.0.

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

- [#7](https://github.com/zendframework/zend-expressive-authentication-session/pull/7)
  fixes an issue with the default username and password values defined in the
  `ConfigProvider`. Previously, these were issued as empty strings; however,
  they needed to be `null` values to ensure lookups did not provide a false
  positive.

## 0.3.0 - 2018-01-25

### Added

- [#6](https://github.com/zendframework/zend-expressive-authentication-session/pull/6)
  adds support for zend-expressive-authentication-session 0.3.0.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#6](https://github.com/zendframework/zend-expressive-authentication-session/pull/6)
  drops support for zend-expressive-authentication-session versions less than 0.3.0.

### Fixed

- Nothing.

## 0.2.2 - 2018-01-08

### Added

- [#5](https://github.com/zendframework/zend-expressive-authentication-session/pull/5)
  adds support for the 1.0.0-dev branch of zend-expressive-session.

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

- [#2](https://github.com/zendframework/zend-expressive-authentication-session/pull/2)
  adds support for the 1.0.0-dev branch of zend-expressive-authentication.

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

- Adds support for zend-expressive-authentication 0.2.0.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Adds support for zend-expressive-authentication 0.1.0.

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

- [#1](https://github.com/zendframework/zend-expressive-authentication-session/pull/1)
  fixes how the `PhpSession` adapter both stores user details in the session,
  and retrieves them. Since zend-expressive-session does not allow object
  serialization, the class now stores the username and role in the session, and
  then populates an anonymous class implementing
  `Zend\Expressive\Authentication\UserInterface` with the values on subsequent
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
