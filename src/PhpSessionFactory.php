<?php

declare(strict_types=1);

namespace Mezzio\Authentication\Session;

use Mezzio\Authentication\Exception;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

class PhpSessionFactory
{
    use Psr17ResponseFactoryTrait;

    public function __invoke(ContainerInterface $container): PhpSession
    {
        $hasUserRepository           = $container->has(UserRepositoryInterface::class);
        $hasDeprecatedUserRepository = false;
        if (! $hasUserRepository) {
            $hasDeprecatedUserRepository = $container->has(
                'Zend\Expressive\Authentication\UserRepositoryInterface'
            );
        }
        if (
            ! $hasUserRepository
            && ! $hasDeprecatedUserRepository
        ) {
            throw new Exception\InvalidConfigException(
                'UserRepositoryInterface service is missing for authentication'
            );
        }

        $config = $container->get('config')['authentication'] ?? [];

        if (! isset($config['redirect'])) {
            throw new Exception\InvalidConfigException(
                'The redirect configuration is missing for authentication'
            );
        }

        $hasUser           = $container->has(UserInterface::class);
        $hasDeprecatedUser = false;
        if (! $hasUser) {
            $hasDeprecatedUser = $container->has('Zend\Expressive\Authentication\UserInterface');
        }

        if (
            ! $hasUser
            && ! $hasDeprecatedUser
        ) {
            throw new Exception\InvalidConfigException(
                'UserInterface factory service is missing for authentication'
            );
        }

        return new PhpSession(
            $hasUserRepository
                ? $container->get(UserRepositoryInterface::class)
                : $container->get('Zend\Expressive\Authentication\UserRepositoryInterface'),
            $config,
            $this->detectResponseFactory($container),
            $hasUser
                ? $container->get(UserInterface::class)
                : $container->get('Zend\Expressive\Authentication\UserInterface')
        );
    }
}
