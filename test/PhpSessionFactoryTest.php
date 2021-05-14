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

    public function testInvokeWithEmptyContainer(): void
    {
        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container);
    }

    public function testInvokeWithContainerEmptyConfig(): void
    {
        $this->container->expects(self::atLeastOnce())
                        ->method('has')
                        ->willReturn(true);
        $this->container->expects(self::atLeastOnce())
                        ->method('get')
                        ->willReturnMap(
                            [
                                ['config', []],
                                [UserRepositoryInterface::class, $this->userRegister],
                                [ResponseInterface::class, $this->responseFactory],
                                [UserInterface::class, $this->userFactory],
                            ]
                        );

        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container);
    }

    public function testInvokeWithContainerAndConfig(): void
    {
        $this->container->expects(self::atLeastOnce())
                        ->method('has')
                        ->willReturn(true);
        $this->container->expects(self::atLeastOnce())
                        ->method('get')
                        ->willReturnMap(
                            [
                                ['config', ['authentication' => ['redirect' => '/login']]],
                                [UserRepositoryInterface::class, $this->userRegister],
                                [ResponseInterface::class, $this->responseFactory],
                                [UserInterface::class, $this->userFactory],
                            ]
                        );

        $phpSession = ($this->factory)($this->container);
        $this->assertInstanceOf(PhpSession::class, $phpSession);
        self::assertResponseFactoryReturns($this->responsePrototype, $phpSession);
    }

    public static function assertResponseFactoryReturns(ResponseInterface $expected, PhpSession $service): void
    {
        $r = new ReflectionProperty($service, 'responseFactory');
        $r->setAccessible(true);
        $responseFactory = $r->getValue($service);
        Assert::assertSame($expected, $responseFactory());
    }

    protected function setUp(): void
    {
        $this->container         = $this->createMock(ContainerInterface::class);
        $this->factory           = new PhpSessionFactory();
        $this->userRegister      = $this->createMock(UserRepositoryInterface::class);
        $this->responsePrototype = $this->createMock(ResponseInterface::class);
        $this->responseFactory   = function () {
            return $this->responsePrototype;
        };
        $this->userFactory       = function (string $identity, array $roles = [], array $details = []): UserInterface {
            return new DefaultUser($identity, $roles, $details);
        };
    }
}
