<?php

declare(strict_types=1);

namespace Mezzio\Authentication\Session\Exception;

use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\Session\SessionMiddleware;
use RuntimeException;

use function sprintf;

class MissingSessionContainerException extends RuntimeException implements ExceptionInterface
{
    public static function create(): self
    {
        return new self(sprintf(
            'Request is missing the attribute %s::SESSION_ATTRIBUTE ("%s"); '
            . 'perhaps you forgot to inject the %s prior to the %s?',
            SessionMiddleware::class,
            SessionMiddleware::SESSION_ATTRIBUTE,
            SessionMiddleware::class,
            AuthenticationMiddleware::class
        ));
    }
}
