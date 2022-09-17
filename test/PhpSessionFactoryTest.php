<?php

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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class PhpSessionFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    private PhpSessionFactory $factory;

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
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(UserRepositoryInterface::class)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([]);

        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container);
    }

    public function testInvokeWithContainerAndConfig(): void
    {
        $this->container
            ->expects(self::atLeastOnce())
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [ResponseFactoryInterface::class, false],
                [UserRepositoryInterface::class, true],
                [UserInterface::class, true],
            ]);

        $this->container
            ->expects(self::atLeastOnce())
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
        self::assertResponseFactoryReturns($this->responsePrototype, $phpSession);
    }

    public static function assertResponseFactoryReturns(ResponseInterface $expected, PhpSession $service): void
    {
        $responseFactory = $service->getResponseFactory();
        Assert::assertSame($expected, $responseFactory->getResponseFromCallable());
    }

    protected function setUp(): void
    {
        $this->container         = $this->createMock(ContainerInterface::class);
        $this->factory           = new PhpSessionFactory();
        $this->userRegister      = $this->createMock(UserRepositoryInterface::class);
        $this->responsePrototype = $this->createMock(ResponseInterface::class);
        $this->responseFactory   = fn() => $this->responsePrototype;
        $this->userFactory       = static fn(string $identity, array $roles = [], array $details = []): UserInterface
             => new DefaultUser($identity, $roles, $details);
    }
}
