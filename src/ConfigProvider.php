<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Authentication\Session;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'authentication' => $this->getAuthenticationConfig(),
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getAuthenticationConfig() : array
    {
        return [
            'username' => '', // provide a custom field name for the username
            'password' => '', // provide a custom field name for the password
            'redirect' => '', // URI to which to redirect if no valid credentials present
        ];
    }

    public function getDependencies() : array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Authentication\Session\PhpSession::class => PhpSession::class,
            ],
            'factories' => [
                PhpSession::class => PhpSessionFactory::class,
            ],
        ];
    }
}
