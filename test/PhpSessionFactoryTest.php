<?php

/**
 * @see       https://github.com/mezzio/mezzio-authentication-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authentication-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authentication-session/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Authentication\Session;

use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\Session\PhpSessionFactory;
use Mezzio\Authentication\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class PhpSessionFactoryTest extends TestCase
{
    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new PhpSessionFactory();
        $this->userRegister = $this->prophesize(UserRepositoryInterface::class);
        $this->responsePrototype = $this->prophesize(ResponseInterface::class);
    }

    /**
     * @expectedException Mezzio\Authentication\Exception\InvalidConfigException
     */
    public function testInvokeWithEmptyContainer()
    {
        $phpSession = ($this->factory)($this->container->reveal());
    }

    /**
     * @expectedException Mezzio\Authentication\Exception\InvalidConfigException
     */
    public function testInvokeWithContainerEmptyConfig()
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
            ->willReturn($this->responsePrototype->reveal());
        $this->container
            ->get('config')
            ->willReturn([]);

        $phpSession = ($this->factory)($this->container->reveal());
    }

    public function testInvokeWithContainerAndConfig()
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
            ->willReturn($this->responsePrototype->reveal());
        $this->container
            ->get('config')
            ->willReturn([
                'authentication' => ['redirect' => '/login'],
            ]);

        $phpSession = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(PhpSession::class, $phpSession);
    }
}
