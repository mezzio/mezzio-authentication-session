<?php

declare(strict_types=1);

use mezziotest\authentication\session\testasset\inmemorycontainer;
use MezzioTest\Authentication\Session\TestAsset\InMemoryContainerPSR11V1;
use MezzioTest\Authentication\Session\TestAsset\InMemoryContainerPSR11V2;
use Psr\Container\ContainerInterface;

(static function () {
    $r      = new ReflectionMethod(ContainerInterface::class, 'has');
    $params = $r->getParameters();
    $id     = array_shift($params);
    // phpcs:disable WebimpressCodingStandard.Formatting.StringClassReference.Found
    $id->hasType()
        ? class_alias(InMemoryContainerPSR11V2::class, inmemorycontainer::class)
        : class_alias(InMemoryContainerPSR11V1::class, inmemorycontainer::class);
    // phpcs:enable WebimpressCodingStandard.Formatting.StringClassReference.Found
})();
