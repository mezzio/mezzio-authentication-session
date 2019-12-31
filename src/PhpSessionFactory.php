<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Authentication\Session;

use Mezzio\Authentication\Exception;
use Mezzio\Authentication\ResponsePrototypeTrait;
use Mezzio\Authentication\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

class PhpSessionFactory
{
    use ResponsePrototypeTrait;

    public function __invoke(ContainerInterface $container): PhpSession
    {
        $userRegister = $container->has(UserRepositoryInterface::class)
            ? $container->get(UserRepositoryInterface::class)
            : ($container->has(\Zend\Expressive\Authentication\UserRepositoryInterface::class)
                ? $container->get(\Zend\Expressive\Authentication\UserRepositoryInterface::class)
                : null);

        if (null === $userRegister) {
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

        return new PhpSession(
            $userRegister,
            $config,
            $this->getResponsePrototype($container)
        );
    }
}
