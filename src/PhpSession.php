<?php

declare(strict_types=1);

namespace Mezzio\Authentication\Session;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\Response\CallableResponseFactoryDecorator;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

use function is_array;
use function is_callable;
use function iterator_to_array;
use function strtoupper;

class PhpSession implements AuthenticationInterface
{
    /** @var UserRepositoryInterface */
    private $repository;

    /** @var array */
    private $config;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var callable */
    private $userFactory;

    /**
     * @param (callable():ResponseInterface)|ResponseFactoryInter $responseFactory
     */
    public function __construct(
        UserRepositoryInterface $repository,
        array $config,
        $responseFactory,
        callable $userFactory
    ) {
        $this->repository = $repository;
        $this->config     = $config;

        if (is_callable($responseFactory)) {
            // Ensures type safety of the composed factory
            $responseFactory = new CallableResponseFactoryDecorator(
                static function () use ($responseFactory): ResponseInterface {
                    return $responseFactory();
                }
            );
        }

        $this->responseFactory = $responseFactory;

        // Ensures type safety of the composed factory
        $this->userFactory = static function (
            string $identity,
            array $roles = [],
            array $details = []
        ) use ($userFactory): UserInterface {
            return $userFactory($identity, $roles, $details);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(ServerRequestInterface $request): ?UserInterface
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

        $params   = $request->getParsedBody();
        $username = $this->config['username'] ?? 'username';
        $password = $this->config['password'] ?? 'password';
        if (! isset($params[$username]) || ! isset($params[$password])) {
            return null;
        }

        $user = $this->repository->authenticate(
            $params[$username],
            $params[$password]
        );

        if (null !== $user) {
            $session->set(UserInterface::class, [
                'username' => $user->getIdentity(),
                'roles'    => iterator_to_array($this->getUserRoles($user)),
                'details'  => $user->getDetails(),
            ]);
            $session->regenerate();
        }

        return $user;
    }

    public function unauthorizedResponse(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader(
                'Location',
                $this->config['redirect']
            );
    }

    /**
     * Create a UserInterface instance from the session data.
     *
     * mezzio-session does not serialize PHP objects directly. As such,
     * we need to create a UserInterface instance based on the data stored in
     * the session instead.
     */
    private function createUserFromSession(SessionInterface $session): ?UserInterface
    {
        $userInfo = $session->get(UserInterface::class);
        if (! is_array($userInfo) || ! isset($userInfo['username'])) {
            return null;
        }
        $roles   = $userInfo['roles'] ?? [];
        $details = $userInfo['details'] ?? [];

        return ($this->userFactory)($userInfo['username'], (array) $roles, (array) $details);
    }

    /**
     * Convert the iterable user roles to a Traversable.
     */
    private function getUserRoles(UserInterface $user): Traversable
    {
        return yield from $user->getRoles();
    }

    /**
     * @internal This should only be used in unit tests.
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }
}
