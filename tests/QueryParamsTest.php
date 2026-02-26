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
class QueryParamsTest extends TestCase
{
    public function testQueryParams(): void
    {
        $exist = $_GET;
        $_GET = ['list' => 'ok', 0 => 'true', 'desc' => true];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        self::assertEquals($_GET, $sr->getQueryParams());

        $_GET = $exist;
    }
}
