<?php
/**
 * @see https://github.com/zendframework/zend-expressive-authentication-session
 *     for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-expressive-authentication-session/blob/master/LICENSE.md
 *     New BSD License
 */

namespace ZendTest\Expressive\Authentication\Session;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\Session\Exception;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Authentication\UserRepositoryInterface;
use Zend\Expressive\Session\SessionInterface;

class PhpSessionTest extends TestCase
{
    protected function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->userRegister = $this->prophesize(UserRepositoryInterface::class);
        $this->authenticatedUser = $this->prophesize(UserInterface::class);
        $this->responsePrototype = $this->prophesize(ResponseInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
    }

    public function testConstructor()
    {
        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );
        $this->assertInstanceOf(AuthenticationInterface::class, $phpSession);
    }

    public function testAuthenticationWithMissingSessionAttributeRaisesException()
    {
        $this->request->getAttribute('session')->willReturn(null);

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $this->expectException(Exception\MissingSessionContainerException::class);
        $phpSession->authenticate($this->request->reveal());
    }

    public function testAuthenticationWhenSessionDoesNotContainUserAndRequestIsGetReturnsNull()
    {
        $this->session->has(UserInterface::class)->willReturn(false);

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);
        $this->request->getMethod()->willReturn('GET');

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $this->assertNull($phpSession->authenticate($this->request->reveal()));
    }

    public function testAuthenticationWithNoSessionUserViaPostWithNoDataReturnsNull()
    {
        $this->session->has(UserInterface::class)->willReturn(false);

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);
        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([]);

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $this->assertNull($phpSession->authenticate($this->request->reveal()));
    }

    public function testAuthenticationWithNoSessionUserViaPostWithDefaultFieldsCanHaveSuccessfulResult()
    {
        $this->session
            ->has(UserInterface::class)
            ->willReturn(false);
        $this->session
            ->set(UserInterface::class, [
                'username' => 'vimes',
                'role' => 'captain',
            ])
            ->shouldBeCalled();
        $this->session
            ->regenerate()
            ->shouldBeCalled();

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);
        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([
            'username' => 'foo',
            'password' => 'bar'
        ]);

        $this->authenticatedUser->getUsername()->willReturn('vimes');
        $this->authenticatedUser->getUserRole()->willReturn('captain');

        $this->userRegister
            ->authenticate('foo', 'bar')
            ->willReturn($this->authenticatedUser->reveal());

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $result = $phpSession->authenticate($this->request->reveal());

        $this->assertSame($this->authenticatedUser->reveal(), $result);
    }

    public function testAuthenticationWithNoSessionUserViaPostWithCustomFieldsCanHaveSuccessfulResult()
    {
        $this->session
            ->has(UserInterface::class)
            ->willReturn(false);
        $this->session
            ->set(UserInterface::class, [
                'username' => 'foo',
                'role' => '',
            ])
            ->shouldBeCalled();
        $this->session
            ->regenerate()
            ->shouldBeCalled();

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);
        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([
            'user' => 'foo',
            'pass' => 'bar'
        ]);

        $this->userRegister
            ->authenticate('foo', 'bar')
            ->will([$this->authenticatedUser, 'reveal']);

        $this->authenticatedUser->getUsername()->willReturn('foo');
        $this->authenticatedUser->getUserRole()->willReturn('');

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [
                'username' => 'user',
                'password' => 'pass'
            ],
            $this->responsePrototype->reveal()
        );

        $result = $phpSession->authenticate($this->request->reveal());

        $this->assertSame($this->authenticatedUser->reveal(), $result);
    }

    public function testCanAuthenticateUserProvidedViaSession()
    {
        $this->session
            ->has(UserInterface::class)
            ->willReturn(true);
        $this->session
            ->get(UserInterface::class)
            ->willReturn([
                'username' => 'vimes',
                'role' => 'captain',
            ]);

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $result = $phpSession->authenticate($this->request->reveal());

        $this->assertInstanceOf(UserInterface::class, $result);
        $this->assertSame('vimes', $result->getUsername());
        $this->assertSame('captain', $result->getUserRole());
    }

    public function testAuthenticationWhenSessionUserIsOfIncorrectTypeResultsInUnsuccessfulAuthentication()
    {
        $this->session
            ->has(UserInterface::class)
            ->willReturn(true);
        $this->session
            ->get(UserInterface::class)
            ->willReturn('foo');

        $this->request->getAttribute('session')->will([$this->session, 'reveal']);


        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [],
            $this->responsePrototype->reveal()
        );

        $this->assertNull($phpSession->authenticate($this->request->reveal()));
    }

    public function testUnauthorizedResponse()
    {
        $this->responsePrototype
            ->getHeader('Location')
            ->willReturn(['/login']);
        $this->responsePrototype
            ->withHeader('Location', '/login')
            ->willReturn($this->responsePrototype->reveal());
        $this->responsePrototype
            ->withStatus(301)
            ->willReturn($this->responsePrototype->reveal());

        $phpSession = new PhpSession(
            $this->userRegister->reveal(),
            [ 'redirect' => '/login' ],
            $this->responsePrototype->reveal()
        );

        $result = $phpSession->unauthorizedResponse($this->request->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(['/login'], $result->getHeader('Location'));
    }
}
