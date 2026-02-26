<?php

declare(strict_types=1);

namespace Tests\Kaspi\Psr7Wizard;

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ServerRequestWizard::class)]
class CookieParamsTest extends TestCase
{
    public function testCookieParams(): void
    {
        $_COOKIE = ['store' => 'ok', 'live' => 'no'];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        self::assertEquals($_COOKIE, $sr->getCookieParams());
    }
}
