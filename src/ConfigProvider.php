<?php

declare(strict_types=1);

namespace Mezzio\Authentication\Session;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'authentication' => $this->getAuthenticationConfig(),
            'dependencies'   => $this->getDependencies(),
        ];
    }

    public function getAuthenticationConfig(): array
    {
        return [
            'username' => null,
            'password' => null,
        ];
    }

    public function getDependencies(): array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases'   => [
                'Zend\Expressive\Authentication\Session\PhpSession' => PhpSession::class,
            ],
            'factories' => [
                PhpSession::class => PhpSessionFactory::class,
            ],
        ];
    }
}
