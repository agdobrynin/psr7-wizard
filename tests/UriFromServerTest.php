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
class UriFromServerTest extends TestCase
{
    #[DataProvider('dataProviderServerHeaders')]
    public function testCreatingUriFromServerHeaders(array $srvArgs, string $expectUri): void
    {
        $exist = $_SERVER;
        $_SERVER = $srvArgs;

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        self::assertEquals($expectUri, (string) $sr->getUri());

        $_SERVER = $exist;
    }

    public static function dataProviderServerHeaders(): Generator
    {
        yield 'empty' => [
            [],
            '',
        ];

        yield 'with QUERY_STRING different REQUEST_URI' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'sort=false',
                'HTTP_HOST' => 'hello.st:8080',
            ],
            'http://hello.st:8080/index.php?list=ok',
        ];

        yield 'has HTTP_X_FORWARDED_PROTO' => [
            [
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
            ],
            'https://127.0.0.1:8080/',
        ];

        yield 'has HTTPS' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
                'HTTPS' => 'on',
            ],
            'https://127.0.0.1:8080/',
        ];

        yield 'has HTTPS off' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
                'HTTPS' => '1',
            ],
            'https://127.0.0.1:8080/',
        ];

        yield 'not has HTTP_HOST' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
            ],
            'http://127.0.0.1:8080/index.php?list=ok',
        ];

        yield 'without HTTP_HOST and SERVER_NAME' => [
            [
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
            ],
            '',
        ];

        yield 'without HTTP_HOST header but has non standard port SERVER_PORT' => [
            [
                'SERVER_NAME' => '[::1]',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => 'list=ok',
            ],
            'http://[::1]:8080/index.php?list=ok',
        ];

        yield 'without REQUEST_URI' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_METHOD' => 'GET',
                'ORIG_PATH_INFO' => '/index.php',
                'QUERY_STRING' => 'list=ok',
                'HTTP_HOST' => 'hello.st:8080',
            ],
            'http://hello.st:8080/index.php?list=ok',
        ];

        yield 'without REQUEST_URI and QUERY_STRING is "0"' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '80',
                'REQUEST_METHOD' => 'GET',
                'ORIG_PATH_INFO' => '/index.php',
                'QUERY_STRING' => '0',
            ],
            'http://127.0.0.1/index.php?0',
        ];

        yield 'without REQUEST_URI and ORIG_PATH_INFO but has QUERY_STRING' => [
            [
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '80',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'list=ok',
            ],
            'http://127.0.0.1/?list=ok',
        ];
    }
}
