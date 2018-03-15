<?php
/**
 * @see https://github.com/zendframework/zend-expressive-authentication-session
 *     for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-expressive-authentication-session/blob/master/LICENSE.md
 *     New BSD License
 */

namespace Zend\Expressive\Authentication\Session\Exception;

use RuntimeException;
use Zend\Expressive\Authentication\AuthenticationMiddleware;
use Zend\Expressive\Session\SessionMiddleware;

use function sprintf;

class MissingSessionContainerException extends RuntimeException implements ExceptionInterface
{
    public static function create() : self
    {
        return new self(sprintf(
            'Request is missing the attribute %s::SESSION_ATTRIBUTE ("session"); '
            . 'perhaps you forgot to inject the %s prior to the %s?',
            SessionMiddleware::class,
            SessionMiddleware::class,
            AuthenticationMiddleware::class
        ));
    }
}
