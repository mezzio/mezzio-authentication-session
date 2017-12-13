# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
