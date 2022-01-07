<?php

declare(strict_types=1);

namespace MezzioTest\Authentication\Session;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\DefaultUser;
use Mezzio\Authentication\Session\ConfigProvider;
use Mezzio\Authentication\Session\Exception;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpSessionTest extends TestCase
{
    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var UserRepositoryInterface|ObjectProphecy */
    private $userRegister;

    /** @var UserInterface|ObjectProphecy */
    private $authenticatedUser;

    /** @var ResponseInterface|ObjectProphecy */
    private $responsePrototype;

    /** @var callable */
    private $responseFactory;

    /** @var callable */
    private $userFactory;

    /** @var SessionInterface|ObjectProphecy */
    private $session;

    /** @var array */
    private $defaultConfig;

    public function testConstructor(): void
    {
        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );
        $this->assertInstanceOf(AuthenticationInterface::class, $phpSession);
    }

    public function testAuthenticationWithMissingSessionAttributeRaisesException(): void
    {
        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn(null);

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $this->expectException(Exception\MissingSessionContainerException::class);
        $phpSession->authenticate($this->request);
    }

    public function testAuthenticationWhenSessionDoesNotContainUserAndRequestIsGetReturnsNull(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(false);
        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);
        $this->request->expects(self::atLeastOnce())
                      ->method('getMethod')
                      ->willReturn('GET');

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $this->assertNull($phpSession->authenticate($this->request));
    }

    public function testAuthenticationWithNoSessionUserViaPostWithNoDataReturnsNull(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(false);

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);
        $this->request->expects(self::atLeastOnce())
                      ->method('getMethod')
                      ->willReturn('POST');
        $this->request->expects(self::atLeastOnce())
                      ->method('getParsedBody')
                      ->willReturn([]);

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $this->assertNull($phpSession->authenticate($this->request));
    }

    public function testAuthenticationWithNoSessionUserViaPostWithDefaultFieldsCanHaveSuccessfulResult(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(false);
        $this->session->expects(self::atLeastOnce())
                      ->method('set')
                      ->with(
                          UserInterface::class,
                          [
                              'username' => 'vimes',
                              'roles'    => ['captain'],
                              'details'  => ['gender' => 'male'],
                          ]
                      );
        $this->session->expects(self::atLeastOnce())
                      ->method('regenerate');

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);
        $this->request->expects(self::atLeastOnce())
                      ->method('getMethod')
                      ->willReturn('POST');
        $this->request->expects(self::atLeastOnce())
                      ->method('getParsedBody')
                      ->willReturn(
                          [
                              'username' => 'foo',
                              'password' => 'bar',
                          ]
                      );

        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getIdentity')
                                ->willReturn('vimes');
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getRoles')
                                ->willReturn(['captain']);
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getDetails')
                                ->willReturn(['gender' => 'male']);

        $this->userRegister->expects(self::atLeastOnce())
                           ->method('authenticate')
                           ->with('foo', 'bar')
                           ->willReturn($this->authenticatedUser);

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $result = $phpSession->authenticate($this->request);

        $this->assertSame($this->authenticatedUser, $result);
    }

    public function testAuthenticationWithNoSessionUserViaPostWithCustomFieldsCanHaveSuccessfulResult(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(false);
        $this->session->expects(self::atLeastOnce())
                      ->method('set')
                      ->with(
                          UserInterface::class,
                          [
                              'username' => 'foo',
                              'roles'    => [],
                              'details'  => [],
                          ]
                      );
        $this->session->expects(self::atLeastOnce())
                      ->method('regenerate');

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);
        $this->request->expects(self::atLeastOnce())
                      ->method('getMethod')
                      ->willReturn('POST');
        $this->request->expects(self::atLeastOnce())
                      ->method('getParsedBody')
                      ->willReturn(
                          [
                              'user' => 'foo',
                              'pass' => 'bar',
                          ]
                      );

        $this->userRegister->expects(self::atLeastOnce())
                           ->method('authenticate')
                           ->with('foo', 'bar')
                           ->willReturn($this->authenticatedUser);

        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getIdentity')
                                ->willReturn('foo');
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getRoles')
                                ->willReturn([]);
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getDetails')
                                ->willReturn([]);

        $phpSession = new PhpSession(
            $this->userRegister,
            [
                'username' => 'user',
                'password' => 'pass',
            ],
            $this->responseFactory,
            $this->userFactory
        );

        $result = $phpSession->authenticate($this->request);

        $this->assertSame($this->authenticatedUser, $result);
    }

    public function testCanAuthenticateUserProvidedViaSession(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(true);
        $this->session->expects(self::atLeastOnce())
                      ->method('get')
                      ->with(UserInterface::class)
                      ->willReturn(
                          [
                              'username' => 'vimes',
                              'roles'    => ['captain'],
                              'details'  => ['gender' => 'male'],
                          ]
                      );

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $result = $phpSession->authenticate($this->request);

        $this->assertInstanceOf(UserInterface::class, $result);
        $this->assertSame('vimes', $result->getIdentity());
        $this->assertSame(['captain'], $result->getRoles());
        $this->assertSame(['gender' => 'male'], $result->getDetails());
        $this->assertSame('male', $result->getDetail('gender'));
    }

    public function testAuthenticationWhenSessionUserIsOfIncorrectTypeResultsInUnsuccessfulAuthentication(): void
    {
        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(true);
        $this->session->expects(self::atLeastOnce())
                      ->method('get')
                      ->with(UserInterface::class)
                      ->willReturn('foo');

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);

        $phpSession = new PhpSession(
            $this->userRegister,
            $this->defaultConfig,
            $this->responseFactory,
            $this->userFactory
        );

        $this->assertNull($phpSession->authenticate($this->request));
    }

    public function testUnauthorizedResponse(): void
    {
        $this->responsePrototype->expects(self::atLeastOnce())
                                ->method('getHeader')
                                ->with('Location')
                                ->willReturn(['/login']);
        $this->responsePrototype->expects(self::atLeastOnce())
                                ->method('withHeader')
                                ->with('Location', '/login')
                                ->willReturn($this->responsePrototype);
        $this->responsePrototype->expects(self::atLeastOnce())
                                ->method('withStatus')
                                ->with(302)
                                ->willReturn($this->responsePrototype);

        $phpSession = new PhpSession(
            $this->userRegister,
            ['redirect' => '/login'],
            $this->responseFactory,
            $this->userFactory
        );

        $result = $phpSession->unauthorizedResponse($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(['/login'], $result->getHeader('Location'));
    }

    public function testIterableRolesWillBeConvertedToArray(): void
    {
        $roleGenerator = function () {
            yield 'captain';
        };

        $this->session->expects(self::atLeastOnce())
                      ->method('has')
                      ->with(UserInterface::class)
                      ->willReturn(false);
        $this->session->expects(self::atLeastOnce())
                      ->method('set')
                      ->with(
                          UserInterface::class,
                          [
                              'username' => 'foo',
                              'roles'    => ['captain'],
                              'details'  => [],
                          ]
                      );
        $this->session->expects(self::atLeastOnce())
                      ->method('regenerate');

        $this->request->expects(self::atLeastOnce())
                      ->method('getAttribute')
                      ->with('session')
                      ->willReturn($this->session);
        $this->request->expects(self::atLeastOnce())
                      ->method('getMethod')
                      ->willReturn('POST');
        $this->request->expects(self::atLeastOnce())
                      ->method('getParsedBody')
                      ->willReturn(
                          [
                              'user' => 'foo',
                              'pass' => 'bar',
                          ]
                      );

        $this->userRegister->expects(self::atLeastOnce())
                           ->method('authenticate')
                           ->with('foo', 'bar')
                           ->willReturn($this->authenticatedUser);

        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getIdentity')
                                ->willReturn('foo');
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getRoles')
                                ->willReturn($roleGenerator());
        $this->authenticatedUser->expects(self::atLeastOnce())
                                ->method('getDetails')
                                ->willReturn([]);

        $phpSession = new PhpSession(
            $this->userRegister,
            [
                'username' => 'user',
                'password' => 'pass',
            ],
            $this->responseFactory,
            $this->userFactory
        );

        $result = $phpSession->authenticate($this->request);

        $this->assertSame($this->authenticatedUser, $result);
    }

    protected function setUp(): void
    {
        $this->request           = $this->createMock(ServerRequestInterface::class);
        $this->userRegister      = $this->createMock(UserRepositoryInterface::class);
        $this->authenticatedUser = $this->createMock(UserInterface::class);
        $this->responsePrototype = $this->createMock(ResponseInterface::class);
        $this->responseFactory   = function () {
            return $this->responsePrototype;
        };
        $this->userFactory       = function (string $identity, array $roles = [], array $details = []): UserInterface {
            return new DefaultUser($identity, $roles, $details);
        };
        $this->session           = $this->createMock(SessionInterface::class);
        $this->defaultConfig     = (new ConfigProvider())()['authentication'];
    }
}
