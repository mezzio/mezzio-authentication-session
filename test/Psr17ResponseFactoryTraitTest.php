<?php

declare(strict_types=1);

namespace MezzioTest\Authentication\Session;

use Generator;
use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Authentication\Session\Response\CallableResponseFactoryDecorator;
use Mezzio\Container\ResponseFactoryFactory;
use MezzioTest\Authentication\Session\TestAsset\Psr17ResponseFactoryTraitImplementation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class Psr17ResponseFactoryTraitTest extends TestCase
{
    private Psr17ResponseFactoryTraitImplementation $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17ResponseFactoryTraitImplementation();
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<string,mixed>}>
     */
    public static function configurationsWithOverriddenResponseInterfaceFactory(): Generator
    {
        yield 'default' => [
            [
                'dependencies' => [
                    'factories' => [
                        ResponseInterface::class => static fn(): ResponseInterface => new TextResponse('Foo'),
                    ],
                ],
            ],
        ];

        yield 'aliased' => [
            [
                'dependencies' => [
                    'aliases' => [
                        ResponseInterface::class => 'CustomResponseInterface',
                    ],
                ],
            ],
        ];

        yield 'delegated' => [
            [
                'dependencies' => [
                    'delegators' => [
                        ResponseInterface::class => [
                            static fn(): ResponseInterface => new TextResponse('Hey!'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testWillUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsNotOverridden(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);

        // phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
        $container = new TestAsset\InMemoryContainer();
        $container->set('config', [
            'dependencies' => [
                'factories' => [
                    ResponseInterface::class => ResponseFactoryFactory::class,
                ],
            ],
        ]);
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $detectedResponseFactory = ($this->factory)($container);
        self::assertSame($responseFactory, $detectedResponseFactory);
    }

    /** @param array<string,mixed> $config */
    #[DataProvider('configurationsWithOverriddenResponseInterfaceFactory')]
    public function testWontUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsOverridden(
        array $config
    ): void {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);

        // phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
        $container = new TestAsset\InMemoryContainer();
        $container->set('config', $config);
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $response = $this->createMock(ResponseInterface::class);
        $container->set(ResponseInterface::class, static fn(): ResponseInterface => $response);

        $detectedResponseFactory = ($this->factory)($container);
        self::assertNotSame($responseFactory, $detectedResponseFactory);
        self::assertInstanceOf(CallableResponseFactoryDecorator::class, $detectedResponseFactory);
        self::assertEquals($response, $detectedResponseFactory->getResponseFromCallable());
    }
}
