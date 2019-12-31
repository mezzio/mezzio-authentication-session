<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Authentication\Session;

use Mezzio\Authentication\Exception;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class PhpSessionFactory
{
    public function __invoke(ContainerInterface $container) : PhpSession
    {
        if (! $container->has(UserRepositoryInterface::class)
            && ! $container->has(\Zend\Expressive\Authentication\UserRepositoryInterface::class)
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

        if (! $container->has(UserInterface::class)
            && ! $container->has(\Zend\Expressive\Authentication\UserInterface::class)
        ) {
            throw new Exception\InvalidConfigException(
                'UserInterface factory service is missing for authentication'
            );
        }

        return new PhpSession(
            $container->has(UserRepositoryInterface::class) ? $container->get(UserRepositoryInterface::class) : $container->get(\Zend\Expressive\Authentication\UserRepositoryInterface::class),
            $config,
            $container->get(ResponseInterface::class),
            $container->has(UserInterface::class) ? $container->get(UserInterface::class) : $container->get(\Zend\Expressive\Authentication\UserInterface::class)
        );
    }
}
