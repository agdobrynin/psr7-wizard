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
class ParsedBodyFromPostTest extends TestCase
{
    public function testParsedBodyFromPost(): void
    {
        $exist = $_POST;
        $_POST = ['list' => 'ok', 0 => 'true', 'desc' => true];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        self::assertEquals($_POST, $sr->getParsedBody());

        $_POST = $exist;
    }

    public function testParsedBodyFromPostIsEmpty(): void
    {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        self::assertNull($sr->getParsedBody());
    }
}
