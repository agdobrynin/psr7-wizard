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
class BodyParamsTest extends TestCase
{
    public function testBodyParamsWithString(): void
    {
        $httpFactory = new HttpFactory();

        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: [], body: 'Hello world 😎');

        self::assertEquals('Hello world 😎', (string) $sr->getBody());
    }

    public function testBodyParamsWithStreamInterface(): void
    {
        $httpFactory = new HttpFactory();

        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: [], body: $httpFactory->createStream('🎨 Hello world'));

        self::assertEquals('🎨 Hello world', (string) $sr->getBody());
    }
}
