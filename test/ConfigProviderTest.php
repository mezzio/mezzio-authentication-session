<?php

declare(strict_types=1);

namespace MezzioTest\Authentication\Session;

use Mezzio\Authentication\Session\ConfigProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray(): array
    {
        $config = ($this->provider)();
        self::assertIsArray($config);
        return $config;
    }

    #[Depends('testInvocationReturnsArray')]
    public function testReturnedArrayContainsDependencies(array $config): void
    {
        self::assertArrayHasKey('dependencies', $config);
        self::assertIsArray($config['dependencies']);
    }
}
