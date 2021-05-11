<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Authentication\Session;

use Mezzio\Authentication\DefaultUser;
use Mezzio\Authentication\Exception\InvalidConfigException;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\Session\PhpSessionFactory;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;

class PhpSessionFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var PhpSessionFactory */
    private $factory;

    /** @var UserRepositoryInterface|ObjectProphecy */
    private $userRegister;

    /** @var ResponseInterface|ObjectProphecy */
    private $responsePrototype;

    /** @var callable */
    private $responseFactory;

    /** @var callable */
    private $userFactory;

    protected function setUp(): void
    {
        $this->container         = $this->prophesize(ContainerInterface::class);
        $this->factory           = new PhpSessionFactory();
        $this->userRegister      = $this->prophesize(UserRepositoryInterface::class);
        $this->responsePrototype = $this->prophesize(ResponseInterface::class);
        $this->responseFactory   = function () {
            return $this->responsePrototype->reveal();
        };
        $this->userFactory       = function (string $identity, array $roles = [], array $details = []): UserInterface {
            return new DefaultUser($identity, $roles, $details);
        };
    }

    public function testInvokeWithEmptyContainer(): void
    {
        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testInvokeWithContainerEmptyConfig(): void
    {
        $this->container
            ->has(UserRepositoryInterface::class)
            ->willReturn(true);
        $this->container
            ->get(UserRepositoryInterface::class)
            ->willReturn($this->userRegister->reveal());
        $this->container
            ->has(ResponseInterface::class)
            ->willReturn(true);
        $this->container
            ->get(ResponseInterface::class)
            ->willReturn($this->responseFactory);
        $this->container
            ->has(UserInterface::class)
            ->willReturn(true);
        $this->container
            ->get(UserInterface::class)
            ->willReturn($this->userFactory);
        $this->container
            ->get('config')
            ->willReturn([]);

        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testInvokeWithContainerAndConfig(): void
    {
        $this->container
            ->has(UserRepositoryInterface::class)
            ->willReturn(true);
        $this->container
            ->get(UserRepositoryInterface::class)
            ->willReturn($this->userRegister->reveal());
        $this->container
            ->has(ResponseInterface::class)
            ->willReturn(true);
        $this->container
            ->get(ResponseInterface::class)
            ->willReturn($this->responseFactory);
        $this->container
            ->has(UserInterface::class)
            ->willReturn(true);
        $this->container
            ->get(UserInterface::class)
            ->willReturn($this->userFactory);
        $this->container
            ->get('config')
            ->willReturn([
                'authentication' => ['redirect' => '/login'],
            ]);

        $phpSession = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(PhpSession::class, $phpSession);
        self::assertResponseFactoryReturns($this->responsePrototype->reveal(), $phpSession);
    }

    public static function assertResponseFactoryReturns(ResponseInterface $expected, PhpSession $service): void
    {
        $r = new ReflectionProperty($service, 'responseFactory');
        $r->setAccessible(true);
        $responseFactory = $r->getValue($service);
        Assert::assertSame($expected, $responseFactory());
    }
}
