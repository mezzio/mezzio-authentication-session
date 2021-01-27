<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Authentication\Session;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

use function array_key_exists;
use function is_array;
use function sprintf;
use function strtoupper;
use function trigger_error;

class PhpSession implements AuthenticationInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * @var array
     */
    private $config;

    /**
     * @var callable
     */
    private $responseFactory;

    /**
     * @var callable
     */
    private $userFactory;

    public function __construct(
        UserRepositoryInterface $repository,
        array $config,
        callable $responseFactory,
        callable $userFactory
    ) {
        $this->repository = $repository;
        $this->config     = $config;

        // Ensures type safety of the composed factory
        $this->responseFactory = function () use ($responseFactory) : ResponseInterface {
            return $responseFactory();
        };

        // Ensures type safety of the composed factory
        $this->userFactory = function (
            string $identity,
            array $roles = [],
            array $details = []
        ) use ($userFactory) : UserInterface {
            return $userFactory($identity, $roles, $details);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(ServerRequestInterface $request) : ?UserInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (! $session) {
            throw Exception\MissingSessionContainerException::create();
        }

        if ($session->has(UserInterface::class)) {
            return $this->createUserFromSession($session);
        }

        if ('POST' !== strtoupper($request->getMethod())) {
            return null;
        }

        $this->triggerDeprecatedUsername($this->config);

        $params   = $request->getParsedBody();
        $identity = $this->config['identity'] ?? 'username';
        $password = $this->config['password'] ?? 'password';

        if (! isset($params[$identity]) || ! isset($params[$password])) {
            return null;
        }

        $user = $this->repository->authenticate(
            $params[$identity],
            $params[$password]
        );

        if (null !== $user) {
            $session->set(UserInterface::class, [
                'identity' => $user->getIdentity(),
                'roles'    => iterator_to_array($this->getUserRoles($user)),
                'details'  => $user->getDetails(),
            ]);

            $session->regenerate();
        }

        return $user;
    }

    public function unauthorizedResponse(ServerRequestInterface $request) : ResponseInterface
    {
        return ($this->responseFactory)()
            ->withHeader(
                'Location',
                $this->config['redirect']
            )
            ->withStatus(302);
    }

    /**
     * Create a UserInterface instance from the session data.
     *
     * mezzio-session does not serialize PHP objects directly. As such,
     * we need to create a UserInterface instance based on the data stored in
     * the session instead.
     */
    private function createUserFromSession(SessionInterface $session) : ?UserInterface
    {
        $userInfo = $session->get(UserInterface::class);

        if (is_array($userInfo)) {
            $this->triggerDeprecatedUsername($userInfo);
        }

        if (! is_array($userInfo) || ! isset($userInfo['identity'])) {
            return null;
        }

        $roles   = $userInfo['roles'] ?? [];
        $details = $userInfo['details'] ?? [];

        return ($this->userFactory)($userInfo['identity'], (array) $roles, (array) $details);
    }

    /**
     * Convert the iterable user roles to a Traversable.
     */
    private function getUserRoles(UserInterface $user) : Traversable
    {
        return yield from $user->getRoles();
    }

    private function triggerDeprecatedUsername(array $config)
    {
        if (array_key_exists('username', $config) && ! array_key_exists('identity', $config)) {
            trigger_error(sprintf(
                '%s is currently using an old configuration. The username is deprecated and has an identity instead; '
                . 'please update your authentication configuration.',
                __CLASS__,
            ), E_USER_DEPRECATED);
        }
    }
}
