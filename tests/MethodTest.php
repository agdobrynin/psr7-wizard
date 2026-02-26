<?php

declare(strict_types=1);

namespace Tests\Kaspi\Psr7Wizard;

use Generator;
use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ServerRequestWizard::class)]
class MethodTest extends TestCase
{
    #[DataProvider('dataProviderGetMethod')]
    public function testGetMethod($server, $method): void
    {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: $server);

        self::assertEquals($method, $sr->getMethod());
    }

    public static function dataProviderGetMethod(): Generator
    {
        yield 'Method get' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
            ],
            'GET',
        ];

        yield 'Method post' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'POST',
                'PHP_SELF' => '/index.php',
            ],
            'POST',
        ];

        yield 'Method not set' => [
            [],
            'GET',
        ];
    }
}
