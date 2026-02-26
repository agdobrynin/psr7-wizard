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
class ProtocolVersionTest extends TestCase
{
    #[DataProvider('protocolVersionProvider')]
    public function testProtocolVersion($server, $protocol): void
    {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: $server);

        self::assertEquals($protocol, $sr->getProtocolVersion());
    }

    public static function protocolVersionProvider(): Generator
    {
        yield 'Not set protocol' => [
            [],
            '1.1',
        ];

        yield 'Has version 1.1' => [
            [
                'DOCUMENT_ROOT' => '/home/slider/tmp',
                'REMOTE_ADDR' => '127.0.0.1',
                'REMOTE_PORT' => '40792',
                'SERVER_SOFTWARE' => 'PHP 8.2.14 Development Server',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => '/index.php',
            ],
            '1.1',
        ];

        yield 'Has version 1.0' => [
            [
                'DOCUMENT_ROOT' => '/home/slider/tmp',
                'REMOTE_ADDR' => '127.0.0.1',
                'REMOTE_PORT' => '40792',
                'SERVER_SOFTWARE' => 'PHP 8.2.14 Development Server',
                'SERVER_PROTOCOL' => 'HTTP/1.0',
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => '/index.php',
            ],
            '1.0',
        ];
    }
}
